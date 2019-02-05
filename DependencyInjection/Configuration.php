<?php
namespace Fungio\GoogleCalendarBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 * @package Fungio\GoogleCalendarBundle\DependencyInjection
 *
 * @author Pierrick AUBIN <fungio76@gmail.com>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('fungio_google_calendar');
        $rootNode = $treeBuilder->root('fungio_google_calendar');
        $rootNode
            ->children()
                ->arrayNode('google_calendar')->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('application_name')->end()
                        ->scalarNode('credentials_path')->end()
                        ->scalarNode('client_secret_path')->end()
                    ->end()
                ->end()
            ->end()
        ;
        return $treeBuilder;
    }
}
