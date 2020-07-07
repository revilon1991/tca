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
        $this->manager->upsert('Group', $params, [
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
            from `Group` g
            where 1
                and g.externalId = :external_id
                and g.externalHash = :external_hash
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
            'groupId' => $groupId,
            'date' => $now,
            'countSubscriber' => $countSubscriber,
        ];

        $this->manager->upsert('ReportGroup', $params, [
            'countSubscriber',
        ]);
    }

    /**
     * @param array $params
     *
     * @throws DBALException
     */
    public function addPhoto(array $params): void
    {
        $this->manager->insert('Photo', $params);
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
            from Photo p
            where 1
                and p.externalId = :external_id
                and p.externalHash = :external_hash
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
            from `Group`
SQL;
        $stmt = $this->manager->getConnection()->executeQuery($sql);

        return $stmt->fetchAll(FetchMode::COLUMN);
    }
}
