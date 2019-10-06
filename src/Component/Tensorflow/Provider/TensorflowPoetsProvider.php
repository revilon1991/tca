<?php

declare(strict_types=1);

namespace App\Component\Tensorflow\Provider;

use App\Component\Tensorflow\Dto\TensorflowPoetsPredictDto;
use App\Component\Tensorflow\Dto\TensorflowPredictInterface;
use App\Component\Tensorflow\Enum\ClassificationEnum;
use App\Component\Tensorflow\Exception\TensorflowException;
use Psr\Log\LoggerInterface;
use ReflectionException;
use function exec;
use function file_exists;
use function implode;
use function sprintf;

class TensorflowPoetsProvider implements TensorflowProviderInterface
{
    private const BOTTLENECKS_DIRECTORY_NAME = 'bottlenecks';
    private const MODELS_DIRECTORY_NAME = 'models';
    private const RETRAINED_GRAPH_FILENAME = 'retrained_graph.pb';
    private const RETRAINED_LABELS_FILENAME = 'retrained_labels.txt';
    private const TRAINING_SUMMARIES_DIRECTORY_NAME = 'training_summaries';
    private const TENSORFLOW_MOBILENET_ARCHITECTURE = 'mobilenet_0.50_224';
    private const RETRAINED_IMAGE_DIRECTORY_NAME = 'retrained_image';
    private const IMAGE_PREDICT_CHUNK = 100;

    /**
     * @var string
     */
    private $tensorflowForPoetsRepositoryPath;

    /**
     * @var string
     */
    private $projectDir;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @required
     *
     * @param string $tensorflowForPoetsRepositoryPath
     * @param string $projectDir
     * @param LoggerInterface $logger
     */
    public function dependencyInjection(
        string $tensorflowForPoetsRepositoryPath,
        string $projectDir,
        LoggerInterface $logger
    ): void {
        $this->tensorflowForPoetsRepositoryPath = $tensorflowForPoetsRepositoryPath;
        $this->projectDir = $projectDir;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     *
     * @throws ReflectionException
     * @throws TensorflowException
     */
    public function retrain(string $classificationModel): string
    {
        $modelPath = $this->getModelPath($classificationModel);

        [$outputCommand, $returnCode] = $this->doRetrain($modelPath);

        if ($returnCode !== 0) {
            $providerClassName = self::class;

            throw new TensorflowException("Provider $providerClassName exit with non zero code ($returnCode)");
        }

        return implode(PHP_EOL, $outputCommand);
    }

    /**
     * {@inheritdoc}
     *
     * @throws ReflectionException
     * @throws TensorflowException
     */
    public function predict(TensorflowPredictInterface $imageDto): array
    {
        foreach ($imageDto->getImageList() as $image) {
            if (!file_exists($image)) {
                throw new TensorflowException("File '$image' do not exist");
            }
        }

        $predictList = [];
        $predictListChunk = [];

        $countImages = count($imageDto->getImageList());
        $this->logger->info("Predict start for $countImages images");

        foreach (array_chunk($imageDto->getImageList(), self::IMAGE_PREDICT_CHUNK) as $imageList) {
            [$outputCommand, $returnCode] = $this->doPredict(
                $imageList,
                $imageDto->getClassificationModel()
            );

            if ($returnCode !== 0) {
                $providerClassName = self::class;
                $message = implode(PHP_EOL, $outputCommand);

                throw new TensorflowException(
                    "Provider '$providerClassName' exit with non zero code ($returnCode): $message"
                );
            }

            $jsonString = implode(PHP_EOL, $outputCommand);
            $predictListChunk[] = json_decode($jsonString, true);

            if ($predictList === false) {
                throw new TensorflowException("Predict can\'t parse. See output: $outputCommand");
            }

            $imagePredictChunk = array_map('count', $predictListChunk);
            $imagePredictChunk = array_sum($imagePredictChunk);
            $this->logger->info("Predict complete for $imagePredictChunk image");
        }

        $predictList = array_merge(...$predictListChunk);

        return $predictList;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(TensorflowPredictInterface $entryDto): bool
    {
        return $entryDto instanceof TensorflowPoetsPredictDto;
    }

    /**
     * @param string $classificationModel
     *
     * @return string
     *
     * @throws TensorflowException
     * @throws ReflectionException
     */
    private function getModelPath(string $classificationModel): string
    {
        switch ($classificationModel) {
            case ClassificationEnum::PEOPLE:
                return "$this->projectDir/var/tensorflow/models/poets/people";

            case ClassificationEnum::MALE:
                return "$this->projectDir/var/tensorflow/models/poets/male";

            default:
                $availableClassification = implode(', ', ClassificationEnum::getEnumList());

                throw new TensorflowException(
                    "Classification model '$classificationModel' not found. Available $availableClassification"
                );
        }
    }

    /**
     * @param string $modelPath
     *
     * @return array
     */
    private function doRetrain(string $modelPath): array
    {
        $command = sprintf('cd %s && python3 -m scripts.retrain ', $this->tensorflowForPoetsRepositoryPath);

        $bottlenecksDirectoryName = self::BOTTLENECKS_DIRECTORY_NAME;
        $modelsDirectoryName = self::MODELS_DIRECTORY_NAME;
        $retrainedGraphFilename = self::RETRAINED_GRAPH_FILENAME;
        $retrainedLabelsFilename = self::RETRAINED_LABELS_FILENAME;
        $trainingSummariesDirectoryName = self::TRAINING_SUMMARIES_DIRECTORY_NAME;
        $tensorflowMobilenetArchitecture = self::TENSORFLOW_MOBILENET_ARCHITECTURE;
        $retrainedImageDirectoryName = self::RETRAINED_IMAGE_DIRECTORY_NAME;

        $options = '--how_many_training_steps=500 ';
        $options .= "--bottleneck_dir=$modelPath/$bottlenecksDirectoryName ";
        $options .= "--model_dir=$modelPath/$modelsDirectoryName ";
        $options .= "--output_graph=$modelPath/$retrainedGraphFilename ";
        $options .= "--output_labels=$modelPath/$retrainedLabelsFilename ";
        $options .= "--architecture=$tensorflowMobilenetArchitecture ";
        $options .= "--summaries_dir=$modelPath/$trainingSummariesDirectoryName/$tensorflowMobilenetArchitecture ";
        $options .= "--image_dir=$modelPath/$retrainedImageDirectoryName ";

        $command .= $options;

        exec($command, $outputCommand, $returnCode);

        return [$outputCommand, $returnCode];
    }

    /**
     * @param array $imageList
     * @param string $classificationModel
     *
     * @return array
     *
     * @throws ReflectionException
     * @throws TensorflowException
     */
    private function doPredict(array $imageList, string $classificationModel): array
    {
        $dataPath = $this->getModelPath($classificationModel);

        $labelImageBulkScriptPath = __DIR__ . '/../scripts';
        $command = "cd $labelImageBulkScriptPath && python3 label_image_bulk.py ";

        $retrainedGraphFilename = self::RETRAINED_GRAPH_FILENAME;
        $retrainedLabelsFilename = self::RETRAINED_LABELS_FILENAME;

        $options = "$dataPath/$retrainedGraphFilename ";
        $options .= "$dataPath/$retrainedLabelsFilename ";
        $options .= implode(' ', $imageList);

        $command .= $options;

        exec($command, $outputCommand, $returnCode);

        return [$outputCommand, $returnCode];
    }
}
