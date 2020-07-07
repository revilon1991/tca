<?php

declare(strict_types=1);

namespace App\UseCase\Dashboard;

use Doctrine\DBAL\DBALException;

class DashboardHandler
{
    /**
     * @var DashboardManager
     */
    private $manager;

    /**
     * @param DashboardManager $manager
     */
    public function __construct(DashboardManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @return array
     *
     * @throws DBALException
     */
    public function handle(): array
    {
        $resultList['group_id_list'] = $this->manager->getGroupIdList();

        return $resultList;
    }
}
