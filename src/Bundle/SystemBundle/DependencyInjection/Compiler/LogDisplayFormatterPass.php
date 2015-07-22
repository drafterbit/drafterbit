<?php

namespace Drafterbit\Bundle\SystemBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class LogDisplayFormatterPass implements CompilerPassInterface {

    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('drafterbit_system.log.display_formatter')) {
            return;
        }

        $definition = $container->getDefinition(
            'drafterbit_system.log.display_formatter'
        );

        $taggedServices = $container->findTaggedServiceIds(
            'drafterbit_system_log.display_formatter'
        );

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall(
                'addEntityFormatter',
                array(new Reference($id))
            );
        }
    }
}