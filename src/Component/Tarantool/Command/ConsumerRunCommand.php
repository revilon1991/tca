<?php

declare(strict_types=1);

namespace App\Component\Tarantool\Command;

use App\Component\Tarantool\Adapter\TarantoolQueueAdapter;
use App\Component\Tarantool\Exception\ConsumerSilentException;
use App\Component\Tarantool\Exception\QueueTarantoolException;
use App\Component\Tarantool\Exception\ReleasePartialException;
use App\Component\Tarantool\Handler\ConsumerHandler;
use App\Component\Tarantool\Queue\Queue;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function pcntl_signal;

class ConsumerRunCommand extends Command
{
    private const TIMEOUT = 0.01;

    /**
     * @var ConsumerHandler
     */
    private $consumerHandler;

    /**
     * @var TarantoolQueueAdapter
     */
    private $queueAdapter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @required
     *
     * @param ConsumerHandler $consumerHandler
     * @param TarantoolQueueAdapter $queueAdapter
     * @param LoggerInterface $logger
     */
    public function dependencyInjection(
        ConsumerHandler $consumerHandler,
        TarantoolQueueAdapter $queueAdapter,
        LoggerInterface $logger
    ): void {
        $this->consumerHandler = $consumerHandler;
        $this->queueAdapter = $queueAdapter;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('tarantool:consumer:run')
            ->setDescription('Run consumer')
            ->setHelp('This command allows you to run any consumer by his name')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of the consumer')
        ;
    }

    /**
     * {@inheritdoc}
     *
     * @throws QueueTarantoolException
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        declare(ticks=1);

        pcntl_signal(SIGTERM, [$this, 'stopConsumer']);
        pcntl_signal(SIGINT, [$this, 'stopConsumer']);
        pcntl_signal(SIGHUP, [$this, 'stopConsumer']);

        $name = $input->getArgument('name');
        $consumer = $this->consumerHandler->getConsumer($name);

        $this->queueAdapter->initQueue($consumer->getQueueName(), $consumer->getQueueType());
        $queue = $this->queueAdapter->getQueue($consumer->getQueueName());

        while (true) {
            $taskList = $queue->takeList($consumer->getBatchSize(), self::TIMEOUT);

            if (empty($taskList)) {
                $microseconds = $consumer->getSleepDuration() * 1000000;
                usleep((int)$microseconds);

                continue;
            }

            $taskIdList = array_keys($taskList);

            try {
                $consumer->process($taskList);

                $queue->ackList($taskIdList);
            } catch (ConsumerSilentException $exception) {
                $this->rollbackTaskList($taskIdList, $queue);
            } catch (ReleasePartialException $exception) {
                $delay = $exception->getDelay();
                $releaseTaskIdList = $exception->getReleaseTaskIdList();

                $askTaskIdList = array_diff($taskIdList, $releaseTaskIdList);

                $this->rollbackTaskList($releaseTaskIdList, $queue, $delay);
                $queue->ackList($askTaskIdList);
            } catch (Exception $exception) {
                $this->rollbackTaskList($taskIdList, $queue);

                if ($this->logger) {
                    $this->logger->warning('Error process queue', ['errorMessage' => $exception->getMessage()]);
                }

                throw $exception;
            } finally {
                if ($consumer->isPropagationStopped()) {
                    $this->logger->info('Consumer has been propagation stopped forcibly');

                    exit(0);
                }
            }
        }

        return 0;
    }

    private function stopConsumer(): void
    {
        $this->logger->info('Consumer has been stopped forcibly');

        exit();
    }

    /**
     * @param array $taskIdList
     * @param Queue $queue
     * @param int $delay
     */
    private function rollbackTaskList(array $taskIdList, Queue $queue, int $delay = 10): void
    {
        foreach ($taskIdList as $taskId) {
            $queue->release($taskId, ['delay' => $delay]);
        }
    }
}
