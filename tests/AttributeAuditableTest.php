<?php

declare(strict_types=1);

namespace Pedropiedade\AuditLogBundle\Tests;

use Pedropiedade\AuditLogBundle\Attribute\Auditable;
use PHPUnit\Framework\TestCase;

class AttributeAuditableTest extends TestCase
{
    public function testDefaultEventsIncludeAllActions(): void
    {
        $attr = new Auditable();
        $this->assertContains(Auditable::ACTION_INSERT, $attr->auditEvents);
        $this->assertContains(Auditable::ACTION_UPDATE, $attr->auditEvents);
        $this->assertContains(Auditable::ACTION_DELETE, $attr->auditEvents);
    }

    public function testDefaultsAreNull(): void
    {
        $attr = new Auditable();
        $this->assertNull($attr->ignoredAttributes);
        $this->assertNull($attr->trackedAttributes);
    }

    public function testIgnoredAttributesCanBeSet(): void
    {
        $attr = new Auditable(ignoredAttributes: ['password', 'token']);
        $this->assertSame(['password', 'token'], $attr->ignoredAttributes);
        $this->assertNull($attr->trackedAttributes);
    }

    public function testTrackedAttributesCanBeSet(): void
    {
        $attr = new Auditable(trackedAttributes: ['email', 'name']);
        $this->assertSame(['email', 'name'], $attr->trackedAttributes);
        $this->assertNull($attr->ignoredAttributes);
    }

    public function testCustomAuditEvents(): void
    {
        $attr = new Auditable(auditEvents: [Auditable::ACTION_UPDATE]);
        $this->assertSame([Auditable::ACTION_UPDATE], $attr->auditEvents);
    }

    public function testThrowsWhenBothIgnoredAndTrackedProvided(): void
    {
        $this->expectException(\Exception::class);
        new Auditable(
            ignoredAttributes: ['password'],
            trackedAttributes: ['email'],
        );
    }
}
