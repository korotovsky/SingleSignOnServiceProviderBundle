<?php

namespace Krtv\Bundle\SingleSignOnServiceProviderBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Krtv\Bundle\SingleSignOnServiceProviderBundle\DependencyInjection\Compiler\ResolveSecretPass;
use Krtv\Bundle\SingleSignOnServiceProviderBundle\Factory\SingleSignOnFactory;

/**
 * Class KrtvSingleSignOnServiceProviderBundle
 * @package Krtv\Bundle\SingleSignOnServiceProviderBundle
 */
class KrtvSingleSignOnServiceProviderBundle extends Bundle
{
    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ResolveSecretPass());

        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new SingleSignOnFactory());
    }
}