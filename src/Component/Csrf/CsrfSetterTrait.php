<?php

namespace App\Component\Csrf;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

trait CsrfSetterTrait
{
    /**
     * @var CsrfTokenManagerInterface
     */
    private $csrfTokenManager;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @required
     *
     * @param CsrfTokenManagerInterface $csrfTokenManager
     * @param RequestStack $requestStack
     */
    public function dependencyInjection(
        CsrfTokenManagerInterface $csrfTokenManager,
        RequestStack $requestStack
    ): void {
        $this->csrfTokenManager = $csrfTokenManager;
        $this->requestStack = $requestStack;
    }

    /**
     * @param ResponseEvent $event
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        $request = $event->getRequest();

        if (get_class($request) !== Request::class) {
            return;
        }

        $csrfToken = $this->makeCsrfToken($request);

        $this->setCsrfToken($csrfToken, $response);
    }

    /**
     * @param Request $request
     *
     * @return CsrfToken
     */
    public function makeCsrfToken(Request $request): CsrfToken
    {
        $clientIp = $request->getClientIp();

        return $this->csrfTokenManager->refreshToken($clientIp);
    }

    /**
     * @param CsrfToken $csrfToken
     * @param Response $response
     */
    public function setCsrfToken(CsrfToken $csrfToken, Response $response): void
    {
        $response->headers->set(CsrfSetterAwareInterface::CSRF_HEADER_NAME, $csrfToken->getValue());
    }
}
