<?php

declare(strict_types=1);

namespace Pedropiedade\AuditLogBundle\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use Pedropiedade\AuditLogBundle\Attribute\Auditable;
use Pedropiedade\AuditLogBundle\AuditLogEntryProcessor;
use Pedropiedade\AuditLogBundle\AuditLogger;
use Pedropiedade\AuditLogBundle\Dto\RemovedEntity;
use Pedropiedade\AuditLogBundle\Tests\Fixtures\ConcreteAuditLog;
use Pedropiedade\AuditLogBundle\Tests\Fixtures\ConcreteAuditLogFactory;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

#[AllowMockObjectsWithoutExpectations]
class AuditLoggerTest extends TestCase
{
    private AuditLogger $logger;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->logger = new AuditLogger(
            entityManager: $this->entityManager,
            security: $this->createMock(Security::class),
            requestStack: $this->createMock(RequestStack::class),
            auditLogEntryProcessor: $this->createMock(AuditLogEntryProcessor::class),
            auditLogEntryFactory: new ConcreteAuditLogFactory(),
        );
    }

    public function testHasPendingEntriesDefaultsFalse(): void
    {
        $this->assertFalse($this->logger->hasPendingEntries());
    }

    public function testResetPendingEntriesKeepsFalse(): void
    {
        $this->logger->resetPendingEntries();
        $this->assertFalse($this->logger->hasPendingEntries());
    }

    public function testHandleEventWithNonAuditableEntityIsNoOp(): void
    {
        $nonAuditableEntity = new \stdClass();
        $em = $this->createMock(EntityManagerInterface::class);

        $this->logger->handleEvent($nonAuditableEntity, 'insert', $em);

        $this->assertFalse($this->logger->hasPendingEntries());
    }

    public function testHandleEventWithEntityWithoutGetIdMethodIsNoOp(): void
    {
        $entity = new class {
            // no getId method, no Auditable attribute
        };
        $em = $this->createMock(EntityManagerInterface::class);

        $this->logger->handleEvent($entity, 'insert', $em);

        $this->assertFalse($this->logger->hasPendingEntries());
    }

    public function testHandleEventWithAuditableEntityAndDeleteActionPersistsEntry(): void
    {
        $entity = new #[Auditable] class {
            public function getId(): ?string
            {
                return 'test-id-123';
            }

            public function __toString(): string
            {
                return 'Test Entity';
            }
        };

        $removedEntity = new RemovedEntity($entity);

        $uow = $this->createMock(UnitOfWork::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getUnitOfWork')->willReturn($uow);
        $em->expects($this->once())->method('persist');

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getCurrentRequest')->willReturn(null);

        $logger = new AuditLogger(
            entityManager: $em,
            security: $this->createMock(Security::class),
            requestStack: $requestStack,
            auditLogEntryProcessor: $this->createMock(AuditLogEntryProcessor::class),
            auditLogEntryFactory: new ConcreteAuditLogFactory(),
        );

        $logger->handleEvent($entity, Auditable::ACTION_DELETE, $em, [$removedEntity]);

        $this->assertTrue($logger->hasPendingEntries());
    }

    public function testHandleEventWithAuditableEntityAndDeleteWithNoRemovedEntityIsNoOp(): void
    {
        $entity = new #[Auditable] class {
            public function getId(): ?string
            {
                return 'id';
            }

            public function __toString(): string
            {
                return 'Entity';
            }
        };

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getUnitOfWork')->willReturn($this->createMock(UnitOfWork::class));

        $logger = new AuditLogger(
            entityManager: $em,
            security: $this->createMock(Security::class),
            requestStack: $this->createMock(RequestStack::class),
            auditLogEntryProcessor: $this->createMock(AuditLogEntryProcessor::class),
            auditLogEntryFactory: new ConcreteAuditLogFactory(),
        );

        $logger->handleEvent($entity, Auditable::ACTION_DELETE, $em, []);
        $this->assertFalse($logger->hasPendingEntries());
    }

    public function testHandleEventWithAuditableEntityAndFilteredAuditEventIsNoOp(): void
    {
        $entity = new #[Auditable(auditEvents: [Auditable::ACTION_UPDATE])] class {
            public function getId(): ?string
            {
                return 'id';
            }
        };

        $em = $this->createMock(EntityManagerInterface::class);

        $logger = new AuditLogger(
            entityManager: $em,
            security: $this->createMock(Security::class),
            requestStack: $this->createMock(RequestStack::class),
            auditLogEntryProcessor: $this->createMock(AuditLogEntryProcessor::class),
            auditLogEntryFactory: new ConcreteAuditLogFactory(),
        );

        // 'insert' is not in auditEvents: ['update'] so should be no-op
        $logger->handleEvent($entity, Auditable::ACTION_INSERT, $em);
        $this->assertFalse($logger->hasPendingEntries());
    }

    public function testHandleEventSkipsWhenChangeSetEmpty(): void
    {
        $entity = new #[Auditable] class {
            public function getId(): ?string
            {
                return 'entity-id';
            }
        };

        $uow = $this->createMock(UnitOfWork::class);
        $uow->method('getEntityChangeSet')->willReturn([]);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getUnitOfWork')->willReturn($uow);
        $em->expects($this->never())->method('persist');

        $logger = new AuditLogger(
            entityManager: $em,
            security: $this->createMock(Security::class),
            requestStack: $this->createMock(RequestStack::class),
            auditLogEntryProcessor: $this->createMock(AuditLogEntryProcessor::class),
            auditLogEntryFactory: new ConcreteAuditLogFactory(),
        );

        $logger->handleEvent($entity, Auditable::ACTION_UPDATE, $em);
        $this->assertFalse($logger->hasPendingEntries());
    }

    public function testHandleEventProcessesAuditableEntityUsingInjectedFactory(): void
    {
        $entity = new #[Auditable] class {
            public function getId(): ?string
            {
                return 'entity-id';
            }
        };

        $uow = $this->createMock(UnitOfWork::class);
        $uow->method('getEntityChangeSet')->willReturn(['description' => ['old value', 'new value']]);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getUnitOfWork')->willReturn($uow);

        $processor = $this->createMock(AuditLogEntryProcessor::class);
        $processor->method('isUpdated')->willReturn(true);
        $processor->method('createDisplayDetails')->willReturnArgument(0);

        $persisted = null;
        $em->expects($this->once())->method('persist')->willReturnCallback(function (object $entity) use (&$persisted): void {
            $persisted = $entity;
        });

        $logger = new AuditLogger(
            entityManager: $em,
            security: $this->createMock(Security::class),
            requestStack: $this->createMock(RequestStack::class),
            auditLogEntryProcessor: $processor,
            auditLogEntryFactory: new ConcreteAuditLogFactory(),
        );

        $logger->handleEvent($entity, Auditable::ACTION_INSERT, $em);

        $this->assertInstanceOf(ConcreteAuditLog::class, $persisted);
        $this->assertSame('entity-id', $persisted->getEntityId());
    }
}
