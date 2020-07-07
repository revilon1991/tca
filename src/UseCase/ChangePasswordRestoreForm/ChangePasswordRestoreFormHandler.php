<?php

declare(strict_types=1);

namespace App\UseCase\ChangePasswordRestoreForm;

use App\Component\Csrf\CsrfSetterAwareInterface;
use App\Component\Csrf\CsrfSetterTrait;
use App\Component\JwtToken\Exception\JwtTokenException;
use App\Component\JwtToken\Handler\JwtTokenHandler;
use Doctrine\DBAL\DBALException;
use RuntimeException;

class ChangePasswordRestoreFormHandler implements CsrfSetterAwareInterface
{
    use CsrfSetterTrait;

    /**
     * @var JwtTokenHandler
     */
    private $jwtTokenHandler;

    /**
     * @var ChangePasswordRestoreFormManager
     */
    private $manager;

    /**
     * @param JwtTokenHandler $jwtTokenHandler
     * @param ChangePasswordRestoreFormManager $manager
     */
    public function __construct(
        JwtTokenHandler $jwtTokenHandler,
        ChangePasswordRestoreFormManager $manager
    ) {
        $this->jwtTokenHandler = $jwtTokenHandler;
        $this->manager = $manager;
    }

    /**
     * @param string $contextEncoded
     *
     * @throws ChangePasswordRestoreFormException
     * @throws DBALException
     * @throws JwtTokenException
     */
    public function handle(string $contextEncoded): void
    {
        if (!$contextEncoded) {
            throw new RuntimeException('Context empty');
        }

        $context = $this->jwtTokenHandler->decode($contextEncoded);

        $restoreMethod = $context['restore_method'];
        $userId = $context['user_id'];
        $userUpdatedAt = $context['user_updated_at'];

        $user = $this->manager->getUser($userId);

        if (!$userId) {
            $message = "User by id '$userId' not found. Restore method $restoreMethod";

            throw new ChangePasswordRestoreFormException($message);
        }

        if ($user['updatedAt'] !== $userUpdatedAt) {
            $message = "User id '$userId' have not match updated at from context. Restore method $restoreMethod";

            throw new ChangePasswordRestoreFormException($message);
        }
    }
}
