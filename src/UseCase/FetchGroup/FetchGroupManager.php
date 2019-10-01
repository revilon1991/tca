<?php

declare(strict_types=1);

namespace App\UseCase\FetchGroup;

use App\Component\Manager\Executer\RowManager;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\FetchMode;

class FetchGroupManager
{
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
     * @param array $params
     *
     * @throws DBALException
     */
    public function saveGroup(array $params): void
    {
        $this->manager->upsert('group', $params, [
            'title',
            'about',
        ]);
    }

    /**
     * @param string $externalId
     * @param string $externalHash
     *
     * @return string
     *
     * @throws DBALException
     */
    public function getGroupId(string $externalId, string $externalHash): string
    {
        $sql = <<<SQL
            select
                g.id
            from `group` g
            where 1
                and g.external_id = :external_id
                and g.external_hash = :external_hash
SQL;

        $stmt = $this->manager->getConnection()->executeQuery($sql, [
            'external_id' => $externalId,
            'external_hash' => $externalHash,
        ]);

        return $stmt->fetch(FetchMode::COLUMN);
    }

    /**
     * @param string $groupId
     * @param int $countSubscriber
     *
     * @throws DBALException
     */
    public function saveReportSubscriberCount(string $groupId, int $countSubscriber): void
    {
        $now = date('Y-m-d');

        $params = [
            'group_id' => $groupId,
            'date' => $now,
            'count_subscriber' => $countSubscriber,
        ];

        $this->manager->upsert('report_group', $params, [
            'count_subscriber',
        ]);
    }

    /**
     * @param array $params
     *
     * @throws DBALException
     */
    public function addPhoto(array $params): void
    {
        $this->manager->insert('photo', $params);
    }

    /**
     * @param string $externalId
     * @param string $externalHash
     *
     * @return string|null
     *
     * @throws DBALException
     */
    public function getChannelPhoto(string $externalId, string $externalHash): ?string
    {
        $sql = <<<SQL
            select
                id
            from photo p
            where 1
                and p.external_id = :external_id
                and p.external_hash = :external_hash
SQL;

        $stmt = $this->manager->getConnection()->executeQuery($sql, [
            'external_id' => $externalId,
            'external_hash' => $externalHash,
        ]);

        return $stmt->fetch(FetchMode::COLUMN) ?: null;
    }

    /**
     * @return string
     */
    public function generateUniqueId(): string
    {
        return $this->manager->generateUniqueId();
    }

    /**
     * @return array
     *
     * @throws DBALException
     */
    public function getGroupUsernameList(): array
    {
        $sql = <<<SQL
            select
                username
            from `group`
SQL;
        $stmt = $this->manager->getConnection()->executeQuery($sql);

        return $stmt->fetchAll(FetchMode::COLUMN);
    }
}
