<?php

declare(strict_types=1);

namespace App\Command;

use App\UseCase\FetchSubscriberPhoto\FetchSubscriberPhotoHandler;
use Doctrine\DBAL\DBALException;
use MyBuilder\Bundle\CronosBundle\Annotation\Cron;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;

/**
 * @Cron(minute="0", hour="1", noLogs=true, server="main")
 */
class FetchSubscriberPhotoCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'fetch:subscribers:photo';

    /**
     * @var FetchSubscriberPhotoHandler
     */
    private $handler;

    /**
     * @required
     *
     * @param FetchSubscriberPhotoHandler $handler
     */
    public function dependencyInjection(FetchSubscriberPhotoHandler $handler): void
    {
        $this->handler = $handler;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @throws ExceptionInterface
     * @throws DBALException
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->handler->handle();
    }
}
