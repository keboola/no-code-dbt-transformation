<?php

declare(strict_types=1);

namespace NoCodeDbtTransformation;

use Keboola\Component\Config\BaseConfigDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class ConfigDefinition extends BaseConfigDefinition
{
    protected function getParametersDefinition(): ArrayNodeDefinition
    {
        $parametersNode = parent::getParametersDefinition();
        // @formatter:off
        /** @noinspection NullPointerExceptionInspection */
        $parametersNode
            ->isRequired()
            ->children()
                ->scalarNode('tableName')->cannotBeEmpty()->end()
            ->end()
            ->children()
                ->scalarNode('bucketId')->cannotBeEmpty()->end()
            ->end()
            ->children()
                ->arrayNode('models')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->prototype('scalar')
                        ->cannotBeEmpty()
                ->end()
            ->end()
                ->arrayNode('authorization')
                    ->children()
                        ->scalarNode('host')->end()
                        ->scalarNode('warehouse')->end()
                        ->scalarNode('database')->end()
                        ->scalarNode('schema')->end()
                        ->scalarNode('user')->end()
                        ->scalarNode('#password')->end()
                    ->end()
                ->end()
            ->end();

        // only for UI for saving form state
        $parametersNode
            ->children()
                ->arrayNode('tables')
                    ->ignoreExtraKeys()
                ->end()
            ->end();

        $parametersNode
            ->children()
                ->arrayNode('operations')
                    ->ignoreExtraKeys()
                ->end()
            ->end();
        // @formatter:on
        return $parametersNode;
    }
}
