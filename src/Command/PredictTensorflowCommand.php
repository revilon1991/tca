<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PredictTensorflowCommand extends Command
{
    protected static $defaultName = 'tf:predict';

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
     */
    protected function configure(): void
    {
        $this
            ->addArgument('pathname_image_entry')
            ->addOption('write-output', 'w', InputOption::VALUE_NONE)
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $dataPath = $this->tensorflowDataPath;

        $command = sprintf('cd %s && python3 -m scripts.label_image ', $this->tensorflowForPoetsRepositoryPath);

        $options = sprintf('--graph=%s/retrained_graph.pb ', $dataPath);
        $options .= sprintf('--image=%s ', $input->getArgument('pathname_image_entry'));
        $options .= sprintf('--labels=%s/retrained_labels.txt ', $dataPath);

        if (!$input->getOption('write-output')) {
            $options .= '2>/dev/null';
        }

        $command .= $options;

        exec($command, $outputCommand, $returnVar);

        if (!$input->getOption('write-output')) {
            $predict = array_slice($outputCommand, -5, 1);

            $output->writeln($predict);
        } else {
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
