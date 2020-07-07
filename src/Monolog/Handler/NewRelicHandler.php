<?php

declare(strict_types=1);

namespace App\Monolog\Handler;

use Monolog\Handler\MissingExtensionException;
use Monolog\Handler\NewRelicHandler as MonologNewRelicHandler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NewRelicHandler extends MonologNewRelicHandler
{
    /**
     * {@inheritDoc}
     *
     * @throws MissingExtensionException
     */
    protected function write(array $record): void
    {
        $exception = $record['context']['exception'] ?? null;

        if ($exception instanceof NotFoundHttpException && $exception->getStatusCode() === Response::HTTP_NOT_FOUND) {
            return;
        }

        parent::write($record);
    }
}
