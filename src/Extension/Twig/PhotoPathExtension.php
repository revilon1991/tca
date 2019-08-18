<?php

declare(strict_types=1);

namespace App\Extension\Twig;

use App\Component\PathGenerator\PathGenerator;
use App\Entity\Photo;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class PhotoPathExtension extends AbstractExtension
{
    private const PHOTO_PUBLIC_PATH = '/photo';

    /**
     * @var PathGenerator
     */
    private $pathGenerator;

    /**
     * @required
     *
     * @param PathGenerator $pathGenerator
     */
    public function dependencyInjection(
        PathGenerator $pathGenerator
    ): void {
        $this->pathGenerator = $pathGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('photoPath', [$this, 'photoPath']),
        ];
    }

    /**
     * @param Photo $photo
     *
     * @return string
     */
    public function photoPath(Photo $photo): string
    {
        return sprintf(
            '%s/%s/%s.%s',
            self::PHOTO_PUBLIC_PATH,
            $this->pathGenerator->generateBigIntPath($photo->getId()),
            $photo->getId(),
            $photo->getExtension()
        );
    }
}
