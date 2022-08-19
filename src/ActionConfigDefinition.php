<?php

declare(strict_types=1);

namespace NoCodeDbtTransformation;

use Keboola\Component\Config\BaseConfigDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class ActionConfigDefinition extends BaseConfigDefinition
{
    protected function getParametersDefinition(): ArrayNodeDefinition
    {
        $parametersNode = parent::getParametersDefinition();
        // @formatter:off
        /** @noinspection NullPointerExceptionInspection */
        $parametersNode
            ->isRequired()
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
                        ->scalarNode('host')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('warehouse')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('database')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('schema')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('user')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('password')->isRequired()->cannotBeEmpty()->end()
                    ->end()
                ->end()
            ->end();

        // @formatter:on
        return $parametersNode;
    }
}
