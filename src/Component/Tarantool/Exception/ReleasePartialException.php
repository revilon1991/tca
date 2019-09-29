<?php

declare(strict_types=1);

namespace App\Component\Tarantool\Exception;

use RuntimeException;

class ReleasePartialException extends RuntimeException
{
    /**
     * @var int
     */
    private $delay;

    /**
     * @var array
     */
    private $releaseTaskIdList;

    /**
     * @param string $name
     * @param array $releaseTaskIdList
     * @param int $delay
     */
    public function __construct(string $name, array $releaseTaskIdList, int $delay = 1)
    {
        $this->releaseTaskIdList = $releaseTaskIdList;
        $this->delay = $delay;

        parent::__construct("Consumer $name release partial taskList");
    }

    /**
     * @return array
     */
    public function getReleaseTaskIdList(): array
    {
        return $this->releaseTaskIdList;
    }

    /**
     * @return int
     */
    public function getDelay(): int
    {
        return $this->delay;
    }
}
