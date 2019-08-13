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
    private const SUBSCRIBER_KEY_PATTERN = '%s_%s';

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
        $externalHashList = array_column($userList, 'access_hash');

        $responseSubscriberList = [];

        foreach ($userList as $responseSubscriber) {
            $key = sprintf(
                self::SUBSCRIBER_KEY_PATTERN,
                $responseSubscriber['id'],
                $responseSubscriber['access_hash']
            );

            $responseSubscriberList[$key] = $responseSubscriber;
        }

        /** @var Subscriber[] $subscriberList */
        $subscriberList = $this->manager->getRepository(Subscriber::class)->findBy([
            'externalId' => $externalIdList,
            'externalHash' => $externalHashList,
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
            $group->getSubscriberList()->forAll(function ($index, Subscriber $subscriber) use ($group, $userList) {
                $key = sprintf(
                    self::SUBSCRIBER_KEY_PATTERN,
                    $subscriber->getExternalId(),
                    $subscriber->getExternalHash()
                );

                if (!isset($responseSubscriberList[$key])) {
                    $subscriber->removeGroup($group);

                    $this->manager->persist($subscriber);
                }

                return true;
            });

            foreach ($subscriberList as $subscriber) {
                $key = sprintf(
                    self::SUBSCRIBER_KEY_PATTERN,
                    $subscriber->getExternalId(),
                    $subscriber->getExternalHash()
                );

                $externalHash = (string)$responseSubscriberList[$key]['access_hash'];

                $phone = $responseSubscriberList[$key]['phone'] ?? null;
                $firstName = $responseSubscriberList[$key]['first_name'] ?? null;
                $lastName = $responseSubscriberList[$key]['last_name'] ?? null;

                $subscriber->setPhone($phone);
                $subscriber->setFirstName($firstName);
                $subscriber->setLastName($lastName);
                $subscriber->addGroup($group);
                $subscriber->setExternalHash($externalHash); // todo remove after run

                $this->manager->persist($subscriber);

                unset($responseSubscriberList[$key]);
            }

            foreach ($responseSubscriberList as $responseSubscriber) {
                $externalId = (string)$responseSubscriber['id'];
                $externalHash = (string)$responseSubscriber['access_hash'];
                $type = $responseSubscriber['type'];

                $phone = $responseSubscriber['phone'] ?? null;
                $username = $responseSubscriber['username'] ?? null;
                $firstName = $responseSubscriber['first_name'] ?? null;
                $lastName = $responseSubscriber['last_name'] ?? null;

                $subscriber = new Subscriber();

                $subscriber->setExternalId($externalId);
                $subscriber->setExternalHash($externalHash);
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
