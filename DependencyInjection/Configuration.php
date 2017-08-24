<?php

namespace Sopinet\ChatBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('sopinet_chat');

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.
        $defaultTypeMessage = array();

        $defaultTypeMessage['text'] = array();
        $defaultTypeMessage['text']['enabled'] = true;
        $defaultTypeMessage['text']['interfaceEnabled'] = true;
        $defaultTypeMessage['text']['class'] = "Sopinet\ChatBundle\Entity\MessageText";

        $defaultTypeMessage['image'] = array();
        $defaultTypeMessage['image']['enabled'] = true;
        $defaultTypeMessage['image']['interfaceEnabled'] = true;
        $defaultTypeMessage['image']['class'] = "Sopinet\ChatBundle\Entity\MessageImage";

        $defaultTypeMessage['received'] = array();
        $defaultTypeMessage['received']['enabled'] = true;
        $defaultTypeMessage['received']['interfaceEnabled'] = true;
        $defaultTypeMessage['received']['class'] = "Sopinet\ChatBundle\Entity\MessageReceived";

        $defaultTypeMessage['userState'] = array();
        $defaultTypeMessage['userState']['enabled'] = true;
        $defaultTypeMessage['userState']['interfaceEnabled'] = false; // No se puede usar desde interface
        $defaultTypeMessage['userState']['class'] = "Sopinet\ChatBundle\Entity\MessageUserState";

        $rootNode
                ->children()
                    ->booleanNode('disabled')->defaultFalse()->end()
                    ->booleanNode('anyType')->defaultFalse()->end()
                    ->booleanNode('enabledAndroid')->defaultTrue()->end()
                    ->booleanNode('enabledIOS')->defaultTrue()->end()
		    ->booleanNode('enabledWeb')->defaultFalse()->end()
                    ->booleanNode('background')->defaultFalse()->end()
                    ->scalarNode('soundIOS')->defaultValue('default')->end()
                    ->arrayNode('extra_type_chat')
                        ->prototype('array')
                            ->children()
                                ->booleanNode('enabled')->defaultTrue()->end()
                                ->scalarNode('class')->end()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('basic_type_message')
                        ->defaultValue($defaultTypeMessage)
                        ->prototype('array')
                            ->children()
                                ->booleanNode('enabled')->defaultTrue()->end()
                                ->booleanNode('interfaceEnabled')->defaultTrue()->end()
                                ->scalarNode('class')->end()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('extra_type_message')
                        ->prototype('array')
                            ->children()
                                ->booleanNode('enabled')->defaultTrue()->end()
                                ->booleanNode('interfaceEnabled')->defaultTrue()->end()
                                ->scalarNode('class')->end()
                            ->end()
                        ->end()
                    ->end();

        return $treeBuilder;
    }
}
