<?php

declare(strict_types=1);

namespace App\Command;

use App\UseCase\FetchGroup\FetchGroupHandler;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;

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
     */
    protected function configure(): void
    {
        $this->addArgument('group_username', InputArgument::REQUIRED, 'telegram channel/chat username');
    }

    /**
     * {@inheritdoc}
     *
     * @throws DBALException
     * @throws ExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $externalGroupId = $input->getArgument('group_username');

        $this->handler->handle($externalGroupId);
    }
}
