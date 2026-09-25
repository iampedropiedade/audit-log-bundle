<?php

declare(strict_types=1);

namespace Pedropiedade\AuditLogBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Pedropiedade\AuditLogBundle\Attribute\Auditable;
use Pedropiedade\AuditLogBundle\Dto\AuditLogQuery;
use Pedropiedade\AuditLogBundle\Entity\AbstractAuditLog;

/**
 * Extend this in the consuming app's own concrete repository, calling
 * parent::__construct($registry, YourConcreteAuditLog::class) same as any
 * other ServiceEntityRepository, e.g.:
 *
 *   class AuditLogRepository extends AbstractAuditLogRepository implements AuditLogRepositoryInterface
 *   {
 *       public function __construct(ManagerRegistry $registry)
 *       {
 *           parent::__construct($registry, AuditLog::class);
 *       }
 *   }
 *
 * @extends ServiceEntityRepository<AbstractAuditLog>
 */
abstract class AbstractAuditLogRepository extends ServiceEntityRepository
{
    public function findDeletedForEntityQb(string $entityFqcn): QueryBuilder
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.id', 'DESC')
            ->where('a.entityFqcn = :entityFqcn')
            ->setParameter('entityFqcn', $entityFqcn)
            ->andWhere('a.action = :action')
            ->setParameter('action', 'delete');
    }

    public function findForQueryQb(AuditLogQuery $query): QueryBuilder
    {
        $qb = $this->createQueryBuilder('a')
            ->orderBy('a.id', 'DESC')
            ->where('a.entityFqcn = :entityFqcn')
            ->setParameter('entityFqcn', $query->getFqcn());
        if (null !== $query->getId()) {
            $qb
                ->andWhere('a.entityId = :entityId')
                ->setParameter('entityId', $query->getId());
        } else {
            $qb
                ->andWhere('a.action = :action')
                ->setParameter('action', Auditable::ACTION_DELETE);
        }

        return $qb;
    }

    public function save(AbstractAuditLog $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function delete(AbstractAuditLog $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
