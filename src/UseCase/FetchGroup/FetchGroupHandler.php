<?php

declare(strict_types=1);

namespace App\UseCase\FetchGroup;

use App\Component\PathGenerator\PathGenerator;
use App\Component\Telegram\Provider\TelegramProvider;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Mime\MimeTypesInterface;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use function file_exists;
use function mime_content_type;
use function mkdir;
use function rename;
use function sprintf;

class FetchGroupHandler
{
    /**
     * @var TelegramProvider
     */
    private $telegramProvider;

    /**
     * @var FetchGroupManager
     */
    private $manager;

    /**
     * @var PathGenerator
     */
    private $pathGenerator;

    /**
     * @var string
     */
    private $photoPublicDir;

    /**
     * @var MimeTypesInterface
     */
    private $mimeTypes;

    /**
     * @param TelegramProvider $telegramProvider
     * @param FetchGroupManager $manager
     * @param PathGenerator $pathGenerator
     * @param string $photoPublicDir
     * @param MimeTypesInterface $mimeTypes
     */
    public function __construct(
        TelegramProvider $telegramProvider,
        FetchGroupManager $manager,
        PathGenerator $pathGenerator,
        string $photoPublicDir,
        MimeTypesInterface $mimeTypes
    ) {
        $this->telegramProvider = $telegramProvider;
        $this->manager = $manager;
        $this->pathGenerator = $pathGenerator;
        $this->photoPublicDir = $photoPublicDir;
        $this->mimeTypes = $mimeTypes;
    }

    /**
     * @param string $externalGroupId
     *
     * @throws ExceptionInterface
     * @throws DBALException
     */
    public function handle(string $externalGroupId): void
    {
        $channelInfo = $this->telegramProvider->getChannelInfo($externalGroupId);

        $channelId = $this->manager->generateUniqueId();

        $params = [
            'id' => $channelId,
            'external_id' => $channelInfo->getExternalId(),
            'external_hash' => $channelInfo->getExternalHash(),
            'type' => $channelInfo->getType(),
            'title' => $channelInfo->getTitle(),
            'username' => $channelInfo->getUsername(),
            'about' => $channelInfo->getAbout(),
            'subscriber_count' => $channelInfo->getSubscriberCount(),
            'last_update' => $channelInfo->getLastUpdate()->format('Y-m-d H:i:s'),
        ];

        $this->manager->saveChannel($params);

        $photoId = $this->manager->getChannelPhoto(
            $channelInfo->getPhotoExternalId(),
            $channelInfo->getPhotoExternalHash()
        );

        if ($photoId) {
            return;
        }

        $photoId = $this->manager->generateUniqueId();

        $path = sprintf('%s/%s', $this->photoPublicDir, $this->pathGenerator->generateIntPath($photoId));

        !file_exists($path) ? mkdir($path, 0777, true) : null;

        $pathname = "$path/$photoId";
        $this->telegramProvider->savePhoto($channelInfo->getPhotoMeta(), $pathname);

        $extensions = $this->mimeTypes->getExtensions(mime_content_type($pathname));
        rename($pathname, "$pathname.$extensions[0]");

        $params = [
            'id' => $photoId,
            'group_id' => $channelId,
            'external_id' => $channelInfo->getPhotoExternalId(),
            'external_hash' => $channelInfo->getPhotoExternalHash(),
            'extension' => $extensions[0],
        ];

        $this->manager->addPhoto($params);
    }
}
