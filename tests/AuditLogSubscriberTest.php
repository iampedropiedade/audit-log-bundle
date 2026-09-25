<?php

declare(strict_types=1);

namespace Pedropiedade\AuditLogBundle\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Pedropiedade\AuditLogBundle\Attribute\Auditable;
use Pedropiedade\AuditLogBundle\AuditLogger;
use Pedropiedade\AuditLogBundle\AuditLogSubscriber;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class AuditLogSubscriberTest extends TestCase
{
    private AuditLogger&MockObject $auditLogger;
    private AuditLogSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->auditLogger = $this->createMock(AuditLogger::class);
        $this->subscriber = new AuditLogSubscriber($this->auditLogger);
    }

    public function testPostPersistDelegatesToAuditLogger(): void
    {
        $entity = new \stdClass();
        $em = $this->createMock(EntityManagerInterface::class);
        $args = new PostPersistEventArgs($entity, $em);

        $this->auditLogger
            ->expects($this->once())
            ->method('handleEvent')
            ->with($entity, Auditable::ACTION_INSERT, $em);

        $this->subscriber->postPersist($args);
    }

    public function testPostUpdateDelegatesToAuditLogger(): void
    {
        $entity = new \stdClass();
        $em = $this->createMock(EntityManagerInterface::class);
        $args = new PostUpdateEventArgs($entity, $em);

        $this->auditLogger
            ->expects($this->once())
            ->method('handleEvent')
            ->with($entity, Auditable::ACTION_UPDATE, $em);

        $this->subscriber->postUpdate($args);
    }

    public function testPostRemoveDelegatesToAuditLogger(): void
    {
        $entity = new \stdClass();
        $em = $this->createMock(EntityManagerInterface::class);

        $this->subscriber->preRemove(new PreRemoveEventArgs($entity, $em));

        $this->auditLogger
            ->expects($this->once())
            ->method('handleEvent')
            ->with($entity, Auditable::ACTION_DELETE, $em, $this->isArray());

        $this->subscriber->postRemove(new PostRemoveEventArgs($entity, $em));
    }

    public function testPreRemoveStoresEntityForLaterProcessing(): void
    {
        $entity = new \stdClass();
        $em = $this->createMock(EntityManagerInterface::class);

        $this->subscriber->preRemove(new PreRemoveEventArgs($entity, $em));

        $this->auditLogger
            ->expects($this->once())
            ->method('handleEvent')
            ->with(
                $entity,
                Auditable::ACTION_DELETE,
                $em,
                $this->callback(fn (array $removals) => 1 === count($removals))
            );

        $this->subscriber->postRemove(new PostRemoveEventArgs($entity, $em));
    }

    public function testPostFlushSkipsWhenNoPendingEntries(): void
    {
        $logger = $this->createMock(AuditLogger::class);
        $logger->method('hasPendingEntries')->willReturn(false);
        $logger->expects($this->never())->method('resetPendingEntries');

        $subscriber = new AuditLogSubscriber($logger);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('flush');

        $subscriber->postFlush(new PostFlushEventArgs($em));
    }

    public function testPostFlushFlushesWhenPendingEntries(): void
    {
        $logger = $this->createMock(AuditLogger::class);
        $logger->method('hasPendingEntries')->willReturn(true);
        $logger->expects($this->once())->method('resetPendingEntries');

        $subscriber = new AuditLogSubscriber($logger);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        $subscriber->postFlush(new PostFlushEventArgs($em));
    }

    public function testPostFlushDoesNotFlushWhileFlushing(): void
    {
        $logger = $this->createMock(AuditLogger::class);
        $logger->method('hasPendingEntries')->willReturn(true);

        $em = $this->createMock(EntityManagerInterface::class);
        $subscriber = new AuditLogSubscriber($logger);
        $event = new PostFlushEventArgs($em);

        $callCount = 0;
        $em->method('flush')->willReturnCallback(function () use ($subscriber, $event, &$callCount) {
            ++$callCount;
            if (1 === $callCount) {
                $subscriber->postFlush($event);
            }
        });

        $subscriber->postFlush($event);
        $this->assertSame(1, $callCount);
    }
}
