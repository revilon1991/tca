<?php

declare(strict_types=1);

namespace App\Command;

use App\Component\IdGenerator\IdGenerator;
use App\Component\PathGenerator\PathGenerator;
use App\Entity\Group;
use App\Entity\Photo;
use App\Enum\GroupTypeEnum;
use App\Service\TelegramAPIService;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FetchGroupCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'fetch:group';

    /**
     * @var EntityManagerInterface
     */
    private $manager;

    /**
     * @var TelegramAPIService
     */
    private $telegramAPIService;

    /**
     * @var IdGenerator
     */
    private $idGenerator;

    /**
     * @var PathGenerator
     */
    private $pathGenerator;

    /**
     * @var string
     */
    private $photoPublicDir;

    /**
     * @required
     *
     * @param EntityManagerInterface $manager
     * @param TelegramAPIService $telegramAPIService
     * @param IdGenerator $idGenerator
     * @param PathGenerator $pathGenerator
     * @param string $photoPublicDir
     */
    public function dependencyInjection(
        EntityManagerInterface $manager,
        TelegramAPIService $telegramAPIService,
        IdGenerator $idGenerator,
        PathGenerator $pathGenerator,
        string $photoPublicDir
    ): void {
        $this->manager = $manager;
        $this->telegramAPIService = $telegramAPIService;
        $this->idGenerator = $idGenerator;
        $this->pathGenerator = $pathGenerator;
        $this->photoPublicDir = $photoPublicDir;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->addArgument('group_id', InputArgument::REQUIRED, 'telegram channel/chat id')
        ;
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $externalGroupId = $input->getArgument('group_id');

        $channelInfo = $this->telegramAPIService->getChannelInfo($externalGroupId);

        $externalId = (string)$channelInfo['Chat']['id'];
        $externalHash = (string)$channelInfo['Chat']['access_hash'];

        $group = $this->manager->getRepository(Group::class)->findOneBy([
            'externalId' => $externalId,
            'externalHash' => $externalHash,
        ]);

        if (!$group) {
            $group = new Group();
            $group->setExternalId($externalId);
            $group->setExternalHash($externalHash);
        }

        $group->setTitle($channelInfo['Chat']['title']);
        $group->setUsername($channelInfo['Chat']['username']);
        $group->setAbout($channelInfo['full']['about']);
        $group->setSubscriberCount((int)$channelInfo['full']['participants_count']);

        $this->savePhoto($channelInfo, $group);

        $lastUpdate = (new DateTime())->setTimestamp($channelInfo['last_update']);
        $timezone = new DateTimeZone(date_default_timezone_get());
        $lastUpdate->setTimezone($timezone);

        $group->setLastUpdate($lastUpdate);

        switch ($channelInfo['type']) {
            case GroupTypeEnum::CHANNEL:
                $group->setType(GroupTypeEnum::CHANNEL);
                break;

            case GroupTypeEnum::CHAT:
                $group->setType(GroupTypeEnum::CHAT);
                break;

            default:
                throw new RuntimeException(sprintf(
                    'While execute command "%s", api return undefined group type "%s"',
                    $this->getName(),
                    $channelInfo['type']
                ));
        }

        $this->manager->persist($group);
        $this->manager->flush();
    }

    /**
     * @param array $channelInfo
     * @param Group $group
     */
    private function savePhoto(array $channelInfo, Group $group): void
    {
        $id = $this->idGenerator->generateUniqueId();

        $photoMeta = $channelInfo['full']['chat_photo'];
        $photoExternalId = (string)$photoMeta['id'];
        $photoExternalHash = (string)$photoMeta['access_hash'];

        /** @var Photo $photo */
        foreach ($group->getPhotoList() as $photo) {
            if ($photo->getExternalId() === $photoExternalId && $photo->getExternalHash() === $photoExternalHash) {
                return;
            }
        }

        $photoInfo = $this->telegramAPIService->getPhotoInfo($photoMeta);
        $photoExtension = $photoInfo['ext'];

        $path = sprintf(
            '%s/%s',
            $this->photoPublicDir,
            $this->pathGenerator->generateBigIntPath($id)
        );

        !file_exists($path) ? mkdir($path, 0777, true) : null;

        $pathname = sprintf(
            '%s/%s.%s',
            $path,
            $id,
            ltrim($photoExtension, '.')
        );

        $this->telegramAPIService->savePhoto($photoMeta, $pathname);

        $photo = new Photo();
        $photo->setId($id);
        $photo->setGroup($group);
        $photo->setExternalId($photoExternalId);
        $photo->setExternalHash($photoExternalHash);
        $photo->setExtension($photoExtension);

        $this->manager->persist($photo);
    }
}
