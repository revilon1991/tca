<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Group;
use App\Entity\Subscriber;
use App\Service\TelegramAPIService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use MyBuilder\Bundle\CronosBundle\Annotation\Cron;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @Cron(minute="0", hour="0", noLogs=true, server="main")
 */
class FetchGroupSubscriberCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'fetch:group:subscribers';

    /**
     * @var EntityManagerInterface
     */
    private $manager;

    /**
     * @var TelegramAPIService
     */
    private $telegramAPIService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $defaultGroupId;

    /**
     * @required
     *
     * @param EntityManagerInterface $manager
     * @param TelegramAPIService $telegramAPIService
     * @param LoggerInterface $logger
     * @param string $defaultGroupId
     */
    public function dependencyInjection(
        EntityManagerInterface $manager,
        TelegramAPIService $telegramAPIService,
        LoggerInterface $logger,
        string $defaultGroupId
    ): void {
        $this->manager = $manager;
        $this->telegramAPIService = $telegramAPIService;
        $this->logger = $logger;
        $this->defaultGroupId = $defaultGroupId;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->addArgument('group_id', InputArgument::OPTIONAL, 'telegram channel/chat id')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $externalGroupId = $input->getArgument('group_id') ?? $this->defaultGroupId;

        if (!$externalGroupId) {
            throw new InvalidArgumentException('Argument "group_id" is required.');
        }

        $userList = $this->telegramAPIService->getChannelUserList($externalGroupId);

        $userList = array_column($userList, 'user');
        $externalIdList = array_column($userList, 'id');
        $userList = array_combine($externalIdList, $userList);

        $subscriberRepository = $this->manager->getRepository(Subscriber::class);
        /** @var Subscriber[] $subscriberList */
        $subscriberList = $subscriberRepository->findBy([
            'externalId' => $externalIdList,
        ]);

        $groupRepository = $this->manager->getRepository(Group::class);
        /** @var Group|null $group */
        $group = $groupRepository->findOneBy(['username' => $externalGroupId]);

        if (!$group) {
            $output->write(sprintf(
                'Group with id "%s" not found. You must first run command "%s"',
                $externalGroupId,
                FetchGroupCommand::getDefaultName()
            ));

            return;
        }

        $this->manager->beginTransaction();

        try {
            $group->getSubscriberList()->forAll(function ($key, Subscriber $subscriber) use ($group, $userList) {
                $externalId = $subscriber->getExternalId();

                if (!isset($userList[$externalId])) {
                    $subscriber->removeGroup($group);

                    $this->manager->persist($subscriber);
                }

                return true;
            });

            foreach ($subscriberList as $subscriber) {
                $externalId = $subscriber->getExternalId();

                $phone = isset($userList[$externalId]['phone'])
                    ? (string)$userList[$externalId]['phone']
                    : null
                ;
                $firstName = $userList[$externalId]['first_name'] ?? null;
                $lastName = $userList[$externalId]['last_name'] ?? null;

                $subscriber->setPhone($phone);
                $subscriber->setFirstName($firstName);
                $subscriber->setLastName($lastName);
                $subscriber->addGroup($group);

                $this->manager->persist($subscriber);

                unset($userList[$externalId]);
            }

            foreach ($userList as $user) {
                $phone = isset($user['phone']) ? (string)$user['phone']: null;
                $username = isset($user['username']) ? (string)$user['username']: null;
                $firstName = $user['first_name'] ?? null;
                $lastName = $user['last_name'] ?? null;
                $externalId = (string)$user['id'];
                $type = $user['type'];

                $subscriber = new Subscriber();

                $subscriber->setExternalId($externalId);
                $subscriber->setPhone($phone);
                $subscriber->setFirstName($firstName);
                $subscriber->setLastName($lastName);
                $subscriber->setType($type);
                $subscriber->setUsername($username);
                $subscriber->addGroup($group);

                $this->manager->persist($subscriber);
            }

            $this->manager->flush();
            $this->manager->commit();
        } catch (Exception $exception) {
            $this->manager->rollback();

            $this->logger->error(sprintf(
                'Error parse group subscriber with message: "%s"',
                $exception->getMessage()
            ));
        }
    }
}
