<?php

namespace Krtv\Bundle\SingleSignOnServiceProviderBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 * @package Krtv\Bundle\SingleSignOnServiceProviderBundle\DependencyInjection
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();

        $builder->root('krtv_single_sign_on_service_provider')
            ->children()
                ->scalarNode('host')
                    ->isRequired()
                    ->validate()
                        ->ifTrue(function($v) {
                            return preg_match('/^http(s?):\/\//', $v);
                        })
                        ->thenInvalid('SSO host must only contain the host, and not the url scheme, eg: idp.domain.com')
                    ->end()
                ->end()

                ->scalarNode('host_scheme')
                    ->defaultValue('http')
                ->end()

                ->scalarNode('login_path')
                    ->isRequired()
                ->end()

                ->arrayNode('otp_manager')
                    ->addDefaultsIfNotSet()
                    ->info('Configuration for OTP managers')
                    ->children()
                        ->scalarNode('name')
                            ->defaultValue('orm')
                        ->end()

                        ->arrayNode('managers')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->arrayNode('orm')
                                    ->addDefaultsIfNotSet()
                                    ->info('ORM OTP configuration')
                                    ->children()
                                    ->end()
                                ->end()
                                ->arrayNode('http')
                                    ->addDefaultsIfNotSet()
                                    ->info('HTTP OTP configuration')
                                    ->children()
                                        ->scalarNode('provider')
                                            ->info('Active provider for HTTP OTP manager')
                                            ->defaultValue('guzzle')
                                        ->end()
                                        ->arrayNode('providers')
                                            ->addDefaultsIfNotSet()
                                            ->info('Available HTTP providers')
                                            ->children()
                                                ->arrayNode('guzzle')
                                                    ->addDefaultsIfNotSet()
                                                    ->children()
                                                        ->scalarNode('client')
                                                            ->info('Guzzle client service id')
                                                        ->end()
                                                        ->scalarNode('resource')
                                                            ->info('Url for fetch/invalidate OTPs')
                                                        ->end()
                                                    ->end()
                                                ->end()
                                                ->arrayNode('service')
                                                    ->addDefaultsIfNotSet()
                                                        ->children()
                                                            ->scalarNode('id')
                                                            ->info('Service id')
                                                        ->end()
                                                    ->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()

                ->scalarNode('otp_parameter')
                    ->defaultValue('_otp')
                ->end()

                ->scalarNode('secret_parameter')
                    ->defaultValue('secret')
                ->end()
            ->end()
        ;

        return $builder;
    }
}
