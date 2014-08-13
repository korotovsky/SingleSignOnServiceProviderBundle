<?php

namespace Krtv\Bundle\SingleSignOnServiceProviderBundle\Authentication\Handler;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationFailureHandler;

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
            if (null !== $this->logger) {
                $this->logger->debug(sprintf('Forwarding to %s', $this->options['failure_path']));
            }

            $subRequest = $this->httpUtils->createRequest($request, $this->options['failure_path']);
            $subRequest->attributes->set(SecurityContextInterface::AUTHENTICATION_ERROR, $exception);

            return $this->httpKernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
        }

        if (null !== $this->logger) {
            $this->logger->debug(sprintf('Redirecting to %s', $this->options['failure_path']));
        }

        $request->getSession()->set(SecurityContextInterface::AUTHENTICATION_ERROR, $exception);

        $failureUrl = $this->options['failure_path'];
        $failureUrl .= strpos($failureUrl, '?') !== false ? '&' : '?';
        $failureUrl .= sprintf('_otp_failure=1&');
        $failureUrl .= sprintf('_otp_failure_time=%s', microtime(true));

        $failureUrl = $this->uriSigner->sign($failureUrl);

        return $this->httpUtils->createRedirectResponse($request, $failureUrl);
    }
}
