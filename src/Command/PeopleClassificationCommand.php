<?php

declare(strict_types=1);

namespace App\Command;

use App\Component\Manager\Executer\RowManager;
use App\UseCase\PeopleClassification\PeopleClassificationHandler;
use Exception;
use MyBuilder\Bundle\CronosBundle\Annotation\Cron;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;

/**
 * @Cron(minute="0", hour="2", noLogs=true, server="main")
 */
class PeopleClassificationCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'classification:people';

    /**
     * @var PeopleClassificationHandler
     */
    private $handler;

    /**
     * @var RowManager
     */
    private $manager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @required
     *
     * @param PeopleClassificationHandler $handler
     * @param RowManager $manager
     * @param LoggerInterface $logger
     */
    public function dependencyInjection(
        PeopleClassificationHandler $handler,
        RowManager $manager,
        LoggerInterface $logger
    ): void {
        $this->handler = $handler;
        $this->manager = $manager;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     *
     * @throws ExceptionInterface
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->manager->beginTransaction();

        try {
            $this->handler->handle();

            $this->manager->commit();
        } catch (Exception $exception) {
            $this->manager->rollBack();

            $this->logger->error("Rollback while run people classification: {$exception->getMessage()}");

            throw $exception;
        }
    }
}
