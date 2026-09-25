<?php

declare(strict_types=1);

namespace Pedropiedade\AuditLogBundle;

use Pedropiedade\AuditLogBundle\Entity\AbstractAuditLog;

/**
 * Implement this in the consuming app, returning a new instance of its own
 * concrete AbstractAuditLog subclass - the one thing this bundle can't
 * provide a generic default for, since the entity is per-app-owned.
 */
interface AuditLogEntryFactoryInterface
{
    public function create(): AbstractAuditLog;
}
