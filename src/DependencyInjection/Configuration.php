<?php

declare(strict_types=1);

namespace Pedropiedade\AuditLogBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('audit_log');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('user_class')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->info('FQCN of the app\'s own User entity (must implement Symfony\Component\Security\Core\User\UserInterface) - resolved via doctrine.orm.resolve_target_entities.')
                ->end()
                ->arrayNode('default_ignored_attributes')
                    ->info('Entity fields never included in a change diff unless overridden per-entity via #[Auditable(trackedAttributes: ...)].')
                    ->scalarPrototype()->end()
                    ->defaultValue([
                        'id',
                        'uuid',
                        'password',
                        'userIdentifier',
                        'sortPosition',
                        'createdAt',
                        '__initializer__',
                        '__cloner__',
                        '__isInitialized__',
                    ])
                ->end()
            ->end();

        return $treeBuilder;
    }
}
