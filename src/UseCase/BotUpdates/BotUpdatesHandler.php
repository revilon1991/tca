<?php

declare(strict_types=1);

namespace App\UseCase\BotUpdates;

use App\Component\Tarantool\Adapter\TarantoolQueueAdapter;
use App\Consumer\GroupFetchConsumer;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class BotUpdatesHandler
{
    private const TELEGRAM_API_UPDATES_PATTERN = 'https://api.telegram.org/bot%s/getUpdates?offset=%s';
    private const TELEGRAM_API_SEND_MESSAGE_PATTERN = 'https://api.telegram.org/bot%s/sendMessage?chat_id=%s&text=%s';
    private const TELEGRAM_API_ADMIN_LIST_PATTERN = 'https://api.telegram.org/bot%s/getChatAdministrators?chat_id=%s';
    private const COMMAND_REGISTRATION = '/go';

    /**
     * @var BotUpdatesManager
     */
    private $manager;

    /**
     * @var string
     */
    private $proxyUrl;

    /**
     * @var string
     */
    private $botToken;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var TarantoolQueueAdapter
     */
    private $tarantoolQueueAdapter;

    /**
     * @param BotUpdatesManager $manager
     * @param string $proxyUrl
     * @param string $botToken
     * @param Client $client
     * @param TarantoolQueueAdapter $tarantoolQueueAdapter
     */
    public function __construct(
        BotUpdatesManager $manager,
        string $proxyUrl,
        string $botToken,
        Client $client,
        TarantoolQueueAdapter $tarantoolQueueAdapter
    ) {
        $this->manager = $manager;
        $this->proxyUrl = $proxyUrl;
        $this->botToken = $botToken;
        $this->client = $client;
        $this->tarantoolQueueAdapter = $tarantoolQueueAdapter;
    }

    /**
     * @throws GuzzleException
     * @throws Exception
     */
    public function handle(): void
    {
        $offset = 0;

        while (true) {
            $url = sprintf(self::TELEGRAM_API_UPDATES_PATTERN, $this->botToken, $offset);

            $response = $this->client->request('GET', $url, [
                'proxy' => $this->proxyUrl,
            ]);

            $response = json_decode($response->getBody()->getContents(), true);

            $messageList = $response['result'] ?? [];

            foreach ($messageList as $message) {
                $offset = $message['update_id'] + 1;
                $textMessage = $message['message']['text'] ?? $message['edited_message']['text'];
                $userId = $message['message']['from']['id'] ?? $message['edited_message']['from']['id'];

                $needle = self::COMMAND_REGISTRATION . ' @';
                if (strpos($textMessage, $needle) === false) {
                    $text = sprintf('Send me a "%s @group" and I will register you', self::COMMAND_REGISTRATION);

                    $this->sendMessage($userId, $text);

                    continue;
                }

                $groupUsername = substr($textMessage, strpos($textMessage, '@'));

                $url = sprintf(self::TELEGRAM_API_ADMIN_LIST_PATTERN, $this->botToken, $groupUsername);

                $textError = "Add the bot as an administrator to channel/group '$groupUsername'. ";
                $textError .= 'Registration is allowed only to channel/group administrators.';

                try {
                    $response = $this->client->request('GET', $url, [
                        'proxy' => $this->proxyUrl,
                    ]);

                    $response = json_decode($response->getBody()->getContents(), true);

                    $isAdminFound = false;
                    foreach ($response['result'] as $adminSubscriber) {
                        if ($adminSubscriber['user']['id'] === $userId) {
                            $botHash = sha1($userId . random_bytes(10) . $groupUsername);

                            $text = "Get hash for complete registration <$botHash>";
                            $this->sendMessage($userId, $text);

                            $isAdminFound = true;

                            $subscriberId = $this->manager->getSubscriberId((string)$userId);
                            $this->manager->saveUser((string)$userId, $botHash, $subscriberId);

                            $this->tarantoolQueueAdapter->put(GroupFetchConsumer::QUEUE_FETCH_GROUP, [
                                'username' => $groupUsername,
                            ]);

                            break;
                        }
                    }

                    if ($isAdminFound === false) {
                        $this->sendMessage($userId, $textError);
                    }
                } catch (GuzzleException $exception) {
                    $this->sendMessage($userId, $textError);
                }
            }

            usleep(500000);
        }
    }

    /**
     * @param int $userId
     * @param string $text
     *
     * @throws GuzzleException
     */
    public function sendMessage(int $userId, string $text): void
    {
        $url = sprintf(self::TELEGRAM_API_SEND_MESSAGE_PATTERN, $this->botToken, $userId, $text);

        $this->client->request('GET', $url, [
            'proxy' => $this->proxyUrl,
        ]);
    }
}
