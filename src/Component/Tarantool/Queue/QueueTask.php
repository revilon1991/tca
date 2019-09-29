<?php

declare(strict_types=1);

namespace App\Component\Tarantool\Queue;

class QueueTask
{
    /**
     * @var string
     */
    private $queueName;

    /**
     * @var array
     */
    private $data;

    /**
     * @var array
     */
    private $options;

    /**
     * @param string $queueName
     * @param array $data
     * @param array $options
     */
    public function __construct(string $queueName, array $data, array $options = [])
    {
        $this->queueName = $queueName;
        $this->data = $data;
        $this->options = $options;
    }

    /**
     * @return string
     */
    public function getQueueName(): string
    {
        return $this->queueName;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
