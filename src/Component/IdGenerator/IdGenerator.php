<?php

declare(strict_types=1);

namespace App\Component\IdGenerator;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Id\AbstractIdGenerator;
use Doctrine\ORM\Mapping\MappingException;
use Exception;
use function getmypid;
use function hexdec;
use function str_pad;
use function substr;
use function uniqid;
use function get_class;

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
     *
     * @throws MappingException
     */
    public function generate(EntityManager $em, $entity)
    {
        /** @var object $entity */
        $class = $em->getClassMetadata(get_class($entity));

        $idField = $class->getSingleIdentifierFieldName();

        $value = $class->getFieldValue($entity, $idField);

        return $value ?? $this->generateUniqueId();
    }
}
