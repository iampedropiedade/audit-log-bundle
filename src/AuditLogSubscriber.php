<?php

declare(strict_types=1);

namespace Pedropiedade\AuditLogBundle;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;
use Pedropiedade\AuditLogBundle\Attribute\Auditable;
use Pedropiedade\AuditLogBundle\Dto\RemovedEntity;

#[AsDoctrineListener(event: Events::postPersist, priority: -1000),
    AsDoctrineListener(event: Events::postUpdate, priority: -1000),
    AsDoctrineListener(event: Events::postRemove, priority: -1000),
    AsDoctrineListener(event: Events::preRemove, priority: -1000),
    AsDoctrineListener(event: Events::postFlush, priority: -1000),]
class AuditLogSubscriber
{
    /**
     * @var array<int, mixed>
     */
    private array $removals = [];
    private bool $isFlushing = false;

    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {
    }

    public function preRemove(PreRemoveEventArgs $args): void
    {
        $entity = $args->getObject();
        $removedEntity = new RemovedEntity($entity);
        $this->removals[] = $removedEntity;
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->auditLogger->handleEvent($args->getObject(), Auditable::ACTION_INSERT, $args->getObjectManager());
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->auditLogger->handleEvent($args->getObject(), Auditable::ACTION_UPDATE, $args->getObjectManager());
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $this->auditLogger->handleEvent($args->getObject(), Auditable::ACTION_DELETE, $args->getObjectManager(), $this->removals);
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if ($this->isFlushing || !$this->auditLogger->hasPendingEntries()) {
            return;
        }
        $this->isFlushing = true;
        $this->auditLogger->resetPendingEntries();
        $args->getObjectManager()->flush();
        $this->isFlushing = false;
    }
}
