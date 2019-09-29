<?php

declare(strict_types=1);

namespace App\Component\Tarantool\Queue;

use App\Component\Tarantool\Exception\QueuePoolNestingLevelException;
use App\Component\Tarantool\Exception\QueuePoolRollbackException;

class QueuePool
{
    /**
     * @var QueueTask[]
     */
    private $queueTaskList = [];

    /**
     * @var bool
     */
    private $isRollback = false;

    /**
     * @var int
     */
    private $poolNestingLevel = 0;

    /**
     * @return int
     */
    public function getPoolNestingLevel(): int
    {
        return $this->poolNestingLevel;
    }

    /**
     * @return QueueTask[]
     */
    public function takeQueueTaskList(): array
    {
        $taskList = $this->queueTaskList;
        $this->clean();

        return $taskList;
    }

    /**
     * @param string $queueName
     * @param array $data
     * @param array $options
     */
    public function add(string $queueName, array $data, array $options = []): void
    {
        $this->queueTaskList[] = new QueueTask($queueName, $data, $options);
    }

    public function clean(): void
    {
        $this->queueTaskList = [];
    }

    public function resetPoolNestingLevel(): void
    {
        $this->poolNestingLevel = 0;
    }

    public function incrementPoolNestingLevel(): void
    {
        ++$this->poolNestingLevel;
    }

    public function decrementPoolNestingLevel(): void
    {
        --$this->poolNestingLevel;
    }

    /**
     * @param bool $value
     */
    public function setRollback(bool $value): void
    {
        $this->isRollback = $value;
    }

    public function rollbackPool(): void
    {
        $isRollbackAvailable = $this->isRollbackAvailable();

        if ($isRollbackAvailable) {
            $this->resetPoolNestingLevel();
            $this->clean();
            $this->setRollback(false);
        } else {
            $this->decrementPoolNestingLevel();
            $this->setRollback(true);
        }
    }

    protected function checkNestingLevel(): void
    {
        if ($this->poolNestingLevel === 0) {
            throw new QueuePoolNestingLevelException('Pool nesting level equality zero');
        }
    }

    protected function checkRollback(): void
    {
        if ($this->isRollback === true) {
            throw new QueuePoolRollbackException('There was a rollback');
        }
    }

    /**
     * @return bool
     */
    public function isCommitAvailable(): bool
    {
        $this->checkNestingLevel();
        $this->checkRollback();

        return $this->poolNestingLevel === 1;
    }

    /**
     * @return bool
     */
    public function isRollbackAvailable(): bool
    {
        $this->checkNestingLevel();

        return $this->poolNestingLevel === 1;
    }
}
