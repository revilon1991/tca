<?php

declare(strict_types=1);

namespace App\Consumer;

use App\Component\Tarantool\Consumer\AbstractConsumer;
use App\UseCase\SendMail\SendMailHandler;
use Tarantool\Queue\Task;

class SendMailConsumer extends AbstractConsumer
{
    public const QUEUE_SEND_MAIL = 'queue_send_mail';

    /**
     * @var SendMailHandler
     */
    private $handler;

    /**
     * @required
     *
     * @param SendMailHandler $handler
     */
    public function dependencyInjection(SendMailHandler $handler): void
    {
        $this->handler = $handler;
    }

    /**
     * @param Task[] $taskList
     */
    public function process(array $taskList): void
    {
        foreach ($taskList as $task) {
            $email = $task->getData()['email'];
            $subject = $task->getData()['subject'];
            $text = $task->getData()['text'];

            $this->handler->handle($email, $subject, $text);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getQueueName(): string
    {
        return self::QUEUE_SEND_MAIL;
    }
}
