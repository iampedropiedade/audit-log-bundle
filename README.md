# audit-log-bundle

Generic Doctrine entity change-tracking (audit log) for Symfony apps. Mark an entity
`#[Auditable]`, and every insert/update/delete gets a change-diff row - who did it, when,
from what IP, on what route, and a from/to summary of every changed field.

Not coupled to any specific `User` entity or `AuditLog` table shape - both are supplied by
the consuming app.

## Installation

```json
{
    "repositories": [
        {"type": "vcs", "url": "https://github.com/iampedropiedade/audit-log-bundle"}
    ],
    "require": {
        "iampedropiedade/audit-log-bundle": "^1.0"
    }
}
```

```php
// config/bundles.php
Pedropiedade\AuditLogBundle\AuditLogBundle::class => ['all' => true],
```

## Configuration

```yaml
# config/packages/audit_log.yaml
audit_log:
    user_class: App\Entity\User # must implement Symfony\Component\Security\Core\User\UserInterface
    # default_ignored_attributes: [id, uuid, password, ...] # optional, see DependencyInjection/Configuration.php for the built-in default
```

## Your own concrete entity, repository and factory

The bundle ships `AbstractAuditLog` (a Doctrine `#[ORM\MappedSuperclass]`) and
`AbstractAuditLogRepository` - your app owns the concrete table/class:

```php
// src/Entity/AuditLog.php
#[ORM\Entity(repositoryClass: AuditLogRepository::class)]
class AuditLog extends Pedropiedade\AuditLogBundle\Entity\AbstractAuditLog
{
}
```

```php
// src/Repository/AuditLogRepository.php
class AuditLogRepository extends Pedropiedade\AuditLogBundle\Repository\AbstractAuditLogRepository
    implements Pedropiedade\AuditLogBundle\Repository\AuditLogRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditLog::class);
    }
}
```

```php
// src/Service/AuditLog/AuditLogEntryFactory.php
class AuditLogEntryFactory implements Pedropiedade\AuditLogBundle\AuditLogEntryFactoryInterface
{
    public function create(): Pedropiedade\AuditLogBundle\Entity\AbstractAuditLog
    {
        return new AuditLog();
    }
}
```

This last class is autowired automatically as the single implementation of
`AuditLogEntryFactoryInterface` - no extra service config needed as long as your app
autowires/autoconfigures its own `App\` services (the Symfony Flex default).

## Usage

```php
#[ORM\Entity]
#[Auditable] // or #[Auditable(ignoredAttributes: [...])] / #[Auditable(trackedAttributes: [...])]
class Issue
{
    // ...
}
```

Reading the log back is up to the consuming app - inject
`Pedropiedade\AuditLogBundle\Repository\AuditLogRepositoryInterface` and use
`findForQueryQb(AuditLogQuery)` / `findDeletedForEntityQb(string $entityFqcn)`.
