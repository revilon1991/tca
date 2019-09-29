<?php

declare(strict_types=1);

namespace App\Component\Tarantool\Adapter;

use App\Component\Tarantool\Exception\QueueTarantoolException;
use App\Component\Tarantool\Handler\ConsumerHandler;
use App\Component\Tarantool\Queue\Queue;
use App\Component\Tarantool\Queue\QueuePool;
use Exception;
use Tarantool\Client\Client;
use Tarantool\Queue\States;
use Tarantool\Queue\Task;

class TarantoolQueueAdapter
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var ConsumerHandler|null
     */
    private $consumerHandler;

    /**
     * @var QueuePool
     */
    private $queuePool;

    /**
     * @var bool
     */
    private $tarantoolQueueRuntimeMode;

    /**
     * @param QueuePool $queuePool
     * @param Client $client
     * @param ConsumerHandler $consumerHandler
     * @param bool $tarantoolQueueRuntimeMode
     */
    public function __construct(
        QueuePool $queuePool,
        Client $client,
        ConsumerHandler $consumerHandler,
        bool $tarantoolQueueRuntimeMode
    ) {
        $this->queuePool = $queuePool;
        $this->client = $client;
        $this->consumerHandler = $consumerHandler;
        $this->tarantoolQueueRuntimeMode = $tarantoolQueueRuntimeMode;
    }

    public function beginPool(): void
    {
        $this->queuePool->incrementPoolNestingLevel();
    }

    /**
     * @throws Exception
     */
    public function commitPool(): void
    {
        $isCommitAvailable = $this->queuePool->isCommitAvailable();
        $this->queuePool->decrementPoolNestingLevel();

        if ($isCommitAvailable) {
            $this->flushPool();
        }
    }

    public function rollbackPool(): void
    {
        $this->queuePool->rollbackPool();
    }

    /**
     * @return array
     */
    public function takeQueueTaskList(): array
    {
        return $this->queuePool->takeQueueTaskList();
    }

    /**
     * @param string $queueName
     *
     * @return Queue
     */
    public function getQueue(string $queueName): Queue
    {
        return new Queue($this->client, $queueName);
    }

    /**
     * @param string $queueName
     * @param array $data
     * @param array $options
     *
     * @throws Exception
     */
    public function put(string $queueName, array $data, array $options = []): void
    {
        $this->beginPool();
        $this->queuePool->add($queueName, $data, $options);
        $this->commitPool();
    }

    /**
     * @param string $queueName
     * @param int|null $timeout
     *
     * @return Task|null
     */
    public function take(string $queueName, ?int $timeout = null): ?Task
    {
        $queue = $this->getQueue($queueName);

        return $queue->take($timeout);
    }

    /**
     * @param string $queueName
     * @param int $taskId
     */
    public function ack(string $queueName, int $taskId): void
    {
        $queue = $this->getQueue($queueName);
        $queue->ack($taskId);
    }

    /**
     * @param string $queueName
     * @param int $taskId
     * @param array|null $options
     */
    public function release(string $queueName, int $taskId, ?array $options = null): void
    {
        $queue = $this->getQueue($queueName);
        $queue->release($taskId, $options);
    }

    /**
     * @param string $queueName
     * @param int $taskId
     */
    public function peek(string $queueName, int $taskId): void
    {
        $queue = $this->getQueue($queueName);
        $queue->peek($taskId);
    }

    /**
     * @param string $queueName
     * @param int $taskId
     */
    public function bury(string $queueName, int $taskId): void
    {
        $queue = $this->getQueue($queueName);
        $queue->bury($taskId);
    }

    /**
     * @param string $queueName
     * @param int $count
     */
    public function kick(string $queueName, int $count): void
    {
        $queue = $this->getQueue($queueName);
        $queue->kick($count);
    }

    /**
     * @param string $queueName
     * @param int $taskId
     */
    public function delete(string $queueName, int $taskId): void
    {
        $queue = $this->getQueue($queueName);
        $queue->delete($taskId);
    }

    /**
     * @return array
     */
    public function statistics(): array
    {
        return $this->client->call('queue.statistics');
    }

    /**
     * @param string $queueName
     * @param string $queueType
     *
     * @return array
     *
     * @throws QueueTarantoolException
     */
    public function initQueue(string $queueName, string $queueType): array
    {
        if (empty($queueName) || empty($queueType)) {
            throw new QueueTarantoolException('Empty queue name or queue type');
        }

        return $this->client->call('init_tube', $queueName, $queueType);
    }

    /**
     * @throws Exception
     */
    private function flushPool(): void
    {
        $queueTaskList = $this->queuePool->takeQueueTaskList();

        if ($this->tarantoolQueueRuntimeMode === true) {
            foreach ($queueTaskList as $queueTask) {
                $task = Task::createFromTuple([random_int(1, 1000000), States::TAKEN, $queueTask->getData()]);

                $consumer = $this->consumerHandler->getConsumer($queueTask->getQueueName());
                $consumer->process([$task]);
            }

            return;
        }

        $queueDataList = [];

        foreach ($queueTaskList as $queueTask) {
            $data['data'] = $queueTask->getData();
            $data['options'] = $queueTask->getOptions();

            $queueDataList[$queueTask->getQueueName()][] = $data;
        }

        foreach ($queueDataList as $queueName => $dataList) {
            $queue = $this->getQueue($queueName);
            $queue->putList($dataList);
        }
    }
}
