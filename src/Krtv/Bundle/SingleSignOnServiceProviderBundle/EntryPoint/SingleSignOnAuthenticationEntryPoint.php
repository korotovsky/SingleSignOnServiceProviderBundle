<?php

namespace Krtv\Bundle\SingleSignOnServiceProviderBundle\EntryPoint;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\HttpUtils;

/**
 * Class SingleSignOnAuthenticationEntryPoint
 * @package Krtv\Bundle\SingleSignOnServiceProviderBundle\EntryPoint
 */
class SingleSignOnAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    /**
     * @var ParameterBag
     */
    private $options;

    /**
     * @var HttpUtils
     */
    private $httpUtils;

    /**
     * @var UriSigner
     */
    private $uriSigner;

    /**
     * @param \Symfony\Component\HttpKernel\UriSigner $signer
     * @param \Symfony\Component\Security\Http\HttpUtils $httpUtils
     * @param array $options
     */
    public function __construct(UriSigner $signer, HttpUtils $httpUtils, array $options = array())
    {
        $this->httpUtils = $httpUtils;
        $this->uriSigner = $signer;

        $this->options = new ParameterBag($options);
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        $host = $this->options->get('sso_host');
        $scheme = $this->options->get('sso_scheme');
        $path = rtrim($this->options->get('sso_path'), '/');
        $ssoService = $this->options->get('sso_service');

        $checkPath = $this->options->get('check_path');

        $targetPathParameter = $this->options->get('target_path_parameter');
        $failurePathParameter = $this->options->get('failure_path_parameter');
        $ssoServiceParameter = $this->options->get('sso_service_parameter');

        $redirectUri = $request->getUriForPath($checkPath);

        // make sure we keep the target path after login
        if ($targetUrl = $this->determineTargetUrl($request)) {
            $redirectUri = sprintf('%s/?%s=%s', rtrim($redirectUri, '/'), $targetPathParameter, rawurlencode($targetUrl));
        }

        $loginUrl = sprintf('%s://%s%s/?%s=%s', $scheme, $host, $path, $targetPathParameter, rawurlencode($redirectUri));

        if ($failureUrl = $this->determineFailureUrl($request)) {
            $loginUrl = sprintf('%s&%s=%s', $loginUrl, $failurePathParameter, rawurlencode($failureUrl));
        }

        if ($ssoService) {
            $loginUrl = sprintf('%s&%s=%s', $loginUrl, $ssoServiceParameter, $ssoService);
        }

        $loginUrl = $this->uriSigner->sign($loginUrl);

        return $this->httpUtils->createRedirectResponse($request, $loginUrl);
    }

    /**
     * @see Symfony\Component\Security\Http\Firewall\AbstractAuthenticationListener:determineTargetUrl
     */
    protected function determineTargetUrl(Request $request)
    {
        if ($this->options->get('always_use_default_target_path') === true) {
            return $this->options->get('default_target_path');
        }

        if ($targetUrl = $request->get($this->options->get('target_path_parameter'), null, true)) {
            return $targetUrl;
        }

        $session = $request->getSession();
        if ($targetUrl = $session->get(sprintf('_security.%s.target_path', $this->options->get('firewall_id')))) {
            return $targetUrl;
        }

        if ($this->options->get('use_referer') && $targetUrl = $request->headers->get('Referer')) {
            return $targetUrl;
        }

        return $this->options->get('default_target_path');
    }

    /**
     * @param Request $request
     * @return string
     */
    protected function determineFailureUrl(Request $request)
    {
        if ($failureUrl = $request->get($this->options->get('failure_path_parameter'), null, true)) {
            return $failureUrl;
        }

        $session = $request->getSession();
        if ($failureUrl = $session->get(sprintf('_security.%s.failure_path', $this->options->get('firewall_id')))) {
            return $failureUrl;
        }

        return $this->options->get('failure_path');
    }
}
