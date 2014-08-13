<?php

namespace Krtv\Bundle\SingleSignOnServiceProviderBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Class AddEncoderSecretPass
 * @package Krtv\Bundle\SingleSignOnServiceProviderBundle\DependencyInjection\Compiler
 */
class AddEncoderSecretPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('krtv_single_sign_on_service_provider.security.authentication.encoder')) {
            return;
        }

        $parameter = $container->getParameter('krtv_single_sign_on_service_provider.secret_parameter');

        $container->getDefinition('krtv_single_sign_on_service_provider.security.authentication.encoder')
            ->replaceArgument(0, $container->getParameter($parameter));
    }
}