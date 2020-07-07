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

    /**
     * @throws DBALException
     */
    public function clearPeopleMark(): void
    {
        $sql = <<<SQL
            update Subscriber set people = null;
            update Photo set people = null;
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
            from Photo p
            inner join GroupSubscriber gs on gs.subscriberId = p.subscriberId
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
                gs.subscriberId,
                p.id photoId
            from Photo p
            inner join GroupSubscriber gs on gs.subscriberId = p.subscriberId
SQL;
        $stmt = $this->manager->getConnection()->executeQuery($sql);

        foreach ($stmt->fetchAll() as $row) {
            $resultList[$row['subscriberId']][$row['photoId']] = $row['photoId'];
        }
        
        return $resultList;
    }
    
    /**
     * @param array $countGroupPeopleList
     *
     * @throws DBALException
     */
    public function saveReportSubscriberPredictPeople(array $countGroupPeopleList): void
    {
        $now = date('Y-m-d');

        foreach (array_chunk($countGroupPeopleList, self::UPSERT_CHUNK, true) as $chunkList) {
            $paramsList = [];

            foreach ($chunkList as $groupId => $countPeople) {
                $paramsList[] = [
                    'date' => $now,
                    'groupId' => $groupId,
                    'countPeople' => $countPeople,
                ];
            }

            $this->manager->upsertBulk('ReportGroup', $paramsList, [
                'countPeople',
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

            $this->manager->updateBulk('Photo', $paramsList);
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

            $this->manager->updateBulk('Subscriber', $paramsList);
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
                gs.groupId,
                count(gs.subscriberId) cnt
            from Subscriber s
            inner join GroupSubscriber gs on s.id = gs.subscriberId
            where s.people = :is_people
            group by gs.groupId
SQL;

        $stmt = $this->manager->getConnection()->executeQuery($sql, [
            'is_people' => 1,
        ]);

        foreach ($stmt->fetchAll() as $row) {
            $resultList[$row['groupId']] = $row['cnt'];
        }

        return $resultList;
    }
}
