<?php

declare(strict_types=1);

namespace App\UseCase\EditUser;

use App\Component\Manager\Executer\RowManager;
use Doctrine\DBAL\DBALException;

class EditUserManager
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
     * @param array $params
     *
     * @throws DBALException
     */
    public function saveUser(array $params): void
    {
        $this->manager->update('User', $params);
    }
}
