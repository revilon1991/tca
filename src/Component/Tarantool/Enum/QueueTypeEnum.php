<?php

declare(strict_types=1);

namespace App\Component\Tarantool\Enum;

class QueueTypeEnum
{
    public const FIFO = 'fifo';
    public const FIFO_TTL = 'fifottl';
    public const FIFO_REPLACE = 'fifo_replace';
    public const FIFO_SKIP = 'fifo_skip';
}
