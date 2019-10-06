import os, sys
import tensorflow as tf
import numpy as np
import json

os.environ['TF_CPP_MIN_LOG_LEVEL'] = '3'

retrained_graph=sys.argv[1]
retrained_labels=sys.argv[2]
files=sys.argv[3:]
json_data=dict()

# Loads label file, strips off carriage return
label_list = [line.rstrip() for line in tf.io.gfile.GFile(retrained_labels)]

# Unpersists graph from file
graph = tf.Graph()
graph_def = tf.compat.v1.GraphDef()
with open(retrained_graph, "rb") as f:
    graph_def.ParseFromString(f.read())
with graph.as_default():
    tf.import_graph_def(graph_def)

input_operation = graph.get_operation_by_name("import/input")
output_operation = graph.get_operation_by_name("import/final_result")

with tf.compat.v1.Session(graph=graph) as sess:
    for file in files:
        # Read and Normalize jpeg
        file_reader = tf.io.read_file(file, "file_reader")
        image_reader = tf.image.decode_jpeg(file_reader, channels=3, name='jpeg_reader')
        float_caster = tf.cast(image_reader, tf.float32)
        dims_expander = tf.expand_dims(float_caster, 0)
        resized = tf.compat.v1.image.resize_bilinear(dims_expander, [224, 224])
        normalized = tf.divide(tf.subtract(resized, [128]), [128])
        sess = tf.compat.v1.Session()
        t = sess.run(normalized)

        predictions = sess.run(output_operation.outputs[0], {input_operation.outputs[0]: t})
        results = np.squeeze(predictions)
        top_k = results.argsort()[-1:][::-1][0]

        json_data[file] = {
            "label": label_list[top_k],
            "probability": float("{:0.5f}".format(results[top_k])),
        }

    print(json.dumps(json_data))
