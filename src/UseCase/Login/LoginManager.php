<?php

declare(strict_types=1);

namespace App\UseCase\Login;

use App\Component\Manager\Executer\RowManager;
use Doctrine\DBAL\DBALException;

class LoginManager
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
     * @param string $username
     *
     * @return array|null
     *
     * @throws DBALException
     */
    public function getUser(string $username): ?array
    {
        $sql = <<<SQL
            select
                id,
                username,
                roles
            from `user`
            where 1
                and username = :username
SQL;

        $stmt = $this->manager->getConnection()->executeQuery($sql, [
            'username' => $username,
        ]);

        return $stmt->fetch() ?: null;
    }

    /**
     * @param string $userId
     * @param string $userAgent
     * @param string $clientIp
     *
     * @throws DBALException
     */
    public function updateUser(string $userId, string $userAgent, string $clientIp): void
    {
        $now = date('Y-m-d H:i:s');

        $this->manager->update('user', [
            'id' => $userId,
            'actual_ip' => $clientIp,
            'actual_user_agent' => $userAgent,
            'actual_login_time' => $now,
        ]);
    }
}
