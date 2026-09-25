<?php

declare(strict_types=1);

namespace Pedropiedade\AuditLogBundle\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class Auditable
{
    public const ACTION_INSERT = 'insert';
    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';

    /**
     * @param array<string> $ignoredAttributes
     * @param array<string> $trackedAttributes
     * @param array<string> $auditEvents
     *
     * @throws \Exception
     */
    public function __construct(
        public ?array $ignoredAttributes = null,
        public ?array $trackedAttributes = null,
        public array $auditEvents = [
            self::ACTION_INSERT,
            self::ACTION_UPDATE,
            self::ACTION_DELETE,
        ],
    ) {
        if (null !== $ignoredAttributes && null !== $trackedAttributes) {
            throw new \Exception('You need to select either tracked attributes or ignored attributes but not both.');
        }
    }
}
