<?php

declare(strict_types=1);

namespace Pedropiedade\AuditLogBundle\Tests\Fixtures;

use Symfony\Component\Security\Core\User\UserInterface;

final class DummyUser implements UserInterface
{
    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return 'dummy-user';
    }
}
