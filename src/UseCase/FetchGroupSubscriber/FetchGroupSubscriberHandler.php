<?php

declare(strict_types=1);

namespace App\UseCase\FetchGroupSubscriber;

use App\Component\Telegram\Provider\TelegramProvider;
use Doctrine\DBAL\DBALException;

class FetchGroupSubscriberHandler
{
    /**
     * @var TelegramProvider
     */
    private $telegramProvider;

    /**
     * @var FetchGroupSubscriberManager
     */
    private $manager;

    public function __construct(
        FetchGroupSubscriberManager $manager,
        TelegramProvider $telegramProvider
    ) {
        $this->manager = $manager;
        $this->telegramProvider = $telegramProvider;
    }

    /**
     * @throws DBALException
     */
    public function handle(): void
    {
        $groupList = $this->manager->getGroupList();

        foreach ($groupList as $group) {
            $userList = $this->telegramProvider->getChannelUserList($group['username']);
            $userList = array_column($userList, 'user');
            $externalIdList = array_column($userList, 'id');
            $externalHashList = array_column($userList, 'access_hash');

            $this->manager->deleteUnsubscribedList($group['id']);
            $this->manager->saveSubscriberList($userList);

            $subscriberIdList = $this->manager->getSubscriberIdList($externalIdList, $externalHashList);

            $this->manager->addGroupSubscriptionList($subscriberIdList, $group['id']);
        }
    }
}
