<?php

declare(strict_types=1);

namespace App\UseCase\ChangePasswordRestore;

use App\Component\Manager\Executer\RowManager;
use Doctrine\DBAL\DBALException;

class ChangePasswordRestoreManager
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
                updated_at
            from `user`
            where id = :user_id
SQL;

        $stmt = $this->manager->getConnection()->executeQuery($sql, [
            'user_id' => $userId,
        ]);

        return $stmt->fetch() ?: null;
    }

    /**
     * @param array $params
     *
     * @throws DBALException
     */
    public function saveUser(array $params): void
    {
        $this->manager->update('user', $params);
    }
}
