<?php

declare(strict_types=1);

namespace Pedropiedade\AuditLogBundle\Tests;

use Pedropiedade\AuditLogBundle\Dto\RemovedEntity;
use PHPUnit\Framework\TestCase;

class RemovedEntityTest extends TestCase
{
    public function testExtractsIdFromObjectWithGetId(): void
    {
        $entity = new class {
            public function getId(): string
            {
                return 'entity-uuid-123';
            }
        };

        $removed = new RemovedEntity($entity);

        $this->assertSame('entity-uuid-123', $removed->getId());
    }

    public function testIdIsNullForObjectWithoutGetId(): void
    {
        $removed = new RemovedEntity(new \stdClass());

        $this->assertNull($removed->getId());
    }

    public function testNameIsDisplayDetailsOfEntity(): void
    {
        $removed = new RemovedEntity('a plain string');

        $this->assertSame('a plain string', $removed->getName());
    }

    public function testSettersAreChainable(): void
    {
        $removed = new RemovedEntity('test');
        $result = $removed->setId('new-id')->setName('new-name');

        $this->assertSame('new-id', $removed->getId());
        $this->assertSame('new-name', $removed->getName());
        $this->assertSame($removed, $result);
    }
}
