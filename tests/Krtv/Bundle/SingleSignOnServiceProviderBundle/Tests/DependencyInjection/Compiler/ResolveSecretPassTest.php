<?php

namespace Krtv\Bundle\SingleSignOnServiceProviderBunde\Tests\DependencyInjection\Compiler;

use Krtv\Bundle\SingleSignOnServiceProviderBundle\DependencyInjection\Compiler\ResolveSecretPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

if (!class_exists('\PHPUnit_Framework_TestCase') && class_exists('\PHPUnit\Framework\TestCase')) {
    class_alias('\PHPUnit\Framework\TestCase', '\PHPUnit_Framework_TestCase');
}

/**
 * Class ResolveSecretPassTest
 * @package Krtv\Bundle\SingleSignOnServiceProviderBunde\Tests\DependencyInjection\Compiler
 */
class ResolveSecretPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $container;

    /**
     *
     */
    protected function setUp()
    {
        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->getMock();
        $this->container->expects($this->any())
            ->method('getParameter')
            ->willReturnMap(array(
                array('krtv_single_sign_on_service_provider.secret_parameter', 'secret'),
                array('secret', 'secret_is_very_secret'),
            ));

        $encoder = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->getMock();
        $encoder->expects($this->once())
            ->method('replaceArgument')
            ->with(0, 'secret_is_very_secret');

        $signer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->getMock();
        $signer->expects($this->once())
            ->method('replaceArgument')
            ->with(0, 'secret_is_very_secret');

        $this->container->expects($this->any())
            ->method('getDefinition')
            ->willReturnMap(array(
                array('krtv_single_sign_on_service_provider.security.authentication.encoder', $encoder),
                array('krtv_single_sign_on_service_provider.uri_signer', $signer)
            ));
    }

    /**
     *
     */
    public function testProcess()
    {
        $pass = new ResolveSecretPass();
        $pass->process($this->container);
    }
}
