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
        $sql = <<<SQL
            select
                s.id,
                s.external_id,
                s.external_hash,
                group_concat(concat(p.external_id, p.external_hash)) photo_unique_keys
            from subscriber s
            inner join group_subscriber gs on s.id = gs.subscriber_id
            left join photo p on s.id = p.subscriber_id
            group by s.id, s.external_id, s.external_hash
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
            select count(distinct gs.subscriber_id)
            from group_subscriber gs
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
            $this->manager->insertBulk('photo', $chunkList);
        }
    }
}
