<?php
namespace Fungio\GoogleCalendarBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * Class FungioGoogleCalendarExtension
 * @package Fungio\GoogleCalendarBundle\DependencyInjection
 *
 * @author Pierrick AUBIN <pierrick.aubin@siqual.fr>
 */
class FungioGoogleCalendarExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $this->processConfiguration($configuration, $configs);
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
    }
}