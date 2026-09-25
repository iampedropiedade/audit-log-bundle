<?php

declare(strict_types=1);

namespace Pedropiedade\AuditLogBundle\Dto;

use Pedropiedade\AuditLogBundle\AuditLogEntryProcessor;

class RemovedEntity
{
    private ?string $id = null;
    private ?string $name = null;

    public function __construct(mixed $entity)
    {
        if (is_object($entity) && method_exists($entity, 'getId')) {
            /** @var string|int|null $id */
            $id = $entity->getId();
            $this->setId(null === $id ? null : (string) $id);
        }
        $this->setName(new AuditLogEntryProcessor()->createDisplayDetails($entity));
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }
}
