<?php

declare(strict_types=1);

namespace App\UseCase\MaleClassification;

use App\Component\Manager\Executer\RowManager;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\FetchMode;
use Generator;

class MaleClassificationManager
{
    private const UPDATE_CHUNK = 100;

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
     * @return int
     * @throws DBALException
     */
    public function getSubscriberPredictCount(): int
    {
        $sql = <<<SQL
            select count(*)
            from subscriber
            where 1
                and people = :is_people
                and male is null
SQL;

        $stmt = $this->manager->getConnection()->executeQuery($sql, [
            'is_people' => true,
        ]);

        return (int)$stmt->fetch(FetchMode::COLUMN) ?: 0;
    }

    /**
     * @return Generator
     *
     * @throws DBALException
     */
    public function getSubscriberPhotoList(): Generator
    {
        $sql = <<<SQL
                select
                    s.id subscriber_id,
                    group_concat(p.id) photo_ids,
                    group_concat(p.extension) as extensions
                from subscriber s
                inner join photo p on s.id = p.subscriber_id
                where 1
                    and s.people = :is_people
                    and s.male is null
                    and p.people = :is_people
                group by p.subscriber_id
SQL;

        $stmt = $this->manager->getConnection()->executeQuery($sql, [
            'is_people' => true,
        ]);

        while ($result = $stmt->fetch()) {
            yield $result['subscriber_id'] => [
                'photo_ids' => $result['photo_ids'],
                'extensions' => $result['extensions'],
            ];
        }

        $stmt->closeCursor();
    }

    /**
     * @param array $subscriberPredictList
     *
     * @throws DBALException
     */
    public function savePredict(array $subscriberPredictList): void
    {
        foreach (array_chunk($subscriberPredictList, self::UPDATE_CHUNK, true) as $chunkList) {
            $paramsList = [];

            foreach ($chunkList as $subscriberId => $subscriberPredict) {
                $paramsList[] = [
                    'id' => $subscriberId,
                    'male' => $subscriberPredict,
                ];
            }

            $this->manager->updateBulk('subscriber', $paramsList);
        }
    }
}
