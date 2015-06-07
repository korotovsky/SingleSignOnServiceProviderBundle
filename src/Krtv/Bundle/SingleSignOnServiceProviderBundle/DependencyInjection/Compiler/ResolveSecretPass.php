<?php

namespace Krtv\Bundle\SingleSignOnServiceProviderBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Class ResolveSecretPass
 * @package Krtv\Bundle\SingleSignOnServiceProviderBundle\DependencyInjection\Compiler
 */
class ResolveSecretPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $parameter = $container->getParameter('krtv_single_sign_on_service_provider.secret_parameter');

        $container->getDefinition('krtv_single_sign_on_service_provider.security.authentication.encoder')
            ->replaceArgument(0, $container->getParameter($parameter));

        $container->getDefinition('krtv_single_sign_on_service_provider.uri_signer')
            ->replaceArgument(0, $container->getParameter($parameter));
    }
}
