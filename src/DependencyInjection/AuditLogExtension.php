<?php

declare(strict_types=1);

namespace Pedropiedade\AuditLogBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Security\Core\User\UserInterface;

class AuditLogExtension extends Extension implements PrependExtensionInterface
{
    /**
     * @param array<int, array<string, mixed>> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter('audit_log.user_class', $config['user_class']);
        $container->setParameter('audit_log.default_ignored_attributes', $config['default_ignored_attributes']);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');
    }

    /**
     * Registers doctrine.orm.resolve_target_entities so the bundle's
     * AbstractAuditLog::$user relation (mapped against UserInterface)
     * resolves to the app's own concrete User class. Runs before load()
     * on every extension, which is why the config needs processing again
     * here rather than reading the parameter set in load().
     */
    public function prepend(ContainerBuilder $container): void
    {
        $configs = $container->getExtensionConfig($this->getAlias());
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->prependExtensionConfig('doctrine', [
            'orm' => [
                'resolve_target_entities' => [
                    UserInterface::class => $config['user_class'],
                ],
            ],
        ]);
    }

    public function getAlias(): string
    {
        return 'audit_log';
    }
}
