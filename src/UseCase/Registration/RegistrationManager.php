<?php

declare(strict_types=1);

namespace App\UseCase\Registration;

use App\Component\Manager\Executer\RowManager;
use Doctrine\DBAL\DBALException;

class RegistrationManager
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
     * @return string|null
     *
     * @throws DBALException
     */
    public function getUserIdByUsername(string $username): ?string
    {
        $sql = <<<SQL
            select id
            from `User`
            where username = :username
SQL;

        $stmt = $this->manager->getConnection()->executeQuery($sql, [
            'username' => $username,
        ]);

        return (string)$stmt->fetchColumn() ?: null;
    }

    /**
     * @param string $botHash
     *
     * @return array|null
     *
     * @throws DBALException
     */
    public function getUserByBotHash(string $botHash): ?array
    {
        $sql = <<<SQL
            select
                id,
                subscriberExternalId
            from `User`
            where botHash = :bot_hash
SQL;

        $stmt = $this->manager->getConnection()->executeQuery($sql, [
            'bot_hash' => $botHash,
        ]);

        return $stmt->fetch() ?: null;
    }

    /**
     * @param string $externalId
     *
     * @return string|null
     *
     * @throws DBALException
     */
    public function getSubscriberId(string $externalId): ?string
    {
        $sql = <<<SQL
            select id
            from Subscriber
            where externalId = :external_id
SQL;

        $stmt = $this->manager->getConnection()->executeQuery($sql, [
            'external_id' => $externalId,
        ]);

        return (string)$stmt->fetchColumn() ?: null;
    }

    /**
     * @param array $params
     *
     * @throws DBALException
     */
    public function updateUser(array $params): void
    {
        $this->manager->update('User', $params);
    }
}
