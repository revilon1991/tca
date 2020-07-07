<?php

declare(strict_types=1);

namespace App\Component\Csrf;

use App\Component\Csrf\Exception\CsrfIdentifyUserException;
use App\Component\Csrf\Exception\CsrfValidationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Wakeapp\Bundle\ApiPlatformBundle\HttpFoundation\ApiRequest;

trait CsrfValidatorTrait
{
    /**
     * @var CsrfTokenManagerInterface
     */
    private $csrfTokenManager;

    /**
     * @required
     *
     * @param CsrfTokenManagerInterface $csrfTokenManager
     */
    public function dependencyInjection(CsrfTokenManagerInterface $csrfTokenManager): void
    {
        $this->csrfTokenManager = $csrfTokenManager;
    }

    /**
     * @param RequestEvent $event
     *
     * @throws CsrfIdentifyUserException
     * @throws CsrfValidationException
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$request instanceof ApiRequest) {
            return;
        }

        $csrfToken = $this->getCsrfToken($request);

        $this->validateCsrf($csrfToken);
    }

    /**
     * @param CsrfToken $csrfToken
     *
     * @throws CsrfValidationException
     */
    public function validateCsrf(CsrfToken $csrfToken): void
    {
        if (!$this->csrfTokenManager->isTokenValid($csrfToken)) {
            throw new CsrfValidationException('Validate csrf token error');
        }
    }

    /**
     * @param Request $request
     *
     * @return CsrfToken
     *
     * @throws CsrfIdentifyUserException
     */
    public function getCsrfToken(Request $request): CsrfToken
    {
        $clientIp = $request->getClientIp();

        if ($clientIp === null) {
            throw new CsrfIdentifyUserException('Request ip is empty');
        }

        $csrfTokenString = $request->headers->get(CsrfSetterAwareInterface::CSRF_HEADER_NAME);

        return new CsrfToken($clientIp, $csrfTokenString);
    }
}
