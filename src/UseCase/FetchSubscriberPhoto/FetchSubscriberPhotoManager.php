<?php

declare(strict_types=1);

namespace App\UseCase\FetchSubscriberPhoto;

use App\Component\Manager\Executer\RowManager;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\FetchMode;
use Generator;

class FetchSubscriberPhotoManager
{
    private const INSERT_CHUNK = 100;
    private const GROUP_CONCAT_MAX_LENGTH_32_BIT = 4294967295;

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
     * @return Generator
     *
     * @throws DBALException
     */
    public function getSubscriberList(): Generator
    {
        $this->manager->getConnection()->exec(sprintf(
            'set session group_concat_max_len=%s',
            self::GROUP_CONCAT_MAX_LENGTH_32_BIT
        ));

        $sql = <<<SQL
            select
                s.id,
                s.externalId,
                s.externalHash,
                group_concat(distinct concat(p.externalId, p.externalHash)) photoUniqueKeys
            from Subscriber s
            inner join GroupSubscriber gs on s.id = gs.subscriberId
            left join Photo p on s.id = p.subscriberId
            group by s.id, s.externalId, s.externalHash
SQL;

        $stmt = $this->manager->getConnection()->executeQuery($sql);

        while ($row = $stmt->fetch()) {
            yield $row;
        }
    }

    /**
     * @return int
     *
     * @throws DBALException
     */
    public function getSubscriberCount(): int
    {
        $sql = <<<SQL
            select count(distinct gs.subscriberId)
            from GroupSubscriber gs
SQL;
        $stmt = $this->manager->getConnection()->executeQuery($sql);

        return (int)$stmt->fetch(FetchMode::COLUMN) ?: 0;
    }

    /**
     * @return string
     */
    public function generateUniqueId(): string
    {
        return $this->manager->generateUniqueId();
    }

    /**
     * @param array $photoList
     *
     * @throws DBALException
     */
    public function addPhotoList(array $photoList): void
    {
        foreach (array_chunk($photoList, self::INSERT_CHUNK) as $chunkList) {
            $this->manager->insertBulk('Photo', $chunkList);
        }
    }
}
