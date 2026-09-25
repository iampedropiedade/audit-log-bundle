<?php

declare(strict_types=1);

namespace Pedropiedade\AuditLogBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Pedropiedade\AuditLogBundle\Doctrine\UuidGenerator;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Extend this in the consuming app as a concrete #[ORM\Entity], e.g.:
 *
 *   #[ORM\Entity(repositoryClass: AuditLogRepository::class)]
 *   class AuditLog extends AbstractAuditLog {}
 *
 * The $user relation targets Symfony's own UserInterface - configure which
 * concrete class it resolves to via this bundle's `user_class` option
 * (see AuditLogExtension), which prepends a doctrine.orm.resolve_target_entities
 * entry for it.
 */
#[ORM\MappedSuperclass]
abstract class AbstractAuditLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: 'uuid', unique: true, nullable: false)]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    protected ?string $id = null;

    #[ORM\Column(length: 255)]
    protected string $entityFqcn;

    #[ORM\Column(nullable: true)]
    protected ?string $entityId = null;

    #[ORM\Column]
    protected \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: UserInterface::class)]
    protected ?UserInterface $user = null;

    #[ORM\Column(length: 255)]
    protected string $action;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $requestRoute = null;

    /** @var array<string, array<int|string, mixed>> */
    #[ORM\Column]
    protected array $eventData = [];

    #[ORM\Column(length: 255)]
    protected string $ipAddress;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getEntityFqcn(): string
    {
        return $this->entityFqcn;
    }

    public function setEntityFqcn(string $entityFqcn): static
    {
        $this->entityFqcn = $entityFqcn;

        return $this;
    }

    public function getEntityId(): ?string
    {
        return $this->entityId;
    }

    public function setEntityId(?string $entityId): static
    {
        $this->entityId = $entityId;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(): static
    {
        $this->createdAt = new \DateTimeImmutable();

        return $this;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function setUser(?UserInterface $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function getRequestRoute(): ?string
    {
        return $this->requestRoute;
    }

    public function setRequestRoute(string $requestRoute): static
    {
        $this->requestRoute = $requestRoute;

        return $this;
    }

    /**
     * @return array<string, array<int|string, mixed>>
     */
    public function getEventData(): array
    {
        return $this->eventData;
    }

    /**
     * @param array<string, array<int|string, mixed>> $eventData
     */
    public function setEventData(array $eventData): static
    {
        $this->eventData = $eventData;

        return $this;
    }

    public function getIpAddress(): string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): static
    {
        $this->ipAddress = $ipAddress ?? '[UNKNOWN/CLI/WEBHOOK]';

        return $this;
    }
}
