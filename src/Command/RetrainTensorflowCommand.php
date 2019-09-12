<?php

declare(strict_types=1);

namespace App\Command;

use App\Component\Tensorflow\Exception\TensorflowException;
use App\Component\Tensorflow\Provider\TensorflowPoetsProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RetrainTensorflowCommand extends Command
{
    protected static $defaultName = 'tf:poets:retrain';

    /**
     * @var TensorflowPoetsProvider
     */
    private $tensorflowPoetsProvider;

    /**
     * @required
     *
     * @param TensorflowPoetsProvider $tensorflowPoetsProvider
     */
    public function dependencyInjection(
        TensorflowPoetsProvider $tensorflowPoetsProvider
    ): void {
        $this->tensorflowPoetsProvider = $tensorflowPoetsProvider;
    }

    /**
     * {@inheritdoc}
     *
     * @throws TensorflowException
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->tensorflowPoetsProvider->retrain();
    }
}
