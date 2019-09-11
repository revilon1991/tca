<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RetrainTensorflowCommand extends Command
{
    protected static $defaultName = 'tf:retrain';

    /**
     * @var string
     */
    private $tensorflowForPoetsRepositoryPath;

    /**
     * @var string
     */
    private $tensorflowDataPath;

    /**
     * @var string
     */
    private $tensorflowArchitecture;

    /**
     * @required
     *
     * @param string $tensorflowForPoetsRepositoryPath
     * @param string $tensorflowDataPath
     * @param string $tensorflowArchitecture
     */
    public function dependencyInjection(
        string $tensorflowForPoetsRepositoryPath,
        string $tensorflowDataPath,
        string $tensorflowArchitecture
    ): void {
        $this->tensorflowForPoetsRepositoryPath = $tensorflowForPoetsRepositoryPath;
        $this->tensorflowDataPath = $tensorflowDataPath;
        $this->tensorflowArchitecture = $tensorflowArchitecture;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->addOption('write-output', 'w', InputOption::VALUE_NONE)
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $dataPath = $this->tensorflowDataPath;

        $command = sprintf('cd %s && python3 -m scripts.retrain ', $this->tensorflowForPoetsRepositoryPath);

        $options = '--how_many_training_steps=500 ';
        $options .= sprintf('--bottleneck_dir=%s/bottlenecks ', $dataPath);
        $options .= sprintf('--model_dir=%s/models ', $dataPath);
        $options .= sprintf('--output_graph=%s/retrained_graph.pb ', $dataPath);
        $options .= sprintf('--output_labels=%s/retrained_labels.txt ', $dataPath);
        $options .= sprintf('--architecture=%s ', $this->tensorflowArchitecture);
        $options .= sprintf('--summaries_dir=%s/training_summaries/%s ', $dataPath, $this->tensorflowArchitecture);
        $options .= sprintf('--image_dir=%s/retrained_image ', $dataPath);

        if (!$input->getOption('write-output')) {
            $options .= '2>/dev/null';
        }

        $command .= $options;

        exec($command, $outputCommand, $returnVar);

        if ($input->getOption('write-output')) {
            $output->writeln($outputCommand);
        }

        if ($returnVar !== 0) {
            throw new RuntimeException(sprintf(
                '%s command exit with non zero code (%s)',
                $command,
                $returnVar
            ));
        }
    }
}
