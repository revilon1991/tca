<?php

declare(strict_types=1);

namespace App\Command;

use App\Component\Tensorflow\Dto\TensorflowPoetsPredictDto;
use App\Component\Tensorflow\Exception\TensorflowException;
use App\Component\Tensorflow\Service\TensorflowService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;

class PredictTensorflowCommand extends Command
{
    protected static $defaultName = 'tf:predict';

    /**
     * @var TensorflowService
     */
    private $tensorflowService;

    /**
     * @required
     *
     * @param TensorflowService $tensorflowService
     */
    public function dependencyInjection(TensorflowService $tensorflowService): void
    {
        $this->tensorflowService = $tensorflowService;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->addArgument('classification_model', InputArgument::REQUIRED)
            ->addArgument('pathname_image_entry', InputArgument::IS_ARRAY|InputArgument::REQUIRED)
        ;
    }

    /**
     * {@inheritdoc}
     *
     * @throws ExceptionInterface
     * @throws TensorflowException
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $classificationModel = $input->getArgument('classification_model');
        $imagePathnameList = $input->getArgument('pathname_image_entry');

        $tensorflowPoetsImageDto = new TensorflowPoetsPredictDto([
            'classificationModel' => $classificationModel,
            'image' => $imagePathnameList,
        ]);

        $labelList = $this->tensorflowService->predict($tensorflowPoetsImageDto);

        $output->writeln($labelList);
    }
}
