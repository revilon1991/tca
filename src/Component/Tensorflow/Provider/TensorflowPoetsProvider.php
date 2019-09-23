<?php

declare(strict_types=1);

namespace App\Component\Tensorflow\Provider;

use App\Component\Tensorflow\Dto\TensorflowPoetsPredictDto;
use App\Component\Tensorflow\Dto\TensorflowPredictInterface;
use App\Component\Tensorflow\Enum\ClassificationEnum;
use App\Component\Tensorflow\Exception\TensorflowException;
use ReflectionException;
use function exec;
use function file_exists;
use function implode;
use function preg_match_all;
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
    private const PROBABILITY_COEFFICIENT = 0.5;
    /**
     * @var string
     */
    private $tensorflowForPoetsRepositoryPath;

    /**
     * @var string
     */
    private $projectDir;

    /**
     * @required
     *
     * @param string $tensorflowForPoetsRepositoryPath
     * @param string $projectDir
     */
    public function dependencyInjection(
        string $tensorflowForPoetsRepositoryPath,
        string $projectDir
    ): void {
        $this->tensorflowForPoetsRepositoryPath = $tensorflowForPoetsRepositoryPath;
        $this->projectDir = $projectDir;
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
    public function predict(TensorflowPredictInterface $image): ?string
    {
        if (!file_exists($image->getImage())) {
            throw new TensorflowException("File '$image' do not exist");
        }

        [$outputCommand, $returnCode] = $this->doPredict($image->getImage(), $image->getClassificationModel());

        if ($returnCode !== 0) {
            $providerClassName = self::class;

            throw new TensorflowException("Provider '$providerClassName' exit with non zero code ($returnCode)");
        }

        $outputCommand = implode(PHP_EOL, $outputCommand);

        preg_match_all('#(\w+)\s\(score=(\d\.\d+)\)#', $outputCommand, $matches);

        if (empty($matches[2])) {
            throw new TensorflowException("Predict can\'t parse. See output: $outputCommand");
        }

        $coefficientPredict = (float)$matches[2][0];

        if ($coefficientPredict < self::PROBABILITY_COEFFICIENT) {
            return null;
        }

        return $matches[1][0];
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
     * @param string $image
     * @param string $classificationModel
     *
     * @return array
     *
     * @throws ReflectionException
     * @throws TensorflowException
     */
    private function doPredict(string $image, string $classificationModel): array
    {
        $dataPath = $this->getModelPath($classificationModel);

        $command = "cd $this->tensorflowForPoetsRepositoryPath && python3 -m scripts.label_image ";

        $retrainedGraphFilename = self::RETRAINED_GRAPH_FILENAME;
        $retrainedLabelsFilename = self::RETRAINED_LABELS_FILENAME;

        $options = "--graph=$dataPath/$retrainedGraphFilename ";
        $options .= "--image=$image ";
        $options .= "--labels=$dataPath/$retrainedLabelsFilename ";
        $options .= '2>/dev/null';

        $command .= $options;

        exec($command, $outputCommand, $returnCode);

        return [$outputCommand, $returnCode];
    }
}
