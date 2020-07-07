<?php

declare(strict_types=1);

namespace App\UseCase\ReportGroup;

use Doctrine\DBAL\DBALException;

class ReportGroupHandler
{
    /**
     * @var ReportGroupManager
     */
    private $manager;

    /**
     * @param ReportGroupManager $manager
     */
    public function __construct(ReportGroupManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param ReportGroupEntryDto $entryDto
     *
     * @return array
     *
     * @throws DBALException
     */
    public function handle(ReportGroupEntryDto $entryDto): array
    {
        $groupId = $entryDto->getGroupId();

        $reportGroupList = $this->manager->getReportGroupList($groupId);

        foreach ($reportGroupList as &$reportGroup) {
            $reportGroup['date'] = (int)$reportGroup['date'];
            $reportGroup['countSubscriber'] = (int)$reportGroup['countSubscriber'];
            $reportGroup['countMan'] = (int)$reportGroup['countMan'];
            $reportGroup['countWoman'] = (int)$reportGroup['countWoman'];
        }

        return $reportGroupList;
    }
}
