<?php

declare(strict_types=1);

namespace App\Guesser;

use Symfony\Component\HttpFoundation\Request;
use Wakeapp\Bundle\ApiPlatformBundle\Guesser\ApiAreaGuesserInterface;

class ApiAreaGuesser implements ApiAreaGuesserInterface
{
    /**
     * {@inheritDoc}
     */
    public function getApiVersion(Request $request): ?int
    {
        $apiVersionMatch = [];
        preg_match('/^\/v([\d]+)\//i', $request->getPathInfo(), $apiVersionMatch);

        if (empty($apiVersionMatch)) {
            return null;
        }

        return (int)end($apiVersionMatch);
    }

    /**
     * {@inheritdoc}
     */
    public function isApiRequest(Request $request): bool
    {
        return strpos($request->getPathInfo(), '/cabinet/api') === 0;
    }
}
