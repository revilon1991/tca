<?php

declare(strict_types=1);

namespace App\UseCase\BotUpdates;

use App\Component\Manager\Executer\RowManager;
use Doctrine\DBAL\DBALException;

class BotUpdatesManager
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
            from subscriber
            where external_id = :external_id
SQL;

        $stmt = $this->manager->getConnection()->executeQuery($sql, [
            'external_id' => $externalId,
        ]);

        return (string)$stmt->fetchColumn() ?: null;
    }

    /**
     * @param string $userId
     * @param string $botHash
     * @param string|null $subscriberId
     *
     * @throws DBALException
     */
    public function saveUser(string $userId, string $botHash, ?string $subscriberId = null): void
    {
        $this->manager->upsert(
            'user',
            [
                'subscriber_id' => $subscriberId,
                'bot_hash' => $botHash,
                'subscriber_external_id' => $userId,
            ],
            [
                'subscriber_id',
                'bot_hash',
            ]
        );
    }
}
