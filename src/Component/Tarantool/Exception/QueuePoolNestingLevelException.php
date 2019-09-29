<?php

declare(strict_types=1);

namespace App\Component\Tarantool\Exception;

use RuntimeException;

class QueuePoolNestingLevelException extends RuntimeException
{
}
