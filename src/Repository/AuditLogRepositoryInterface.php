<?php

declare(strict_types=1);

namespace Pedropiedade\AuditLogBundle\Repository;

use Doctrine\ORM\QueryBuilder;
use Pedropiedade\AuditLogBundle\Dto\AuditLogQuery;
use Pedropiedade\AuditLogBundle\Entity\AbstractAuditLog;

interface AuditLogRepositoryInterface
{
    public function findDeletedForEntityQb(string $entityFqcn): QueryBuilder;

    public function findForQueryQb(AuditLogQuery $query): QueryBuilder;

    public function save(AbstractAuditLog $entity, bool $flush = true): void;

    public function delete(AbstractAuditLog $entity, bool $flush = true): void;
}
