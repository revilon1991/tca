<?php

declare(strict_types=1);

namespace App\Consumer;

use App\Component\Tarantool\Consumer\AbstractConsumer;
use App\Component\Tarantool\Enum\QueueTypeEnum;
use App\UseCase\FetchGroup\FetchGroupHandler;
use Doctrine\DBAL\DBALException;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Tarantool\Queue\Task;

class GroupFetchConsumer extends AbstractConsumer
{
    public const QUEUE_FETCH_GROUP = 'queue_fetch_group';

    /**
     * @var FetchGroupHandler
     */
    private $handler;

    /**
     * @required
     *
     * @param FetchGroupHandler $handler
     */
    public function dependencyInjection(FetchGroupHandler $handler): void
    {
        $this->handler = $handler;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueueName(): string
    {
        return self::QUEUE_FETCH_GROUP;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueueType(): string
    {
        return QueueTypeEnum::FIFO_SKIP;
    }

    /**
     * @param Task[] $taskList
     *
     * @throws DBALException
     * @throws ExceptionInterface
     */
    public function process(array $taskList): void
    {
        $groupUsernameList = [];

        foreach ($taskList as $task) {
            $groupUsernameList[] = $task->getData()['username'];
        }

        $this->handler->handle($groupUsernameList);
    }
}
