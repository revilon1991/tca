<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Wakeapp\Bundle\ApiPlatformBundle\EventListener\ApiResponseListener as ApiPlatformResponseListener;

class ApiResponseListener extends ApiPlatformResponseListener
{
    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event): void
    {
        $originalResponse = $event->getResponse();

        $cookieList = $originalResponse->headers->getCookies();

        parent::onKernelResponse($event);

        $response = $event->getResponse();

        array_map([$response->headers, 'setCookie'], $cookieList);
    }
}
