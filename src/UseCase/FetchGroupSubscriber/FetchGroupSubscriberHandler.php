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

            $this->manager->deleteUnsubscribedList($group['id']);
            $this->manager->saveSubscriberList($userList);

            $subscriberIdList = $this->manager->getSubscriberIdList($userList);

            $this->manager->saveSubscriberGroupList($userList, $subscriberIdList, $group['id']);
            $this->manager->saveCountRealSubscriber($group['id'], count($subscriberIdList));
        }
    }
}
