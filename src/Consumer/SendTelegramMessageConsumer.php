<?php

declare(strict_types=1);

namespace App\Consumer;

use App\Component\Tarantool\Consumer\AbstractConsumer;
use App\UseCase\SendTelegramMessage\SendTelegramMessageHandler;
use Tarantool\Queue\Task;

class SendTelegramMessageConsumer extends AbstractConsumer
{
    public const QUEUE_SEND_TELEGRAM_MESSAGE = 'queue_send_telegram_message';

    /**
     * @var SendTelegramMessageHandler
     */
    private $handler;

    /**
     * @param SendTelegramMessageHandler $handler
     */
    public function __construct(SendTelegramMessageHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * @return string
     */
    public function getQueueName(): string
    {
        return self::QUEUE_SEND_TELEGRAM_MESSAGE;
    }

    /**
     * @param Task[] $taskList
     */
    public function process(array $taskList): void
    {
        foreach ($taskList as $task) {
            dump($task);
            $subscriberExternalId = $task->getData()['subscriber_external_id'];
            $text = $task->getData()['text'];

            $this->handler->handle($subscriberExternalId, $text);
        }
    }
}
