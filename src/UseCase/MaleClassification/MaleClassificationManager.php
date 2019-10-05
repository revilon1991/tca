<?php

declare(strict_types=1);

namespace App\UseCase\MaleClassification;

use App\Component\Manager\Executer\RowManager;
use App\Enum\MaleClassificationEnum;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\FetchMode;
use Generator;

class MaleClassificationManager
{
    private const UPSERT_CHUNK = 100;
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
     * @return int
     * @throws DBALException
     */
    public function getSubscriberPredictCount(): int
    {
        $sql = <<<SQL
            select count(distinct s.id)
            from subscriber s
            inner join photo p on s.id = p.subscriber_id
            where s.people = :subscriber_is_people
SQL;

        $stmt = $this->manager->getConnection()->executeQuery($sql, [
            'subscriber_is_people' => 1,
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
        $this->manager->getConnection()->exec(sprintf(
            'set session group_concat_max_len=%s',
            self::GROUP_CONCAT_MAX_LENGTH_32_BIT
        ));

        $sql = <<<SQL
                select
                    gs.subscriber_id,
                    group_concat(distinct gs.group_id) group_ids,
                    group_concat(distinct concat(p.id, '.', p.extension)) photo_names
                from subscriber s
                    inner join photo p on s.id = p.subscriber_id
                    inner join group_subscriber gs on s.id = gs.subscriber_id
                where 1
                    and s.people = :subscriber_is_people
                    and p.people = :photo_is_people
                group by gs.subscriber_id
SQL;

        $stmt = $this->manager->getConnection()->executeQuery($sql, [
            'subscriber_is_people' => 1,
            'photo_is_people' => 1,
        ]);

        while ($result = $stmt->fetch()) {
            yield $result['subscriber_id'] => [
                'group_ids' => $result['group_ids'],
                'photo_names' => $result['photo_names'],
            ];
        }

        $stmt->closeCursor();
    }

    /**
     * @param array $countGroupPeople
     *
     * @throws DBALException
     */
    public function saveReportSubscriberPredictMale(array $countGroupPeople): void
    {
        $now = date('Y-m-d');

        foreach (array_chunk($countGroupPeople, self::UPSERT_CHUNK, true) as $chunkList) {
            $paramsList = [];

            foreach ($chunkList as $groupId => $maleList) {
                if (isset($maleList[MaleClassificationEnum::MAN])) {
                    $paramsList[] = [
                        'date' => $now,
                        'group_id' => $groupId,
                        'count_man' => $maleList[MaleClassificationEnum::MAN],
                    ];
                }

                if (isset($maleList[MaleClassificationEnum::WOMAN])) {
                    $paramsList[] = [
                        'date' => $now,
                        'group_id' => $groupId,
                        'count_woman' => $maleList[MaleClassificationEnum::WOMAN],
                    ];
                }
            }

            $this->manager->upsertBulk('report_group', $paramsList, [
                'count_man',
                'count_woman',
            ]);
        }
    }
}
