<?php

declare(strict_types=1);

namespace App\UseCase\Dashboard;

use App\Component\Manager\Executer\RowManager;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\FetchMode;

class DashboardManager
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
     * @return array
     *
     * @throws DBALException
     */
    public function getGroupIdList(): array
    {
        $sql = <<<SQL
            select id
            from `Group`
            order by createdAt DESC
SQL;

        $stmt = $this->manager->getConnection()->executeQuery($sql);

        return $stmt->fetchAll(FetchMode::COLUMN);
    }
}
