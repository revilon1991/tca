<?php

declare(strict_types=1);

namespace App\UseCase\ChangePasswordRestoreForm;

use App\Component\Manager\Executer\RowManager;
use Doctrine\DBAL\DBALException;

class ChangePasswordRestoreFormManager
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
     * @param string $userId
     *
     * @return array|null
     *
     * @throws DBALException
     */
    public function getUser(string $userId): ?array
    {
        $sql = <<<SQL
            select
                id,
                updatedAt
            from `User`
            where id = :user_id
SQL;

        $stmt = $this->manager->getConnection()->executeQuery($sql, [
            'user_id' => $userId,
        ]);

        return $stmt->fetch() ?: null;
    }
}
