<?php

declare(strict_types=1);

namespace App\UseCase\RestorePassword;

use App\Component\Manager\Executer\RowManager;
use Doctrine\DBAL\DBALException;

class RestorePasswordManager
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
                subscriberExternalId,
                email,
                updatedAt
            from `User`
            where username = :username
SQL;

        $stmt = $this->manager->getConnection()->executeQuery($sql, [
            'username' => $username,
        ]);

        return $stmt->fetch() ?: null;
    }
}
