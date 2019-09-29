<?php

declare(strict_types=1);

namespace App\Command;

use App\UseCase\FetchGroup\FetchGroupHandler;
use Doctrine\DBAL\DBALException;
use MyBuilder\Bundle\CronosBundle\Annotation\Cron;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;

/**
 * @Cron(minute="0", hour="0", noLogs=true, server="main")
 */
class FetchGroupCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'fetch:group';

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
     *
     * @throws DBALException
     * @throws ExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->handler->handle();
    }
}
