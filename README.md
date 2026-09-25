# audit-log-bundle

Generic Doctrine entity change-tracking (audit log) for Symfony apps. Mark an entity
`#[Auditable]`, and every insert/update/delete gets a change-diff row - who did it, when,
from what IP, on what route, and a from/to summary of every changed field.

Not coupled to any specific `User` entity or `AuditLog` table shape - both are supplied by
the consuming app.

## Installation

### 1. Require the package

Not on Packagist - add it as a VCS repository:

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

Then `composer require iampedropiedade/audit-log-bundle`.

### 2. Register the bundle

If your app uses Symfony Flex, this happens automatically (Flex generates a trivial recipe
for any package containing a `Bundle` class and adds it to `config/bundles.php` for you -
check `symfony.lock`/`config/bundles.php` after installing to confirm). Without Flex, add it
yourself:

```php
// config/bundles.php
Pedropiedade\AuditLogBundle\AuditLogBundle::class => ['all' => true],
```

### 3. Configure it

```yaml
# config/packages/audit_log.yaml
audit_log:
    user_class: App\Entity\User # must implement Symfony\Component\Security\Core\User\UserInterface
    # default_ignored_attributes: [id, uuid, password, ...] # optional, see DependencyInjection/Configuration.php for the built-in default
```

### 4. Define your own concrete entity, repository and factory

The bundle ships `AbstractAuditLog` (a Doctrine `#[ORM\MappedSuperclass]`, not a concrete
`#[ORM\Entity]`) and `AbstractAuditLogRepository` - your app owns the concrete table/class,
same as any other entity in your app:

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

### 5. Alias the two bundle interfaces to your classes

Symfony's "alias an interface to its sole implementation" autowiring convenience works
automatically for interfaces under your own `src/`, but **not** for interfaces owned by a
vendor package - add these two explicit aliases or `cache:clear` will fail with "Cannot
autowire service ... but this type has been excluded" / "no such service exists":

```yaml
# config/services.yaml
services:
    Pedropiedade\AuditLogBundle\Repository\AuditLogRepositoryInterface: '@App\Repository\AuditLogRepository'
    Pedropiedade\AuditLogBundle\AuditLogEntryFactoryInterface: '@App\Service\AuditLog\AuditLogEntryFactory'
```

### 6. Generate the migration

The bundle can't ship one - `AbstractAuditLog` is a `MappedSuperclass` with no table of its
own, so there's nothing to migrate until your app registers its own concrete entity (step 4).
Once that's done, this is the normal Doctrine workflow for adding any new entity, nothing
special to this package:

```bash
bin/console doctrine:migrations:diff
```

Review the generated migration, then run it (`bin/console doctrine:migrations:migrate`) same
as any other.

### 7. Mark your entities `#[Auditable]`

```php
#[ORM\Entity]
#[Auditable] // or #[Auditable(ignoredAttributes: [...])] / #[Auditable(trackedAttributes: [...])]
class Issue
{
    // ...
}
```

## Usage

Reading the log back is up to the consuming app - inject
`Pedropiedade\AuditLogBundle\Repository\AuditLogRepositoryInterface` and use
`findForQueryQb(AuditLogQuery)` / `findDeletedForEntityQb(string $entityFqcn)`.
