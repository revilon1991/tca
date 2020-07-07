<?php

declare(strict_types=1);

namespace App\Command;

use App\UseCase\BotUpdates\BotUpdatesHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BotUpdatesCommand extends Command
{
    protected static $defaultName = 'bot:updates';

    /**
     * @var BotUpdatesHandler
     */
    private $handler;

    /**
     * @required
     *
     * @param BotUpdatesHandler $handler
     */
    public function dependencyInjection(BotUpdatesHandler $handler): void
    {
        $this->handler = $handler;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->handler->handle();
    }
}
