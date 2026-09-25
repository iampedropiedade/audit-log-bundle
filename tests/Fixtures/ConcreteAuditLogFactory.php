<?php

declare(strict_types=1);

namespace Pedropiedade\AuditLogBundle\Tests\Fixtures;

use Pedropiedade\AuditLogBundle\AuditLogEntryFactoryInterface;
use Pedropiedade\AuditLogBundle\Entity\AbstractAuditLog;

final class ConcreteAuditLogFactory implements AuditLogEntryFactoryInterface
{
    public function create(): AbstractAuditLog
    {
        return new ConcreteAuditLog();
    }
}
