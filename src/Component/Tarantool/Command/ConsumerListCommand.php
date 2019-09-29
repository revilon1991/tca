<?php

declare(strict_types=1);

namespace App\Component\Tarantool\Command;

use App\Component\Tarantool\Consumer\AbstractConsumer;
use App\Component\Tarantool\Handler\ConsumerHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ConsumerListCommand extends Command
{
    /**
     * @var ConsumerHandler
     */
    private $consumerHandler;

    /**
     * @required
     *
     * @param ConsumerHandler $consumerHandler
     */
    public function dependencyInjection(ConsumerHandler $consumerHandler): void
    {
        $this->consumerHandler = $consumerHandler;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('tarantool:consumer:list')
            ->setDescription('Shows all registered consumers')
            ->setHelp('This command allows you to view list of the all consumers in the system')
            ->addOption('type', 't', InputOption::VALUE_OPTIONAL, 'Filter consumer by type')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $consumerList = $this->consumerHandler->getConsumerList();
        ksort($consumerList);

        $consumerCount = count($consumerList);

        if ($consumerCount === 0) {
            $output->writeln('<comment>You have not yet any registered consumer</comment>');

            return;
        }

        $filterByType = $input->getOption('type');

        $consoleStyle = new SymfonyStyle($input, $output);

        $table = new Table($output);
        $table->setHeaders(['Queue Name', 'Queue Type', 'Batch Size', 'Sleep Duration (sec)']);

        foreach ($consumerList as $consumer) {
            $queueType = $consumer->getQueueType();

            if (null !== $filterByType && $queueType !== $filterByType) {
                --$consumerCount;

                continue;
            }

            $batchSize = $consumer->getBatchSize();
            $sleepDuration = number_format($consumer->getSleepDuration(), 3);

            if ($queueType === AbstractConsumer::DEFAULT_QUEUE_TYPE) {
                $queueType = sprintf('%s <comment>default</comment>', $queueType);
            }

            if ($batchSize === AbstractConsumer::DEFAULT_BATCH_SIZE) {
                $batchSize = sprintf('%s <comment>default</comment>', $batchSize);
            }

            if ($consumer->getSleepDuration() === AbstractConsumer::DEFAULT_SLEEP_DURATION) {
                $sleepDuration = sprintf('%s <comment>default</comment>', $sleepDuration);
            }

            $table->addRow([$consumer->getQueueName(), $queueType, $batchSize, $sleepDuration]);
        }

        $consoleStyle->text(sprintf('Total consumers count: <comment>%s</comment>', $consumerCount));

        if ($consumerCount > 0) {
            $table->render();
        }
    }
}
