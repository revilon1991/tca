<?php

declare(strict_types=1);

namespace App\Component\Tarantool\Handler;

use App\Component\Tarantool\Consumer\AbstractConsumer;
use App\Component\Tarantool\Exception\ConsumerNotFoundException;

class ConsumerHandler
{
    /**
     * @var AbstractConsumer[]
     */
    private $consumerList = [];

    /**
     * @param AbstractConsumer[] $consumerList
     */
    public function __construct(iterable $consumerList)
    {
        foreach ($consumerList as $consumer) {
            $this->consumerList[$consumer->getQueueName()] = $consumer;
        }
    }

    /**
     * @param string $name
     *
     * @return AbstractConsumer
     */
    public function getConsumer(string $name): AbstractConsumer
    {
        if (isset($this->consumerList[$name])) {
            return $this->consumerList[$name];
        }

        throw new ConsumerNotFoundException("Consumer for name $name was not found");
    }

    /**
     * @return AbstractConsumer[]
     */
    public function getConsumerList(): array
    {
        return $this->consumerList;
    }
}
