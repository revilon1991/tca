<?php

declare(strict_types=1);

namespace App\Component\PathGenerator;

class PathGenerator
{
    /**
     * @param string $uniqueId
     *
     * @return string
     */
    public function generateBigIntPath(string $uniqueId): string
    {
        $firstLevel = '100000';
        $secondLevel = '1000';

        // division
        $repositoryFirstLevel = gmp_div(gmp_init($uniqueId), gmp_init($firstLevel));

        // multiplication
        $valueMultiplication = gmp_mul($repositoryFirstLevel, gmp_init($firstLevel));

        // subtraction
        $valueSubtraction = gmp_sub(gmp_init($uniqueId), $valueMultiplication);

        // division
        $repositorySecondLevel = gmp_div($valueSubtraction, gmp_init($secondLevel));

        // addition
        $repositoryFirstLevel = gmp_add($repositoryFirstLevel, gmp_init('1'));
        $repositorySecondLevel = gmp_add($repositorySecondLevel, gmp_init('1'));

        $repositoryFirstLevel = (string)$repositoryFirstLevel;
        $repositorySecondLevel = (string)$repositorySecondLevel;

        return sprintf('%04s/%02s', $repositoryFirstLevel, $repositorySecondLevel);
    }

    /**
     * @param int $uniqueId
     *
     * @return string
     */
    public function generateIntPath(int $uniqueId): string
    {
        $firstLevel = 100000;
        $secondLevel = 1000;

        $repositoryFirstLevel = (int)($uniqueId / $firstLevel);
        $repositorySecondLevel = (int)(($uniqueId - ($repositoryFirstLevel * $firstLevel)) / $secondLevel);

        return sprintf('%04s/%02s', $repositoryFirstLevel + 1, $repositorySecondLevel + 1);
    }
}
