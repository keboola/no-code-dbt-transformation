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
                ->scalarNode('bucketId')->cannotBeEmpty()->end()
                ->arrayNode('models')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->arrayPrototype()
                        ->ignoreExtraKeys()
                        ->children()
                            ->scalarNode('code')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                    ->end()
                ->end()
            ->end()
                ->arrayNode('authorization')
                    ->ignoreExtraKeys()
                    ->children()
                        ->scalarNode('host')->end()
                        ->scalarNode('warehouse')->end()
                        ->scalarNode('database')->end()
                        ->scalarNode('schema')->end()
                        ->scalarNode('user')->end()
                        ->scalarNode('#password')->end()
                        ->scalarNode('#privateKey')->end()
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
        // @formatter:on
        return $parametersNode;
    }
}
