<?php

declare(strict_types=1);

namespace Pedropiedade\AuditLogBundle\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\AbstractIdGenerator;
use Symfony\Component\Uid\Uuid;

class UuidGenerator extends AbstractIdGenerator
{
    public function generateId(EntityManagerInterface $em, mixed $entity): string
    {
        return Uuid::v7()->toString();
    }
}
