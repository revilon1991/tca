<?php

declare(strict_types=1);

namespace App\UseCase\FetchSubscriberPhoto;

use App\Component\PathGenerator\PathGenerator;
use App\Dto\InputUserDto;
use App\Component\Telegram\Provider\TelegramProvider;
use Doctrine\DBAL\DBALException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\MimeTypesInterface;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;

class FetchSubscriberPhotoHandler
{
    /**
     * @var FetchSubscriberPhotoManager
     */
    private $manager;

    /**
     * @var TelegramProvider
     */
    private $telegramProvider;

    /**
     * @var MimeTypesInterface
     */
    private $mimeTypes;

    /**
     * @var string
     */
    private $photoPublicDir;

    /**
     * @var PathGenerator
     */
    private $pathGenerator;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param FetchSubscriberPhotoManager $manager
     * @param TelegramProvider $telegramProvider
     * @param MimeTypesInterface $mimeTypes
     * @param string $photoPublicDir
     * @param PathGenerator $pathGenerator
     * @param LoggerInterface $logger
     */
    public function __construct(
        FetchSubscriberPhotoManager $manager,
        TelegramProvider $telegramProvider,
        MimeTypesInterface $mimeTypes,
        string $photoPublicDir,
        PathGenerator $pathGenerator,
        LoggerInterface $logger
    ) {
        $this->manager = $manager;
        $this->telegramProvider = $telegramProvider;
        $this->mimeTypes = $mimeTypes;
        $this->photoPublicDir = $photoPublicDir;
        $this->pathGenerator = $pathGenerator;
        $this->logger = $logger;
    }

    /**
     * @throws DBALException
     * @throws ExceptionInterface
     */
    public function handle(): void
    {
        $paramsList = [];
        $saveCount = 0;

        $subscriberCount = $this->manager->getSubscriberCount();

        foreach ($this->manager->getSubscriberList() as $row) {
            $inputUserDto = new InputUserDto([
                'userId' => $row['externalId'],
                'accessHash' => $row['externalHash'],
            ]);

            $subscriberPhotoMetaList = $this->telegramProvider->getSubscriberPhotoMetaList($inputUserDto);

            $freshPhotoMetaList = $this->getFreshPhotoMetaList(
                $subscriberPhotoMetaList,
                (string)$row['photoUniqueKeys']
            );

            foreach ($freshPhotoMetaList as $photoMeta) {
                $photoId = $this->manager->generateUniqueId();

                $path = "{$this->photoPublicDir}/{$this->pathGenerator->generateIntPath($photoId)}";

                !file_exists($path) ? mkdir($path, 0777, true) : null;

                $pathname = "$path/$photoId";
                $this->telegramProvider->savePhoto($photoMeta, $pathname);

                $extensions = $this->mimeTypes->getExtensions(mime_content_type($pathname));
                rename($pathname, "$pathname.$extensions[0]");

                $paramsList[] = [
                    'id' => $photoId,
                    'subscriberId' => $row['id'],
                    'externalId' => (string)$photoMeta['id'],
                    'externalHash' => (string)$photoMeta['access_hash'],
                    'extension' => $extensions[0],
                ];
            }

            $saveCount++;
            $this->logger->debug("Subscriber save photo complete for $saveCount/$subscriberCount");
        }

        $this->manager->addPhotoList($paramsList);
    }

    /**
     * @param array $subscriberPhotoMetaList
     * @param string $photoUniqueKeys
     *
     * @return array
     */
    private function getFreshPhotoMetaList(array $subscriberPhotoMetaList, string $photoUniqueKeys): array
    {
        $freshPhotoMetaList = [];

        foreach ($subscriberPhotoMetaList as $subscriberPhotoMeta) {
            $position = strpos($photoUniqueKeys, "$subscriberPhotoMeta[id]$subscriberPhotoMeta[access_hash]");

            if ($position !== false) {
                continue;
            }

            $freshPhotoMetaList[] = $subscriberPhotoMeta;
        }

        return $freshPhotoMetaList;
    }
}
