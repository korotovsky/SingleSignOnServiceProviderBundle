<?php

namespace Krtv\Bundle\SingleSignOnServiceProviderBunde\Tests;

use Krtv\Bundle\SingleSignOnServiceProviderBundle\KrtvSingleSignOnServiceProviderBundle;

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
        $extensionMock = $this->getMock('Symfony\Component\DependencyInjection\Extension\ExtensionInterface', array('addSecurityListenerFactory', 'getNamespace', 'getAlias', 'load', 'getXsdValidationBasePath'));
        $extensionMock->expects($this->once())
            ->method('addSecurityListenerFactory')
            ->with($this->isInstanceOf('Krtv\Bundle\SingleSignOnServiceProviderBundle\Factory\SingleSignOnFactory'));

        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');
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
