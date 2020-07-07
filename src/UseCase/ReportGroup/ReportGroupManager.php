<?php

declare(strict_types=1);

namespace App\UseCase\ReportGroup;

use App\Component\Manager\Executer\RowManager;
use Doctrine\DBAL\DBALException;

class ReportGroupManager
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
     * @param string $groupId
     *
     * @return array
     *
     * @throws DBALException
     */
    public function getReportGroupList(string $groupId): array
    {
        $beforeDate = date('Y-m-d', strtotime('-8 day'));
        $afterDate = date('Y-m-d', strtotime('-1 day'));

        $sql = <<<SQL
            select
                group_id,
                unix_timestamp(date) as date,
                count_subscriber,
                count_man,
                count_woman
            from report_group
            where 1
                and date between :before_date and :after_date
                and group_id = :group_id
SQL;

        $stmt = $this->manager->getConnection()->executeQuery($sql, [
            'before_date' => $beforeDate,
            'after_date' => $afterDate,
            'group_id' => $groupId,
        ]);

        return $stmt->fetchAll();
    }
}
