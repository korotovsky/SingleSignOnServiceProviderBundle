<?php

namespace Krtv\Bundle\SingleSignOnServiceProviderBunde\Tests;

use Krtv\Bundle\SingleSignOnServiceProviderBundle\KrtvSingleSignOnServiceProviderBundle;

if (!class_exists('\PHPUnit_Framework_TestCase') && class_exists('\PHPUnit\Framework\TestCase')) {
    class_alias('\PHPUnit\Framework\TestCase', '\PHPUnit_Framework_TestCase');
}

/**
 * Class KrtvSingleSignOnServiceProviderBundleTest
 * @package Krtv\Bundle\SingleSignOnServiceProviderBunde\Tests
 */
class KrtvSingleSignOnServiceProviderBundleTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     */
    public function testCompilerPassesAreRegistered()
    {
        $extensionMock = $this->getMockBuilder('Symfony\Component\DependencyInjection\Extension\ExtensionInterface')
            ->setMethods(array('addSecurityListenerFactory', 'getNamespace', 'getAlias', 'load', 'getXsdValidationBasePath'))
            ->getMock();
        $extensionMock->expects($this->once())
            ->method('addSecurityListenerFactory')
            ->with($this->isInstanceOf('Krtv\Bundle\SingleSignOnServiceProviderBundle\Factory\SingleSignOnFactory'));

        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')->getMock();
        $container->expects($this->exactly(1))
            ->method('addCompilerPass')
            ->with($this->isInstanceOf('Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface'));
        $container->expects($this->once())
            ->method('getExtension')
            ->with('security')
            ->willReturn($extensionMock);

        $bundle = new KrtvSingleSignOnServiceProviderBundle();
        $bundle->build($container);
    }
}
