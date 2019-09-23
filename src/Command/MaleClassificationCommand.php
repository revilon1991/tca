<?php

declare(strict_types=1);

namespace App\Command;

use App\Component\Tensorflow\Exception\TensorflowException;
use App\UseCase\MaleClassification\MaleClassificationHandler;
use Doctrine\DBAL\DBALException;
use MyBuilder\Bundle\CronosBundle\Annotation\Cron;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;

/**
 * @Cron(minute="0", hour="2", noLogs=true, server="main")
 */
class MaleClassificationCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'classification:male';

    /**
     * @var MaleClassificationHandler
     */
    private $handler;

    /**
     * @required
     *
     * @param MaleClassificationHandler $handler
     */
    public function dependencyInjection(
        MaleClassificationHandler $handler
    ): void {
        $this->handler = $handler;
    }

    /**
     * {@inheritdoc}
     *
     * @throws TensorflowException
     * @throws DBALException
     * @throws ExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->handler->handle();
    }
}
