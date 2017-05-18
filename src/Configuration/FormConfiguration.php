<?php

namespace Albegali\DoctrineFormSerializer\Configuration;

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
                ->booleanNode('enabled')->defaultTrue()->end()
                ->arrayNode('fields')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('property')->isRequired()->end()
                            ->scalarNode('mapped')->defaultTrue()->end()
                            ->enumNode('field')
                                ->values(self::$htmlFields)
                            ->end()
//                            ->enumNode('type')
//                                ->values($formTypes)
//                            ->end()
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
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
//
//    protected function getDependenciesNode()
//    {
//        $treeBuilder = new TreeBuilder();
//        $rootNode = $treeBuilder->root('depends_on');
//
//        $rootNode
//            ->prototype('array')
//                ->children()
//                    ->scalarNode('field')->isRequired()->end()
//                    ->arrayNode('values')
//                        ->isRequired()
//                        ->prototype('scalar')->end()
//                    ->end()
//                ->end()
//            ->end()
//        ;
//
//        return $rootNode;
//    }
}