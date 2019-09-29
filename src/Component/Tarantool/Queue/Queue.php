<?php

declare(strict_types=1);

namespace App\Component\Tarantool\Queue;

use App\Component\Tarantool\Exception\QueueTarantoolException;
use Tarantool\Client\Client;
use Tarantool\Queue\Task;
use function explode;
use function is_array;
use function sprintf;

class Queue
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var string
     */
    private $tubeName;

    /**
     * @param Client $client
     * @param string $tubeName
     */
    public function __construct(Client $client, string $tubeName)
    {
        $this->client = $client;
        $this->prefix = sprintf('queue.tube.%s:', $tubeName);
        $this->tubeName = $tubeName;
    }

    /**
     * @param array $dataList
     */
    public function putList(array $dataList): void
    {
        if (empty($dataList)) {
            return;
        }

        $this->client->call($this->prefix . 'put_list', $dataList);
    }

    /**
     * @param array $idList
     */
    public function ackList(array $idList): void
    {
        if (empty($idList)) {
            return;
        }

        $this->client->call($this->prefix . 'ack_list', $idList);
    }

    /**
     * @param int $count
     * @param float|null $timeout
     *
     * @return Task[]
     */
    public function takeList(int $count = 1, ?float $timeout = null): array
    {
        $toupleList = $this->client->call($this->prefix . 'take_list', $count, $timeout)[0];
        $taskList = [];

        foreach ($toupleList as $touple) {
            if (!$touple) {
                continue;
            }

            $task = Task::createFromTuple($touple);
            $taskList[$task->getId()] = $task;
        }

        return $taskList;
    }

    /**
     * @param array $data
     * @param array|null $options
     *
     * @return Task
     */
    public function put(array $data, array $options = []): Task
    {
        return Task::createFromTuple(
            $this->client->call($this->prefix . 'put', $data, $options)[0]
        );
    }

    /**
     * @param float|null $timeout
     *
     * @return Task|null
     */
    public function take(?float $timeout = null): ?Task
    {
        $args = null === $timeout ? [] : [$timeout];
        $touple = $this->client->call($this->prefix . 'take', ...$args)[0];

        return Task::createFromTuple($touple);
    }

    /**
     * @param int $taskId
     *
     * @return Task
     */
    public function ack(int $taskId): Task
    {
        $result = $this->client->call($this->prefix . 'ack', $taskId);

        return Task::createFromTuple($result[0]);
    }

    /**
     * @param int $taskId
     * @param array $options
     *
     * @return Task
     */
    public function release(int $taskId, array $options = []): Task
    {
        $touple = $this->client->call($this->prefix . 'release', $taskId, $options)[0];

        return Task::createFromTuple($touple);
    }

    /**
     * @param int $taskId
     *
     * @return Task
     */
    public function peek(int $taskId): Task
    {
        $touple = $this->client->call($this->prefix . 'peek', $taskId)[0];

        return Task::createFromTuple($touple);
    }

    /**
     * @param int $taskId
     *
     * @return Task
     */
    public function bury(int $taskId): Task
    {
        $touple = $this->client->call($this->prefix . 'bury', $taskId)[0];

        return Task::createFromTuple($touple);
    }

    /**
     * @param int $count
     *
     * @return int
     */
    public function kick(int $count): int
    {
        $touple = $this->client->call($this->prefix . 'kick', $count)[0];

        return $touple[0];
    }

    /**
     * @param int $taskId
     *
     * @return Task
     */
    public function delete(int $taskId): Task
    {
        $touple = $this->client->call($this->prefix . 'delete', $taskId)[0];

        return Task::createFromTuple($touple);
    }

    public function truncate(): void
    {
        $this->client->call($this->prefix . 'truncate');
    }

    /**
     * @param string|null $path
     *
     * @return array
     *
     * @throws QueueTarantoolException
     */
    public function stats(?string $path = null): array
    {
        $touple = $this->client->call('queue.stats', $this->tubeName)[0];
        $stats = $touple[0];

        if (null === $path) {
            return is_array($stats) ? $stats : [$stats];
        }

        foreach (explode('.', $path) as $key) {
            if (!isset($stats[$key])) {
                throw new QueueTarantoolException("Invalid path $path");
            }

            $stats = $stats[$key];
        }

        return is_array($stats) ? $stats : [$stats];
    }
}
