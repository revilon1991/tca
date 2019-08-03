<?php

declare(strict_types=1);

namespace App\Component\IdGenerator;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Id\AbstractIdGenerator;
use Exception;
use function getmypid;
use function hexdec;
use function str_pad;
use function substr;
use function uniqid;

class IdGenerator extends AbstractIdGenerator
{
    /**
     * @return string
     */
    public function generateUniqueId(): string
    {
        $hexDecSting = (string)hexdec(uniqid());
        $pidString = (string)getmypid();

        return str_pad($pidString, 5, '0') . substr($hexDecSting, -13);
    }

    /**
     * @param int $maxThread
     *
     * @return int
     *
     * @throws Exception
     */
    public function generateThreadId(int $maxThread): int
    {
        return random_int(1, $maxThread);
    }

    /**
     * @param int $maxThread
     * @param string $id
     *
     * @return int
     */
    public function generateThreadById(int $maxThread, string $id): int
    {
        return ((int)$id % $maxThread) + 1;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(EntityManager $em, $entity)
    {
        return $this->generateUniqueId();
    }
}
