<?php

declare(strict_types=1);

namespace App\UseCase\PeopleClassification;

use App\Component\Manager\Executer\RowManager;
use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\DBALException;

class PeopleClassificationManager
{
    private const UPSERT_CHUNK = 100;

    /**
     * @var RowManager
     */
    private $manager;

    /**
     * @required
     *
     * @param RowManager $manager
     */
    public function dependencyInjection(RowManager $manager): void
    {
        $this->manager = $manager;
    }

    public function beginTransaction(): void
    {
        $this->manager->getConnection()->beginTransaction();
    }

    /**
     * @throws ConnectionException
     */
    public function commit(): void
    {
        $this->manager->getConnection()->commit();
    }

    /**
     * @throws ConnectionException
     */
    public function rollBack(): void
    {
        $this->manager->getConnection()->rollBack();
    }

    /**
     * @throws DBALException
     */
    public function clearPeopleMark(): void
    {
        $sql = <<<SQL
            update subscriber set people = null;
            update photo set people = null;
SQL;

        $this->manager->getConnection()->exec($sql);
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
SQL;

        $stmt = $this->manager->getConnection()->executeQuery($sql);

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
                gs.subscriber_id,
                p.id photo_id
            from photo p
            inner join group_subscriber gs on gs.subscriber_id = p.subscriber_id
SQL;
        $stmt = $this->manager->getConnection()->executeQuery($sql);

        foreach ($stmt->fetchAll() as $row) {
            $resultList[$row['subscriber_id']][$row['photo_id']] = $row['photo_id'];
        }
        
        return $resultList;
    }
    
    /**
     * @param array $countGroupPeople
     *
     * @throws DBALException
     */
    public function saveReportSubscriberPredictPeople(array $countGroupPeople): void
    {
        $now = date('Y-m-d');

        foreach (array_chunk($countGroupPeople, self::UPSERT_CHUNK, true) as $chunkList) {
            $paramsList = [];

            foreach ($chunkList as $groupId => $countPeople) {
                $paramsList[] = [
                    'date' => $now,
                    'group_id' => $groupId,
                    'count_people' => $countPeople,
                ];
            }

            $this->manager->upsertBulk('report_group', $paramsList, [
                'count_people',
            ]);
        }
    }

    /**
     * @param array $peoplePredictPhotoList
     *
     * @throws DBALException
     */
    public function savePhotoPredictPeople(array $peoplePredictPhotoList): void
    {
        foreach (array_chunk($peoplePredictPhotoList, self::UPSERT_CHUNK, true) as $chunkList) {
            $paramsList = [];

            foreach ($chunkList as $photoId => $predict) {
                $paramsList[] = [
                    'id' => $photoId,
                    'people' => (int)$predict,
                ];
            }

            $this->manager->updateBulk('photo', $paramsList);
        }
    }

    /**
     * @param array $peoplePredictSubscriberList
     *
     * @throws DBALException
     */
    public function saveSubscriberPredictPeople(array $peoplePredictSubscriberList): void
    {
        foreach (array_chunk($peoplePredictSubscriberList, self::UPSERT_CHUNK, true) as $chunkList) {
            $paramsList = [];

            foreach ($chunkList as $subscriberId => $predict) {
                $paramsList[] = [
                    'id' => $subscriberId,
                    'people' => (int)$predict,
                ];
            }

            $this->manager->updateBulk('subscriber', $paramsList);
        }
    }

    /**
     * @return array
     *
     * @throws DBALException
     */
    public function getPeopleSubscriberGroupCount(): array
    {
        $resultList = [];

        $sql = <<<SQL
            select
                gs.group_id,
                count(gs.subscriber_id) cnt
            from subscriber s
            inner join group_subscriber gs on s.id = gs.subscriber_id
            where s.people = :is_people
            group by gs.group_id
SQL;

        $stmt = $this->manager->getConnection()->executeQuery($sql, [
            'is_people' => 1,
        ]);

        foreach ($stmt->fetchAll() as $row) {
            $resultList[$row['group_id']] = $row['cnt'];
        }

        return $resultList;
    }
}
