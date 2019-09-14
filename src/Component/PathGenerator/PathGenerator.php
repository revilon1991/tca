<?php

declare(strict_types=1);

namespace App\Component\PathGenerator;

class PathGenerator
{
    private const PHP_INT_MAX_32 = 10;

    /**
     * @param string $uniqueId
     *
     * @return string
     */
    public function generateIntPath(string $uniqueId): string
    {
        $firstLevel = 100000;
        $secondLevel = 1000;

        if (strlen($uniqueId) >= PHP_INT_MAX) {
            $lenDiff = strlen($uniqueId) - self::PHP_INT_MAX_32 + 1;
            $id = substr($uniqueId, 0, -$lenDiff);
        } else {
            $id = $uniqueId;
        }

        $repositoryFirstLevel = (int)($id / $firstLevel);
        $repositorySecondLevel = (int)(($id - ($repositoryFirstLevel * $firstLevel)) / $secondLevel);

        return sprintf('%04s/%02s', $repositoryFirstLevel + 1, $repositorySecondLevel + 1);
    }
}
