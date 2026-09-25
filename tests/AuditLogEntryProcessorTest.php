<?php

declare(strict_types=1);

namespace Pedropiedade\AuditLogBundle\Tests;

use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\Proxy;
use Pedropiedade\AuditLogBundle\AuditLogEntryProcessor;
use PHPUnit\Framework\TestCase;

class AuditLogEntryProcessorTest extends TestCase
{
    private AuditLogEntryProcessor $processor;

    protected function setUp(): void
    {
        $this->processor = new AuditLogEntryProcessor();
    }

    public function testNullReturnsNullPlaceholder(): void
    {
        $this->assertSame('[NULL]', $this->processor->createDisplayDetails(null));
    }

    public function testObjectWithGetIdReturnsClassAndId(): void
    {
        $entity = new FakeAuditableEntity();
        $result = $this->processor->createDisplayDetails($entity);
        $this->assertStringContainsString('abc-123', $result);
        $this->assertStringContainsString('#', $result);
    }

    public function testDateTimeInterfaceReturnsFormattedDate(): void
    {
        $date = new \DateTimeImmutable('2024-01-15 10:30:00');
        $result = $this->processor->createDisplayDetails($date);
        $this->assertSame('15-01-2024 10:30:00', $result);
    }

    public function testStringableReturnsStringValue(): void
    {
        $stringable = new class implements \Stringable {
            public function __toString(): string
            {
                return 'hello';
            }
        };
        $this->assertSame('hello', $this->processor->createDisplayDetails($stringable));
    }

    public function testGenericObjectReturnsClassName(): void
    {
        $obj = new \stdClass();
        $this->assertSame('stdClass', $this->processor->createDisplayDetails($obj));
    }

    public function testStringReturnsAsIs(): void
    {
        $this->assertSame('foo', $this->processor->createDisplayDetails('foo'));
    }

    public function testIntegerReturnsAsString(): void
    {
        $this->assertSame('42', $this->processor->createDisplayDetails(42));
    }

    public function testBoolTrueReturnsTrue(): void
    {
        $this->assertSame('True', $this->processor->createDisplayDetails(true));
    }

    public function testBoolFalseReturnsFalse(): void
    {
        $this->assertSame('False', $this->processor->createDisplayDetails(false));
    }

    public function testArrayOfStringsJoinedWithPipe(): void
    {
        $this->assertSame('a | b | c', $this->processor->createDisplayDetails(['a', 'b', 'c']));
    }

    public function testEmptyArrayReturnsEmpty(): void
    {
        $this->assertSame('', $this->processor->createDisplayDetails([]));
    }

    public function testIsUpdatedReturnsFalseForSameValues(): void
    {
        $this->assertFalse($this->processor->isUpdated(['foo', 'foo']));
    }

    public function testIsUpdatedReturnsTrueForDifferentValues(): void
    {
        $this->assertTrue($this->processor->isUpdated(['foo', 'bar']));
    }

    public function testIsUpdatedReturnsFalseForNumericEquality(): void
    {
        $this->assertFalse($this->processor->isUpdated(['1.0', '1']));
    }

    public function testIsUpdatedReturnsFalseForPersistentCollection(): void
    {
        $collection = $this->createStub(Collection::class);
        $this->assertFalse($this->processor->isUpdated($collection));
    }

    public function testArrayOfEnumsJoinedWithPipe(): void
    {
        $result = $this->processor->createDisplayDetails([FakeStatusEnum::OPEN, FakeStatusEnum::RESOLVED]);
        $this->assertStringContainsString('open', $result);
        $this->assertStringContainsString('resolved', $result);
        $this->assertStringContainsString(' | ', $result);
    }

    public function testIsUpdatedReturnsTrueForNumericInequality(): void
    {
        $this->assertTrue($this->processor->isUpdated(['1.0', '2.0']));
    }

    public function testGetIdUnwrapsDoctrineProxyClassName(): void
    {
        $proxy = new class extends FakeAuditableEntity implements Proxy {
            public function __load(): void
            {
            }

            public function __isInitialized(): bool
            {
                return true;
            }
        };

        $result = $this->processor->createDisplayDetails($proxy);

        $this->assertSame(sprintf('%s#abc-123', FakeAuditableEntity::class), $result);
        $this->assertStringNotContainsString('__CG__', $result);
    }

    public function testResourceReturnsEmptyString(): void
    {
        $resource = fopen('php://memory', 'r');
        $result = $this->processor->createDisplayDetails($resource);
        fclose($resource);
        $this->assertSame('', $result);
    }
}

class FakeAuditableEntity
{
    public function getId(): ?string
    {
        return 'abc-123';
    }
}

enum FakeStatusEnum: string
{
    case OPEN = 'open';
    case RESOLVED = 'resolved';
}
