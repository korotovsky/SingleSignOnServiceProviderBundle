<?php

namespace Krtv\Bundle\SingleSignOnServiceProviderBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Class KrtvSingleSignOnServiceProviderExtension
 * @package Krtv\Bundle\SingleSignOnServiceProviderBundle\DependencyInjection
 */
class KrtvSingleSignOnServiceProviderExtension extends Extension
{
    /**
     * @param array $configs
     * @param ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $processor = new Processor();
        $configuration = new Configuration();
        $config = $processor->processConfiguration($configuration, $configs);

        // add config parameters to container
        $prefix = $this->getAlias() . '.';
        foreach ($config as $name => $value) {
            $container->setParameter($prefix . $name, $value);
        }

        // load services
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $otpManagerConfig = $config['otp_manager'];
        $otpManagerName = $otpManagerConfig['name'];

        $loader->load('managers/' . $otpManagerName . '.xml');
        if ($otpManagerName === 'http') {
            $providerName = $otpManagerConfig['managers'][$otpManagerName]['provider'];
            $providerConfig = $otpManagerConfig['managers'][$otpManagerName]['providers'][$providerName];

            $otpManagerId = sprintf('krtv_single_sign_on_service_provider.security.authentication.otp_manager.%s', $otpManagerName);
            $providerId = sprintf('krtv_single_sign_on_service_provider.security.authentication.otp_manager.%s.provider.%s', $otpManagerName, $providerName);

            $otpManagerDefinition = $container->getDefinition($otpManagerId);
            $otpManagerDefinition->replaceArgument(0, new Reference($providerId));

            switch ($providerName) {
                case 'guzzle':
                    $providerDefinition = $container->getDefinition($providerId);
                    $providerDefinition->replaceArgument(0, new Reference($providerConfig['client']));
                    $providerDefinition->replaceArgument(1, $providerConfig['resource']);

                    break;
                case 'service':
                    $container->setAlias($providerId, new Alias($providerConfig['id']));

                    break;
                default:
                    throw new RuntimeException(sprintf('Unsupported HTTP provider %s', $providerName));
            }

        }

        $loader->load('services.xml');

        // Set alias for OTP
        $container->setAlias('krtv_single_sign_on_service_provider.security.authentication.manager.otp', new Alias('krtv_single_sign_on_service_provider.security.authentication.otp_manager.' . $otpManagerName));
        $container->setAlias('sso_service_provider.otp_manager', new Alias('krtv_single_sign_on_service_provider.security.authentication.manager.otp'));

        // Set alias for encoder
        $container->setAlias('sso_service_provider.encoder', new Alias('krtv_single_sign_on_service_provider.security.authentication.encoder'));

        // Set alias for uri_signer
        $container->setAlias('sso_service_provider.uri_signer', new Alias('krtv_single_sign_on_service_provider.uri_signer'));

        $authenticationProviderDefinition = $container->getDefinition('krtv_single_sign_on_service_provider.security.authentication.provider');
        $authenticationProviderDefinition->replaceArgument(1, new Reference('krtv_single_sign_on_service_provider.security.authentication.manager.otp'));
    }

    public function getAlias()
    {
        return 'krtv_single_sign_on_service_provider';
    }
}