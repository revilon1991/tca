<?php

declare(strict_types=1);

namespace App\UseCase\SendTelegramMessage;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

class SendTelegramMessageHandler
{
    private const TELEGRAM_API_SEND_MESSAGE_PATTERN = 'https://api.telegram.org/bot%s/sendMessage?chat_id=%s&text=%s';

    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $proxyUrl;

    /**
     * @var string
     */
    private $botToken;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Client $client
     * @param string $proxyUrl
     * @param string $botToken
     * @param LoggerInterface $logger
     */
    public function __construct(
        Client $client,
        string $proxyUrl,
        string $botToken,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->proxyUrl = $proxyUrl;
        $this->botToken = $botToken;
        $this->logger = $logger;
    }

    /**
     * @param string $subscriberExternalId
     * @param string $text
     */
    public function handle(string $subscriberExternalId, string $text): void
    {
        $url = sprintf(
            self::TELEGRAM_API_SEND_MESSAGE_PATTERN,
            $this->botToken,
            $subscriberExternalId,
            $text
        );

        try {
            $this->client->request('GET', $url, [
                'proxy' => $this->proxyUrl,
            ]);
        } catch (GuzzleException $exception) {
            $message = "Can not send telegram message for $subscriberExternalId: {$exception->getMessage()}";

            $this->logger->error($message);
        }
    }
}
