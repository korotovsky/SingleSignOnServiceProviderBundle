<?php

namespace Krtv\Bundle\SingleSignOnServiceProviderBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Krtv\Bundle\SingleSignOnServiceProviderBundle\Factory\SingleSignOnFactory;
use Krtv\Bundle\SingleSignOnServiceProviderBundle\DependencyInjection\Compiler\AddEncoderSecretPass;
use Krtv\Bundle\SingleSignOnServiceProviderBundle\DependencyInjection\Compiler\AddUriSignerSecretPass;

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

        $container->addCompilerPass(new AddEncoderSecretPass());
        $container->addCompilerPass(new AddUriSignerSecretPass());

        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new SingleSignOnFactory());
    }
}