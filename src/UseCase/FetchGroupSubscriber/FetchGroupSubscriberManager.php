<?php

declare(strict_types=1);

namespace App\UseCase\FetchGroupSubscriber;

use App\Component\Manager\Executer\RowManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;

class FetchGroupSubscriberManager
{
    private const UPSERT_CHUNK = 100;
    private const INSERT_CHUNK = 100;

    /**
     * @var RowManager
     */
    private $manager;

    /**
     * @param RowManager $manager
     */
    public function __construct(RowManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @return array
     *
     * @throws DBALException
     */
    public function getGroupList(): array
    {
        $sql = <<<SQL
            select
                username,
                id
            from `group`
SQL;

        $stmt = $this->manager->getConnection()->executeQuery($sql);

        return $stmt->fetchAll();
    }

    /**
     * @param string $groupId
     *
     * @throws DBALException
     */
    public function deleteUnsubscribedList(string $groupId): void
    {
        $sql = <<<SQL
            delete gs
            from group_subscriber gs
            where gs.group_id = :group_id
SQL;

        $this->manager->getConnection()->executeUpdate($sql, [
            'group_id' => $groupId,
        ]);
    }

    /**
     * @param array $userList
     *
     * @throws DBALException
     */
    public function saveSubscriberList(array $userList): void
    {
        $paramsList = [];

        $userList = array_column($userList, 'user');

        foreach ($userList as $user) {
            $paramsList[] = [
                'external_id' => (string)$user['id'],
                'external_hash' => (string)$user['access_hash'],
                'type' => $user['type'],
                'phone' => $user['phone'] ?? null,
                'username' => $user['username'] ?? null,
                'first_name' => $user['first_name'] ?? null,
                'last_name' => $user['last_name'] ?? null,
            ];
        }

        foreach (array_chunk($paramsList, self::UPSERT_CHUNK) as $params) {
            $this->manager->upsertBulk('subscriber', $params, [
                'phone',
                'first_name',
                'last_name',
            ]);
        }
    }

    /**
     * @param string $groupId
     * @param int $countRealSubscriber
     *
     * @throws DBALException
     */
    public function saveCountRealSubscriber(string $groupId, int $countRealSubscriber): void
    {
        $now = date('Y-m-d');

        $this->manager->upsert(
            'report_group',
            [
                'date' => $now,
                'group_id' => $groupId,
                'count_real_subscriber' => $countRealSubscriber,
            ],
            [
                'count_real_subscriber',
            ]
        );
    }

    /**
     * @param array $userList
     *
     * @return array
     *
     * @throws DBALException
     */
    public function getSubscriberIdList(array $userList): array
    {
        $sql = <<<SQL
            select
                concat(s.external_id, s.external_hash) external_key,
                s.id subscriber_id
            from subscriber s
            where 1
                and s.external_id in (:external_id)
                and s.external_hash in (:external_hash)
SQL;

        $userList = array_column($userList, 'user');
        $externalIdList = array_column($userList, 'id');
        $externalHashList = array_column($userList, 'access_hash');

        $stmt = $this->manager->getConnection()->executeQuery(
            $sql,
            [
                'external_id' => $externalIdList,
                'external_hash' => $externalHashList,
            ],
            [
                'external_id' => Connection::PARAM_STR_ARRAY,
                'external_hash' => Connection::PARAM_STR_ARRAY,
            ]
        );

        return $this->manager->getResultPairList($stmt, 'external_key', 'subscriber_id');
    }

    /**
     * @param array $userList
     * @param array $subscriberIdList
     * @param string $groupId
     *
     * @throws DBALException
     */
    public function saveSubscriberGroupList(array $userList, array $subscriberIdList, string $groupId): void
    {
        $paramsList = [];

        foreach ($userList as $user) {
            $externalKey = $user['user']['id'] . $user['user']['access_hash'];

            $subscriberId = $subscriberIdList[$externalKey];

            $paramsList[] = [
                'role' => $user['role'],
                'group_id' => $groupId,
                'subscriber_id' => $subscriberId,
            ];
        }

        foreach (array_chunk($paramsList, self::INSERT_CHUNK) as $params) {
            $this->manager->insertBulk('group_subscriber', $params, false, false);
        }
    }
}
