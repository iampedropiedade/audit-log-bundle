<?php

declare(strict_types=1);

namespace Pedropiedade\AuditLogBundle\Dto;

class AuditLogQuery
{
    private ?string $id = null;
    private string $fqcn;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): AuditLogQuery
    {
        $this->id = (string) $id;

        return $this;
    }

    public function getFqcn(): string
    {
        return $this->fqcn;
    }

    public function setFqcn(string $fqcn): AuditLogQuery
    {
        $this->fqcn = $fqcn;

        return $this;
    }
}
