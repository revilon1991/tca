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
            from photo p
            inner join group_subscriber gs on gs.subscriber_id = p.subscriber_id
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
                gs.group_id,
                gs.subscriber_id,
                p.id photo_id
            from photo p
            inner join group_subscriber gs on gs.subscriber_id = p.subscriber_id
            where p.people = :photo_is_people
SQL;
        $stmt = $this->manager->getConnection()->executeQuery($sql, [
            'photo_is_people' => 1,
        ]);

        foreach ($stmt->fetchAll() as $row) {
            $resultList[$row['group_id']][$row['subscriber_id']][] = $row['photo_id'];
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
                'group_id' => $groupId,
                'count_man' => $countMale[MaleClassificationEnum::MAN],
                'count_woman' => $countMale[MaleClassificationEnum::WOMAN],
            ];
        }

        foreach (array_chunk($paramsList, self::UPSERT_CHUNK) as $chunk) {
            $this->manager->upsertBulk('report_group', $chunk, [
                'count_man',
                'count_woman',
            ]);
        }
    }
}
