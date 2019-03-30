<?php

namespace Krtv\Bundle\SingleSignOnServiceProviderBundle\Authentication\Handler;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationFailureHandler;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class AuthenticationFailureHandler
 * @package Krtv\Bundle\SingleSignOnServiceProviderBundle\Authentication\Handler
 */
class AuthenticationFailureHandler extends DefaultAuthenticationFailureHandler
{
    /**
     * @var UriSigner
     */
    private $uriSigner;

    /**
     * @param UriSigner $signer
     */
    public function setUriSigner(UriSigner $signer)
    {
        $this->uriSigner = $signer;
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        if ($failureUrl = $request->get($this->options['failure_path_parameter'], null, true)) {
            $this->options['failure_path'] = $failureUrl;
        }

        if (null === $this->options['failure_path']) {
            $this->options['failure_path'] = $this->options['login_path'];
        }

        if ($this->options['failure_forward']) {
            $this->log(sprintf('Forwarding to %s', $this->options['failure_path']));

            $subRequest = $this->httpUtils->createRequest($request, $this->options['failure_path']);
            $subRequest->attributes->set(Security::AUTHENTICATION_ERROR, $exception);

            return $this->httpKernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
        }

        $this->log(sprintf('Redirecting to %s', $this->options['failure_path']));

        $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);

        $failureUrl = $this->options['failure_path'];
        $failureUrl .= strpos($failureUrl, '?') !== false ? '&' : '?';
        $failureUrl .= sprintf('_otp_failure=1&');
        $failureUrl .= sprintf('_otp_failure_time=%s', microtime(true));

        $failureUrl = $this->uriSigner->sign($failureUrl);

        return new RedirectResponse($this->httpUtils->generateUri($request, $failureUrl), 302);
    }

    /**
     * @param string $message
     */
    private function log($message)
    {
        if (null !== $this->logger) {
            $this->logger->debug($message);
        }
    }
}
