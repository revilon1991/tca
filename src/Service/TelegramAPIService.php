<?php

declare(strict_types=1);

namespace App\Service;

use danog\MadelineProto\API;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Stream\Proxy\SocksProxy;
use Psr\Log\LoggerInterface;

class TelegramAPIService
{
    private const SESSION_BOT_FILENAME = 'session.bot.madeline';
    private const SESSION_USER_FILENAME = 'session.user.madeline';

    /**
     * @var API
     */
    private $madelineProto;

    /**
     * @var string
     */
    private $projectDir;

    /**
     * @var string
     */
    private $appId;

    /**
     * @var string
     */
    private $appHash;

    /**
     * @var string
     */
    private $botToken;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string|null
     */
    private $proxyHost;

    /**
     * @var int|null
     */
    private $proxyPort;

    /**
     * @var string|null
     */
    private $proxyUsername;

    /**
     * @var string|null
     */
    private $proxyPassword;

    /**
     * @param string $projectDir
     * @param string $appId
     * @param string $appHash
     * @param string $botToken
     * @param LoggerInterface $logger
     * @param string $proxyHost
     * @param int $proxyPort
     * @param string $proxyUsername
     * @param string $proxyPassword
     */
    public function __construct(
        string $projectDir,
        string $appId,
        string $appHash,
        string $botToken,
        LoggerInterface $logger,
        ?string $proxyHost,
        ?int $proxyPort,
        ?string $proxyUsername,
        ?string $proxyPassword
    ) {
        $this->projectDir = $projectDir;
        $this->appId = $appId;
        $this->appHash = $appHash;
        $this->botToken = $botToken;
        $this->logger = $logger;
        $this->proxyHost = $proxyHost;
        $this->proxyPort = $proxyPort;
        $this->proxyUsername = $proxyUsername;
        $this->proxyPassword = $proxyPassword;
    }

    /**
     * @param string $channelId
     *
     * @return array
     */
    public function getChannelUserList(string $channelId): array
    {
        exec(sprintf('rm -f %s/var/%s', $this->projectDir, self::SESSION_BOT_FILENAME));

        $this->initialization();

        $pwrChannel = $this->madelineProto->get_pwr_chat($channelId);

        return $pwrChannel['participants'];
    }

    /**
     * @param array $photoMeta
     * @param string $pathname
     */
    public function saveChannelPhoto(array $photoMeta, string $pathname): void
    {
        $this->initialization();

        $this->madelineProto->download_to_file($photoMeta, $pathname);
    }

    /**
     * @param array $photoMeta
     *
     * @return array
     */
    public function getChannelPhotoInfo(array $photoMeta): array
    {
        return $this->madelineProto->get_download_info($photoMeta);
    }

    /**
     * @param string $channelId
     *
     * @return array
     */
    public function getChannelInfo(string $channelId): array
    {
        $this->initialization();

        return $this->madelineProto->get_full_info($channelId);
    }

    /**
     * @param bool $isBot
     */
    private function initialization(bool $isBot = true): void
    {
        if ($this->madelineProto) {
            return;
        }

        $settings['connection_settings']['all']['proxy'] = SocksProxy::getName();

        $isProxy =
            (bool)$this->proxyHost &&
            (bool)$this->proxyPort &&
            (bool)$this->proxyUsername &&
            (bool)$this->proxyPassword
        ;

        if ($isProxy) {
            $settings['connection_settings']['all']['proxy_extra'] = [
                'address'  => $this->proxyHost,
                'port'     =>  $this->proxyPort,
                'username' => $this->proxyUsername,
                'password' => $this->proxyPassword,
            ];
        }

        $settings['logger'] = [
            'logger' => Logger::CALLABLE_LOGGER,
            'logger_level' => Logger::FATAL_ERROR,
            'logger_param' => $this->makeLoggerCallable(),
        ];
        $settings['app_info'] = [
            'api_id' => $this->appId,
            'api_hash' => $this->appHash,
        ];
        $settings['peer'] = [
            'cache_all_peers_on_startup' => true,
            'full_fetch' => true,
        ];
        $settings['authorization'] = [
            'default_temp_auth_key_expires_in' => 315576000,
            'full_fetch' => true,
        ];



        if ($isBot) {
            $sessionPathname = sprintf('%s/var/%s', $this->projectDir, self::SESSION_BOT_FILENAME);

            $this->madelineProto = new API($sessionPathname, $settings);

            $this->madelineProto->bot_login($this->botToken);
        } else {
            $sessionPathname = sprintf('%s/var/%s', $this->projectDir, self::SESSION_USER_FILENAME);

            $this->madelineProto = new API($sessionPathname, $settings);
        }

        $this->madelineProto->start();
    }

    /**
     * @return callable
     */
    private function makeLoggerCallable(): callable
    {
        return function ($message, int $level) {
            $message = !is_array($message) ? [$message] : $message;

            switch ($level) {
                case Logger::ULTRA_VERBOSE:
                case Logger::VERBOSE:
                    $this->logger->debug('MadelineProto log', $message);
                    break;

                case Logger::NOTICE:
                    $this->logger->notice('MadelineProto log', $message);
                    break;

                case Logger::WARNING:
                    $this->logger->warning('MadelineProto log', $message);
                    break;

                case Logger::ERROR:
                    $this->logger->error('MadelineProto log', $message);
                    break;

                case Logger::FATAL_ERROR:
                    $this->logger->critical('MadelineProto log', $message);
                    break;
            }
        };
    }
}
