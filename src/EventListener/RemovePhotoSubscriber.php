<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Component\PathGenerator\PathGenerator;
use App\Entity\Photo;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;

class RemovePhotoSubscriber implements EventSubscriber
{
    private const FILE_PATTERN = '%s/%s/%s.*';

    /**
     * @var PathGenerator
     */
    private $pathGenerator;

    /**
     * @var string
     */
    private $photoPublicDir;

    /**
     * @param PathGenerator $pathGenerator
     * @param string $photoPublicDir
     */
    public function __construct(
        PathGenerator $pathGenerator,
        string $photoPublicDir
    ) {
        $this->pathGenerator = $pathGenerator;
        $this->photoPublicDir = $photoPublicDir;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents(): array
    {
        return [Events::preRemove];
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function preRemove(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Photo) {
            return;
        }

        $this->removeFile($entity);
    }

    /**
     * @param Photo $photo
     */
    private function removeFile(Photo $photo): void
    {
        $id = $photo->getId();

        $path = $this->pathGenerator->generateBigIntPath($id);

        $filePattern = sprintf(self::FILE_PATTERN, $this->photoPublicDir, $path, $id);

        array_map('unlink', glob($filePattern));
    }
}
