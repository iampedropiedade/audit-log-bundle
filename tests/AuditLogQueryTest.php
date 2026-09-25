<?php

declare(strict_types=1);

namespace Pedropiedade\AuditLogBundle\Tests;

use Pedropiedade\AuditLogBundle\Dto\AuditLogQuery;
use PHPUnit\Framework\TestCase;

class AuditLogQueryTest extends TestCase
{
    public function testDefaultIdIsNull(): void
    {
        $query = new AuditLogQuery();
        $this->assertNull($query->getId());
    }

    public function testSetIdAndGetId(): void
    {
        $query = new AuditLogQuery();
        $result = $query->setId('entity-uuid');

        $this->assertSame($query, $result);
        $this->assertSame('entity-uuid', $query->getId());
    }

    public function testSetIdWithNullCastsToEmptyString(): void
    {
        $query = new AuditLogQuery();
        $query->setId(null);

        $this->assertSame('', $query->getId());
    }

    public function testSetFqcnAndGetFqcn(): void
    {
        $query = new AuditLogQuery();
        $result = $query->setFqcn('App\\Entity\\Issue');

        $this->assertSame($query, $result);
        $this->assertSame('App\\Entity\\Issue', $query->getFqcn());
    }
}
