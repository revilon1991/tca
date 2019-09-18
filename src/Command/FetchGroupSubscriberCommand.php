<?php

declare(strict_types=1);

namespace App\Command;

use App\Component\Manager\Executer\RowManager;
use App\UseCase\FetchGroupSubscriber\FetchGroupSubscriberHandler;
use Doctrine\DBAL\DBALException;
use MyBuilder\Bundle\CronosBundle\Annotation\Cron;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @Cron(minute="0", hour="0", noLogs=true, server="main")
 */
class FetchGroupSubscriberCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'fetch:group:subscribers';

    /**
     * @var RowManager
     */
    private $manager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var FetchGroupSubscriberHandler
     */
    private $handler;

    /**
     * @required
     *
     * @param FetchGroupSubscriberHandler $handler
     * @param RowManager $manager
     * @param LoggerInterface $logger
     */
    public function dependencyInjection(
        FetchGroupSubscriberHandler $handler,
        RowManager $manager,
        LoggerInterface $logger
    ): void {
        $this->handler = $handler;
        $this->manager = $manager;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->manager->beginTransaction();

        try {
            $this->handler->handle();

            $this->manager->commit();
        } catch (DBALException $exception) {
            $this->manager->rollBack();

            $message = "Database error of command '{$this->getName()}' Message: {$exception->getMessage()}";

            $this->logger->error($message);
        }
    }
}
