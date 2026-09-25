<?php

declare(strict_types=1);

namespace Pedropiedade\AuditLogBundle;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use Pedropiedade\AuditLogBundle\Attribute\Auditable;
use Pedropiedade\AuditLogBundle\Dto\RemovedEntity;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class AuditLogger
{
    private EntityManagerInterface $em;
    private bool $hasPendingEntries = false;

    /** @var \ReflectionAttribute<Auditable>|null */
    private ?\ReflectionAttribute $isAuditable = null;

    /**
     * @var string[]|null
     */
    private ?array $trackedAttributes = null;

    /**
     * @var string[]
     */
    private array $ignoredAttributes;

    /**
     * @param string[] $defaultIgnoredAttributes
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        private readonly Security $security,
        private readonly RequestStack $requestStack,
        private readonly AuditLogEntryProcessor $auditLogEntryProcessor,
        private readonly AuditLogEntryFactoryInterface $auditLogEntryFactory,
        array $defaultIgnoredAttributes = [
            'id',
            'uuid',
            'password',
            'userIdentifier',
            'sortPosition',
            'createdAt',
            '__initializer__',
            '__cloner__',
            '__isInitialized__',
        ],
    ) {
        $this->em = $entityManager;
        $this->ignoredAttributes = $defaultIgnoredAttributes;
    }

    /**
     * @param array<RemovedEntity> $removedEntities
     */
    public function handleEvent(object $entity, string $action, EntityManagerInterface $em, array $removedEntities = []): void
    {
        $entityReflection = new \ReflectionObject($entity);
        $this->createAuditable($entityReflection, $action);
        if (null === $this->isAuditable || false === $entityReflection->hasMethod('getId')) {
            return;
        }
        /** @var object{getId(): string|int|null} $entity */
        $entityId = $entity->getId();
        $uow = $em->getUnitOfWork();
        if (Auditable::ACTION_DELETE === $action) {
            $entityData = array_pop($removedEntities);
            if (null === $entityData) {
                return;
            }
            $entityId = $entityData->getId();
            $entityData = ['entity' => ['from' => sprintf('Entity ID #%s: "%s"', $entityId, $entityData->getName()), 'to' => sprintf('[DELETED] Entity ID #%s: "%s"', $entityId, $entityData->getName())]];
        } else {
            $this->calculateAttributes();
            $entityData = $this->createSummary($uow, $entity);
        }
        $this->createLogEntry($entityReflection->getName(), null === $entityId ? null : (string) $entityId, $action, $entityData);
    }

    private function createAuditable(\ReflectionObject $entityReflection, string $action): void
    {
        $auditable = $entityReflection->getAttributes(Auditable::class);
        if (0 === \count($auditable)) {
            $this->isAuditable = null;

            return;
        }
        $isAuditable = array_pop($auditable);
        if (Auditable::class !== $isAuditable->getName()) {
            $this->isAuditable = null;

            return;
        }
        $arguments = $this->isAuditable?->getArguments();
        if (isset($arguments['auditEvents']) && !in_array($action, $arguments['auditEvents'], true)) {
            return;
        }
        $this->isAuditable = $isAuditable;
    }

    /**
     * @return array<string, array<int|string, mixed>>
     */
    private function createSummary(UnitOfWork $uow, object $entity): array
    {
        $entityData = $uow->getEntityChangeSet($entity);
        /** @var array<string, array<int|string, mixed>> $entityData */
        foreach ($entityData as $field => $change) {
            if (
                true === in_array($field, $this->ignoredAttributes, true)
                || null !== $this->trackedAttributes && false === in_array($field, $this->trackedAttributes, true)
                || false === $this->auditLogEntryProcessor->isUpdated($change)
            ) {
                unset($entityData[$field]);
                continue;
            }
            $entityData[$field] = [
                'from' => $this->auditLogEntryProcessor->createDisplayDetails($change[0]),
                'to' => $this->auditLogEntryProcessor->createDisplayDetails($change[1]),
            ];
        }

        return $entityData;
    }

    private function calculateAttributes(): void
    {
        $arguments = $this->isAuditable?->getArguments();
        if (isset($arguments['ignoredAttributes'])) {
            $this->ignoredAttributes = array_merge($this->ignoredAttributes, $arguments['ignoredAttributes']);
        }
        if (isset($arguments['trackedAttributes'])) {
            $this->trackedAttributes = $arguments['trackedAttributes'];
        }
    }

    /**
     * @param array<string, array<int|string, mixed>> $eventData
     */
    private function createLogEntry(string $entityType, ?string $entityId, string $action, array $eventData): void
    {
        if (0 === \count($eventData)) {
            return;
        }
        $user = $this->security->getUser();
        $request = $this->requestStack->getCurrentRequest();
        $log = $this->auditLogEntryFactory->create()
            ->setEntityFqcn($entityType)
            ->setEntityId($entityId)
            ->setAction($action)
            ->setEventData($eventData)
            ->setUser($user)
            ->setRequestRoute(strval($request?->attributes->get('_route')))
            ->setIpAddress($request?->getClientIp())
            ->setCreatedAt();
        $this->em->persist($log);
        $this->hasPendingEntries = true;
    }

    public function hasPendingEntries(): bool
    {
        return $this->hasPendingEntries;
    }

    public function resetPendingEntries(): void
    {
        $this->hasPendingEntries = false;
    }
}
