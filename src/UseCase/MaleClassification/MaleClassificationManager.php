<?php

declare(strict_types=1);

namespace App\UseCase\MaleClassification;

use App\Component\Manager\Executer\RowManager;
use App\Enum\MaleClassificationEnum;
use Doctrine\DBAL\DBALException;

class MaleClassificationManager
{
    private const UPSERT_CHUNK = 100;

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
    public function getPhotoList(): array
    {
        $sql = <<<SQL
            select
                p.id,
                p.extension
            from Photo p
            inner join GroupSubscriber gs on gs.subscriberId = p.subscriberId
            where p.people = :photo_is_people
SQL;

        $stmt = $this->manager->getConnection()->executeQuery($sql, [
            'photo_is_people' => 1,
        ]);

        return $stmt->fetchAll();
    }

    /**
     * @return array
     *
     * @throws DBALException
     */
    public function getSubscriberPhotoList(): array
    {
        $resultList = [];

        $sql = <<<SQL
            select
                gs.groupId,
                gs.subscriberId,
                p.id photoId
            from Photo p
            inner join GroupSubscriber gs on gs.subscriberId = p.subscriberId
            where p.people = :photo_is_people
SQL;
        $stmt = $this->manager->getConnection()->executeQuery($sql, [
            'photo_is_people' => 1,
        ]);

        foreach ($stmt->fetchAll() as $row) {
            $resultList[$row['groupId']][$row['subscriberId']][] = $row['photoId'];
        }

        return $resultList;
    }

    /**
     * @param array $countMaleList
     *
     * @throws DBALException
     */
    public function saveReportSubscriberPredictMale(array $countMaleList): void
    {
        $paramsList = [];

        $now = date('Y-m-d');

        foreach ($countMaleList as $groupId => $countMale) {
            $paramsList[] = [
                'date' => $now,
                'groupId' => $groupId,
                'countMan' => $countMale[MaleClassificationEnum::MAN],
                'countWoman' => $countMale[MaleClassificationEnum::WOMAN],
            ];
        }

        foreach (array_chunk($paramsList, self::UPSERT_CHUNK) as $chunk) {
            $this->manager->upsertBulk('ReportGroup', $chunk, [
                'countMan',
                'countWoman',
            ]);
        }
    }
}
