<?php

namespace Albegali\DoctrineFormSerializer\Configuration;

use Albegali\DoctrineFormSerializer\Guesser\SerializedTypeGuesser;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class FormConfiguration implements ConfigurationInterface
{
    /** @var array */
    public static $htmlFields = [
        'button' => 'button',
        'checkbox' => 'input',
        'color' => 'input',
        'date' => 'input',
        'email' => 'input',
        'file' => 'input',
        'hidden' => 'input',
        'number' => 'input',
        'password' => 'input',
        'radio' => 'input',
        'range' => 'input',
        'reset' => 'input',
        'search' => 'input',
        'select' => 'select',
        'submit' => 'input',
        'tel' => 'input',
        'text' => 'input',
        'textarea' => 'textarea',
        'time' => 'input',
        'url' => 'input',
    ];

    public static $serializeStrategyCamelCase = 'camelCase';
    public static $serializeStrategysnake_case = 'snake_case';

    /** @var array */
    private static $extraHtmlFields = [];

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('form');

        $rootNode
            ->isRequired()
            ->children()
                ->scalarNode('label')->isRequired()->end()
                ->scalarNode('name')->isRequired()->end()
                ->scalarNode('entity')->isRequired()->end()
                ->scalarNode('serializeStrategy')->defaultValue('snake_case')->end()
                ->booleanNode('enabled')->defaultTrue()->end()
                ->arrayNode('blocks')
                    ->useAttributeAsKey('key')
                    ->normalizeKeys(false)
                    ->prototype('array')
                        ->children()
                            ->scalarNode('label')->end()
                            ->append($this->getFieldsNode())
                            ->append($this->getDependenciesNode())
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

    protected function getFieldsNode()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('fields');
        $rootNode
            ->prototype('array')
                ->children()
                    ->scalarNode('mapped')->defaultTrue()->end()
                    ->scalarNode('insertBefore')->end()
                    ->scalarNode('insertAfter')->end()
                    ->scalarNode('bindWith')->end()
                    ->enumNode('field')
                        ->values(SerializedTypeGuesser::$htmlInputTags)
                    ->end()
                    ->enumNode('type')
                        ->values(SerializedTypeGuesser::$htmlInputTypes)
                    ->end()
                    ->variableNode('default')->defaultNull()->end()
                    ->arrayNode('choices')
                        ->normalizeKeys(false)
                        ->children()
                            ->enumNode('type')
                                ->isRequired()
                                ->values(['ws', 'service', 'array'])
                            ->end()
                            ->variableNode('data')->isRequired()->end()
                        ->end()
                    ->end()
                    ->arrayNode('options')->prototype('variable')->end()->end()
                    ->arrayNode('attributes')->normalizeKeys(false)->prototype('scalar')->end()->end()
                    ->append($this->getDependenciesNode())
                ->end()
            ->end()
        ;

        return $rootNode;
    }

    protected function getDependenciesNode()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('depends_on');

        $rootNode
            ->prototype('array')
                ->children()
                    ->scalarNode('field')->isRequired()->end()
                    ->scalarNode('action')->isRequired()->end()
                    ->scalarNode('when')->isRequired()->end()
                    ->arrayNode('values')
                        ->prototype('scalar')->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $rootNode;
    }
}
