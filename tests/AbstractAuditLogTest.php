<?php

declare(strict_types=1);

namespace Pedropiedade\AuditLogBundle\Tests;

use Pedropiedade\AuditLogBundle\Tests\Fixtures\ConcreteAuditLog;
use Pedropiedade\AuditLogBundle\Tests\Fixtures\DummyUser;
use PHPUnit\Framework\TestCase;

class AbstractAuditLogTest extends TestCase
{
    private ConcreteAuditLog $log;

    protected function setUp(): void
    {
        $this->log = new ConcreteAuditLog();
    }

    public function testCreatedAtIsSetOnConstruction(): void
    {
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->log->getCreatedAt());
    }

    public function testDefaultEventDataIsEmpty(): void
    {
        $this->assertSame([], $this->log->getEventData());
    }

    public function testDefaultEntityIdIsNull(): void
    {
        $this->assertNull($this->log->getEntityId());
    }

    public function testDefaultUserIsNull(): void
    {
        $this->assertNull($this->log->getUser());
    }

    public function testDefaultRequestRouteIsNull(): void
    {
        $this->assertNull($this->log->getRequestRoute());
    }

    public function testSetEntityFqcnReturnsSelf(): void
    {
        $result = $this->log->setEntityFqcn('App\\Entity\\Issue');
        $this->assertSame($this->log, $result);
        $this->assertSame('App\\Entity\\Issue', $this->log->getEntityFqcn());
    }

    public function testSetEntityIdReturnsSelf(): void
    {
        $result = $this->log->setEntityId('entity-uuid');
        $this->assertSame($this->log, $result);
        $this->assertSame('entity-uuid', $this->log->getEntityId());
    }

    public function testSetActionReturnsSelf(): void
    {
        $result = $this->log->setAction('insert');
        $this->assertSame($this->log, $result);
        $this->assertSame('insert', $this->log->getAction());
    }

    public function testSetIpAddressReturnsSelf(): void
    {
        $result = $this->log->setIpAddress('192.168.1.100');
        $this->assertSame($this->log, $result);
        $this->assertSame('192.168.1.100', $this->log->getIpAddress());
    }

    public function testSetIpAddressWithNullFallsBackToPlaceholder(): void
    {
        $this->log->setIpAddress(null);
        $this->assertSame('[UNKNOWN/CLI/WEBHOOK]', $this->log->getIpAddress());
    }

    public function testSetRequestRouteReturnsSelf(): void
    {
        $result = $this->log->setRequestRoute('app_issue_create');
        $this->assertSame($this->log, $result);
        $this->assertSame('app_issue_create', $this->log->getRequestRoute());
    }

    public function testSetEventDataReturnsSelf(): void
    {
        $data = ['changeSet' => ['status' => ['open', 'accepted']]];
        $result = $this->log->setEventData($data);
        $this->assertSame($this->log, $result);
        $this->assertSame($data, $this->log->getEventData());
    }

    public function testSetUserReturnsSelfAndAcceptsAnyUserInterfaceImplementation(): void
    {
        $user = new DummyUser();
        $result = $this->log->setUser($user);
        $this->assertSame($this->log, $result);
        $this->assertSame($user, $this->log->getUser());
    }
}
