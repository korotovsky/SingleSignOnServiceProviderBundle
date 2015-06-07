<?php

namespace Krtv\Bundle\SingleSignOnServiceProviderBunde\Tests\DependencyInjection;

use Krtv\Bundle\SingleSignOnServiceProviderBundle\DependencyInjection\KrtvSingleSignOnServiceProviderExtension;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * Class KrtvSingleSignOnServiceProviderExtensionTest
 * @package Krtv\Bundle\SingleSignOnServiceProviderBunde\Tests\DependencyInjection
 */
class KrtvSingleSignOnServiceProviderExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     */
    public function testLoad()
    {
        $containerMock = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->enableProxyingToOriginalMethods()
            ->disableOriginalConstructor()
            ->setConstructorArgs(array(
                new ParameterBag()
            ))
            ->getMock();

        $configs = array(
            array(
                'host' => 'idp.example.com',
                'host_scheme' => 'https',
                'login_path' => '/sso/login/',
                'otp_manager' => array(
                    'name' => 'http',
                    'managers' => array(
                        'http' => array(
                            'provider' => 'service',
                            'providers' => array(
                                'service' => array(
                                    'id' => 'acme_bundle.your_own_fetch_service.id',
                                ),
                                'guzzle' => array(
                                    'client' => 'acme_bundle.guzzle_service.id',
                                    'resource' => 'http://idp.example.com/internal/v1/sso',
                                ),
                            ),
                        ),
                    ),
                ),
                'otp_parameter' => '_otp',
                'secret_parameter' => 'secret'
            )
        );

        $extension = new KrtvSingleSignOnServiceProviderExtension();
        $extension->load($configs, $containerMock);

        $services = array(
            'krtv_single_sign_on_service_provider.security.authentication.otp_manager.http',
            'krtv_single_sign_on_service_provider.security.authentication.otp_manager.http.provider.guzzle',
            'krtv_single_sign_on_service_provider.security.authentication.factory',
            'krtv_single_sign_on_service_provider.security.authentication.encoder',
            'krtv_single_sign_on_service_provider.security.authentication.entry_point',
            'krtv_single_sign_on_service_provider.security.authentication.provider',
            'krtv_single_sign_on_service_provider.security.authentication.listener',
            'krtv_single_sign_on_service_provider.authentication.handler.authentication_failure.abstract',
            'krtv_single_sign_on_service_provider.uri_signer',
        );

        foreach ($services as $service) {
            $this->assertInstanceOf('Symfony\Component\DependencyInjection\Definition', $containerMock->getDefinition($service));
        }

        $this->assertCount(count($services), $containerMock->getDefinitions());

        $aliases = array(
            'krtv_single_sign_on_service_provider.security.authentication.otp_manager.http.provider.service',
            'krtv_single_sign_on_service_provider.security.authentication.manager.otp',
            'sso_service_provider.otp_manager',
            'sso_service_provider.encoder',
            'sso_service_provider.uri_signer',
        );

        foreach ($aliases as $alias) {
            $this->assertInstanceOf('Symfony\Component\DependencyInjection\Alias', $containerMock->getAlias($alias));
        }

        $this->assertCount(count($aliases), $containerMock->getAliases());

        $parameters = array(
            'krtv_single_sign_on_service_provider.host' => 'idp.example.com',
            'krtv_single_sign_on_service_provider.host_scheme' => 'https',
            'krtv_single_sign_on_service_provider.login_path' => '/sso/login/',
            'krtv_single_sign_on_service_provider.otp_manager' => array(
                'name' => 'http',
                'managers' => array(
                    'http' => array(
                        'provider' => 'service',
                        'providers' => array(
                            'service' => array(
                                'id' => 'acme_bundle.your_own_fetch_service.id',
                            ),
                            'guzzle' => array(
                                'client' => 'acme_bundle.guzzle_service.id',
                                'resource' => 'http://idp.example.com/internal/v1/sso',
                            ),
                        ),
                    ),
                    'orm' => array()
                ),
            ),

            'krtv_single_sign_on_service_provider.otp_parameter' => '_otp',
            'krtv_single_sign_on_service_provider.secret_parameter' => 'secret',
            'krtv_single_sign_on_service_provider.security.authentication.otp_manager.http.class' => 'Krtv\SingleSignOn\Manager\Http\OneTimePasswordManager',
            'krtv_single_sign_on_service_provider.security.authentication.otp_manager.http.provider.guzzle.class' => 'Krtv\SingleSignOn\Manager\Http\Provider\Guzzle\OneTimePasswordProvider',
            'krtv_single_sign_on_service_provider.encoder.otp.class' => 'Krtv\SingleSignOn\Encoder\OneTimePasswordEncoder',
            'krtv_single_sign_on_service_provider.factory.class' => 'Krtv\Bundle\SingleSignOnServiceProviderBundle\Factory\SingleSignOnFactory',
            'krtv_single_sign_on_service_provider.authentication.entry_point.sso.class' => 'Krtv\Bundle\SingleSignOnServiceProviderBundle\EntryPoint\SingleSignOnAuthenticationEntryPoint',
            'krtv_single_sign_on_service_provider.authentication.provider.otp.class' => 'Krtv\Bundle\SingleSignOnServiceProviderBundle\Authentication\Provider\OneTimePasswordAuthenticationProvider',
            'krtv_single_sign_on_service_provider.authentication.handler.authentication_failure.class' => 'Krtv\Bundle\SingleSignOnServiceProviderBundle\Authentication\Handler\AuthenticationFailureHandler',
            'krtv_single_sign_on_service_provider.listener.otp.class' => 'Krtv\Bundle\SingleSignOnServiceProviderBundle\Firewall\OneTimePasswordListener',
        );

        foreach ($parameters as $parameterName => $parameterValue) {
            $this->assertEquals($parameterValue, $containerMock->getParameter($parameterName));
        }
    }

    /**
     *
     */
    public function testGetAlias()
    {
        $extension = new KrtvSingleSignOnServiceProviderExtension();

        $actual = $extension->getAlias();
        $expected = 'krtv_single_sign_on_service_provider';

        $this->assertEquals($expected, $actual);
    }
}
