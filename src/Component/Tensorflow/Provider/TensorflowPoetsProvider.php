<?php

declare(strict_types=1);

namespace App\Component\Tensorflow\Provider;

use App\Component\Tensorflow\Dto\TensorflowImageInterface;
use App\Component\Tensorflow\Dto\TensorflowPoetsImageDto;
use App\Component\Tensorflow\Exception\TensorflowException;
use function implode;
use function file_exists;
use function preg_match_all;
use function sprintf;
use function exec;

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
    private $tensorflowDataPath;

    /**
     * @required
     *
     * @param string $tensorflowForPoetsRepositoryPath
     * @param string $tensorflowDataPath
     */
    public function dependencyInjection(
        string $tensorflowForPoetsRepositoryPath,
        string $tensorflowDataPath
    ): void {
        $this->tensorflowForPoetsRepositoryPath = $tensorflowForPoetsRepositoryPath;
        $this->tensorflowDataPath = $tensorflowDataPath;
    }

    /**
     * {@inheritdoc}
     *
     * @throws TensorflowException
     */
    public function retrain(): string
    {
        [$outputCommand, $returnCode] = $this->doRetrain();

        if ($returnCode !== 0) {
            $providerClassName = self::class;

            throw new TensorflowException("Provider $providerClassName exit with non zero code ($returnCode)");
        }

        return implode(PHP_EOL, $outputCommand);
    }

    /**
     * {@inheritdoc}
     *
     * @throws TensorflowException
     */
    public function predict(TensorflowImageInterface $image): ?string
    {
        if (!file_exists($image->getImage())) {
            throw new TensorflowException("File '$image' do not exist");
        }

        [$outputCommand, $returnCode] = $this->doPredict($image->getImage());

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
    public function supports(TensorflowImageInterface $entryDto): bool
    {
        return $entryDto instanceof TensorflowPoetsImageDto;
    }

    /**
     * @return array
     */
    private function doRetrain(): array
    {
        $dataPath = $this->tensorflowDataPath;

        $command = sprintf('cd %s && python3 -m scripts.retrain ', $this->tensorflowForPoetsRepositoryPath);

        $bottlenecksDirectoryName = self::BOTTLENECKS_DIRECTORY_NAME;
        $modelsDirectoryName = self::MODELS_DIRECTORY_NAME;
        $retrainedGraphFilename = self::RETRAINED_GRAPH_FILENAME;
        $retrainedLabelsFilename = self::RETRAINED_LABELS_FILENAME;
        $trainingSummariesDirectoryName = self::TRAINING_SUMMARIES_DIRECTORY_NAME;
        $tensorflowMobilenetArchitecture = self::TENSORFLOW_MOBILENET_ARCHITECTURE;
        $retrainedImageDirectoryName = self::RETRAINED_IMAGE_DIRECTORY_NAME;

        $options = '--how_many_training_steps=500 ';
        $options .= "--bottleneck_dir=$dataPath/$bottlenecksDirectoryName ";
        $options .= "--model_dir=$dataPath/$modelsDirectoryName ";
        $options .= "--output_graph=$dataPath/$retrainedGraphFilename ";
        $options .= "--output_labels=$dataPath/$retrainedLabelsFilename ";
        $options .= "--architecture=$tensorflowMobilenetArchitecture ";
        $options .= "--summaries_dir=$dataPath/$trainingSummariesDirectoryName/$tensorflowMobilenetArchitecture ";
        $options .= "--image_dir=$dataPath/$retrainedImageDirectoryName ";

        $command .= $options;

        exec($command, $outputCommand, $returnCode);

        return [$outputCommand, $returnCode];
    }

    /**
     * @param string $image
     *
     * @return array
     */
    private function doPredict(string $image): array
    {
        $dataPath = $this->tensorflowDataPath;

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
