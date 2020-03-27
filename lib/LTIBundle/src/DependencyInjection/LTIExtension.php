<?php

namespace Elw\LTIBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Class LTIExtension
 * @package Elw\LTIBundle\DependencyInjection
 */
class LTIExtension extends Extension
{
    /**
     * @param array $configs
     * @param ContainerBuilder $container
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );
        $loader->load('services.xml');
    }

    /**
     * @param array $config
     * @param ContainerBuilder $container
     * @return object|\Symfony\Component\Config\Definition\ConfigurationInterface|Configuration|null
     */
    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration();
    }
}
