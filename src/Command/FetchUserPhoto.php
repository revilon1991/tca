<?php

declare(strict_types=1);

namespace App\Command;

use App\Component\IdGenerator\IdGenerator;
use App\Component\PathGenerator\PathGenerator;
use App\Dto\InputUserDto;
use App\Entity\A;
use App\Entity\Group;
use App\Entity\Photo;
use App\Entity\Subscriber;
use App\Service\TelegramAPIService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use MyBuilder\Bundle\CronosBundle\Annotation\Cron;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;

/**
 * @Cron(minute="0", hour="1", noLogs=true, server="main")
 */
class FetchUserPhoto extends Command
{
    private const PHOTO_KEY_PATTERN = '%s_%s';

    private const BATCH_MAX_COUNT = 10;

    /**
     * @var string
     */
    protected static $defaultName = 'fetch:subscribers:photo';

    /**
     * @var TelegramAPIService
     */
    private $telegramAPIService;

    /**
     * @var string
     */
    private $photoPublicDir;

    /**
     * @var IdGenerator
     */
    private $idGenerator;

    /**
     * @var PathGenerator
     */
    private $pathGenerator;

    /**
     * @var MimeTypes
     */
    private $mimeTypes;

    /**
     * @var EntityManagerInterface
     */
    private $manager;

    /**
     * @var string
     */
    private $defaultGroupId;

    /**
     * @required
     *
     * @param EntityManagerInterface $manager
     * @param TelegramAPIService $telegramAPIService
     * @param IdGenerator $idGenerator
     * @param PathGenerator $pathGenerator
     * @param string $photoPublicDir
     * @param MimeTypes $mimeTypes
     * @param string $defaultGroupId
     */
    public function dependencyInjection(
        EntityManagerInterface $manager,
        TelegramAPIService $telegramAPIService,
        IdGenerator $idGenerator,
        PathGenerator $pathGenerator,
        string $photoPublicDir,
        MimeTypes $mimeTypes,
        string $defaultGroupId
    ): void {
        $this->manager = $manager;
        $this->telegramAPIService = $telegramAPIService;
        $this->idGenerator = $idGenerator;
        $this->pathGenerator = $pathGenerator;
        $this->photoPublicDir = $photoPublicDir;
        $this->mimeTypes = $mimeTypes;
        $this->defaultGroupId = $defaultGroupId;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->addArgument('group', InputArgument::OPTIONAL, 'Telegram group without "@"')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @throws ExceptionInterface
     * @throws ORMException
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $group = $input->getArgument('group') ?? $this->defaultGroupId;

        $group = $this->manager->getRepository(Group::class)->findOneBy([
            'username' => $group,
        ]);

        if (!$group) {
            throw new InvalidArgumentException(sprintf('Channel/Chat with username "%s" not found.', $group));
        }

        $batchCount = 0;

        /** @var Subscriber $subscriber */
        foreach ($group->getSubscriberList() as $subscriber) {
            $inputUserDto = new InputUserDto([
                'userId' => $subscriber->getExternalId(),
                'accessHash' => $subscriber->getExternalHash(),
            ]);

            $responsePhotoMetaList = $this->telegramAPIService->getUserPhotoMetaList($inputUserDto);

            $photoMetaList = [];

            foreach ($responsePhotoMetaList as $responsePhotoMeta) {
                $key = sprintf(
                    self::PHOTO_KEY_PATTERN,
                    $responsePhotoMeta['id'],
                    $responsePhotoMeta['access_hash']
                );

                $photoMetaList[$key] = $responsePhotoMeta;
            }

            $photoMetaList = $this->getFreshPhotoMetaList($subscriber, $photoMetaList);

            $this->persistFreshPhotoList($photoMetaList, $subscriber);

            $batchCount++;

            if (($batchCount % self::BATCH_MAX_COUNT) === 0) {
                $this->manager->flush();
                $this->manager->clear();
            }
        }

        $this->manager->flush();
    }

    /**
     * @param Subscriber $subscriber
     * @param array $photoMetaList
     *
     * @return array
     */
    private function getFreshPhotoMetaList(Subscriber $subscriber, array $photoMetaList): array
    {
        /** @var Photo $photo */
        foreach ($subscriber->getPhotoList() as $photo) {
            $key = sprintf(
                self::PHOTO_KEY_PATTERN,
                $photo->getExternalId(),
                $photo->getExternalHash()
            );

            if (isset($photoMetaList[$key])) {
                unset($photoMetaList[$key]);
            }
        }

        return $photoMetaList;
    }

    /**
     * @param array $photoMetaList
     * @param Subscriber $subscriber
     *
     * @throws ORMException
     */
    private function persistFreshPhotoList(array $photoMetaList, Subscriber $subscriber): void
    {
        if (!$photoMetaList) {
            return;
        }

        /** @var Subscriber $subscriberReference */
        $subscriberReference = $this->manager->getReference(Subscriber::class, $subscriber->getId());

        foreach ($photoMetaList as $photoMeta) {
            $photoExternalId = (string)$photoMeta['id'];
            $photoExternalHash = (string)$photoMeta['access_hash'];

            $id = $this->idGenerator->generateUniqueId();

            $path = sprintf(
                '%s/%s',
                $this->photoPublicDir,
                $this->pathGenerator->generateBigIntPath($id)
            );

            !file_exists($path) ? mkdir($path, 0777, true) : null;

            $pathname = sprintf('%s/%s', $path, $id);
            $this->telegramAPIService->savePhoto($photoMeta, $pathname);

            $extensions = $this->mimeTypes->getExtensions(mime_content_type($pathname));

            rename($pathname, sprintf('%s.%s', $pathname, $extensions[0]));

            $photo = new Photo();
            $photo->setId($id);
            $photo->setExternalId($photoExternalId);
            $photo->setSubscriber($subscriberReference);
            $photo->setExternalHash($photoExternalHash);

            $this->manager->persist($photo);
        }
    }
}
