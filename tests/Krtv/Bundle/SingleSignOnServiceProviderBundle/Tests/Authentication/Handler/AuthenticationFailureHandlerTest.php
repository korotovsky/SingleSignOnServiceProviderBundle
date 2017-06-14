<?php

namespace Krtv\Bundle\SingleSignOnServiceProviderBunde\Tests\Authentication\Handler;

use Krtv\Bundle\SingleSignOnServiceProviderBundle\Authentication\Handler\AuthenticationFailureHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

if (!class_exists('\PHPUnit_Framework_TestCase') && class_exists('\PHPUnit\Framework\TestCase')) {
    class_alias('\PHPUnit\Framework\TestCase', '\PHPUnit_Framework_TestCase');
}

/**
 * Class AuthenticationFailureHandlerTest
 * @package Krtv\Bundle\SingleSignOnServiceProviderBunde\Tests\Authentication\Handler
 */
class AuthenticationFailureHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     */
    public function testOnAuthenticationFailure()
    {
        $signerMock = $this->getMockBuilder('Symfony\Component\HttpKernel\UriSigner')
            ->enableProxyingToOriginalMethods()
            ->setConstructorArgs(array('secret'))
            ->getMock();

        $failureHandler = new AuthenticationFailureHandler(
            $this->getMockBuilder('Symfony\Component\HttpKernel\HttpKernelInterface')->getMock(),
            $this->getMockBuilder('Symfony\Component\Security\Http\HttpUtils')
                ->enableProxyingToOriginalMethods()
                ->getMock(),
            array(
                'login_path'             => '/login',
                'failure_path'           => 'http://idp.example.com/login',
                'failure_forward'        => false,
                'failure_path_parameter' => '_failure_path'
            ),
            $this->getMockBuilder('Psr\Log\LoggerInterface')->getMock()
        );
        $failureHandler->setUriSigner($signerMock);

        $exception = new AuthenticationException();
        $request   = new Request();
        $request->setSession(new Session(
            new MockArraySessionStorage()
        ));

        $response = $failureHandler->onAuthenticationFailure($request, $exception);

        $this->assertNotNull($response);
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertRegExp('#^http://idp.example.com/login\?_otp_failure=1&_otp_failure_time=\d{10}\.\d{0,4}&_hash=.*$#', $response->getTargetUrl());
    }
} 