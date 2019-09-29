<?php

declare(strict_types=1);

namespace App\Component\Tarantool\Consumer;

use App\Component\Tarantool\Enum\QueueTypeEnum;
use Tarantool\Queue\Task;

abstract class AbstractConsumer
{
    public const DEFAULT_BATCH_SIZE = 1;
    public const DEFAULT_QUEUE_TYPE = QueueTypeEnum::FIFO;
    public const DEFAULT_SLEEP_DURATION = 1.0;

    /**
     * @var bool
     */
    private $propagationStopped = false;

    /**
     * @return int
     */
    public function getBatchSize(): int
    {
        return self::DEFAULT_BATCH_SIZE;
    }

    /**
     * @return string
     */
    public function getQueueType(): string
    {
        return self::DEFAULT_QUEUE_TYPE;
    }

    /**
     * @return float
     */
    public function getSleepDuration(): float
    {
        return self::DEFAULT_SLEEP_DURATION;
    }

    /**
     * @return bool
     */
    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    protected function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }

    /**
     * @return string
     */
    abstract public function getQueueName(): string;

    /**
     * @param Task[] $taskList
     */
    abstract public function process(array $taskList): void;
}
