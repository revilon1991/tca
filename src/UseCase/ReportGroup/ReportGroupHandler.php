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
            $reportGroup['count_subscriber'] = (int)$reportGroup['count_subscriber'];
            $reportGroup['count_man'] = (int)$reportGroup['count_man'];
            $reportGroup['count_woman'] = (int)$reportGroup['count_woman'];
        }

        return $reportGroupList;
    }
}
