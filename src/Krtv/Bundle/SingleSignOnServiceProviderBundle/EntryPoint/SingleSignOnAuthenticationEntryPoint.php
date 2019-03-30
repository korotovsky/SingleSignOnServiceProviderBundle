<?php

namespace Krtv\Bundle\SingleSignOnServiceProviderBundle\EntryPoint;

use Krtv\Bundle\SingleSignOnServiceProviderBundle\Context\AuthenticationContext;
use Krtv\Bundle\SingleSignOnServiceProviderBundle\Context\AuthenticationContextFactory;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class SingleSignOnAuthenticationEntryPoint
 * @package Krtv\Bundle\SingleSignOnServiceProviderBundle\EntryPoint
 */
class SingleSignOnAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    /**
     * @var AuthenticationContextFactory
     */
    private $contextFactory;

    /**
     * @var HttpUtils
     */
    private $httpUtils;

    /**
     * @var UriSigner
     */
    private $uriSigner;

    /**
     * @param AuthenticationContextFactory $contextFactory
     * @param UriSigner $signer
     * @param HttpUtils $httpUtils
     */
    public function __construct(AuthenticationContextFactory $contextFactory, UriSigner $signer, HttpUtils $httpUtils)
    {
        $this->contextFactory = $contextFactory;
        $this->httpUtils      = $httpUtils;
        $this->uriSigner      = $signer;
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        $context = $request->attributes->get($this->contextFactory->getAttribute()); /* @var AuthenticationContext $context */
        $options = $context->getOptions();

        $targetPathParameter    = $options->get('target_path_parameter');
        $failurePathParameter   = $options->get('failure_path_parameter');

        $serviceParameter       = $options->get('sso_service_parameter');
        $serviceExtraParameter  = $options->get('sso_service_extra_parameter');

        $loginRequiredParameter = $options->get('sso_login_required_parameter');

        $scheme = $options->get('sso_scheme');
        $host   = $options->get('sso_host');
        $path   = $options->get('sso_path');

        // OTP validate callback should point to /otp/validate/ route on service provider
        // For example: http://service.provider/otp/validate/
        $otpValidateUrl = $context->getOtpValidationPath($request);

        // Target path is a URL or path to previous URL or it
        // should be an any route which user should visit after OTP valid check.
        $targetUrl      = $context->getTargetPath($request);

        // Add extra parameters to Target URL which are marked as proxy parameters.
        if ($context->getServiceProxy()->count()) {
            if (strpos($targetUrl, '?') === false) {
                $targetUrl .= '?';
            }

            $params = array();
            foreach ($context->getServiceProxy() as $name => $value) {
                $params[$name] = $context->getServiceExtra()->get($name);
            }

            $targetUrl .= http_build_query($params);
        }

        // Sign Target URL to be able verify signature later
        /*
         * this signing apears to be unneeded, it only makes one more hash in url...
         */
//        $targetUrl = $this->uriSigner->sign($targetUrl);

        // on successfull auth of SP1, auth on rest SPs
        if (strpos($targetUrl, 'authAll=true') === false){
            $str = ( strpos($targetUrl, '?') !== false ) ? '&authAll=true' : '?authAll=true';
            $targetUrl = $targetUrl . $str;
        }
        
        // User will be redirected to this route if he isn't authenticated on identity provider
        // or if identity provider returned invalid response on OTP check.
        $failureUrl     = $context->getFailurePath($request);

        // Failure URL should contain Target URL to be able catch it if user came back from identity provider
        if ($failureUrl) {
            if (strpos($failureUrl, '?') === false) {
                $separator = '?';
            } else {
                $separator = '&';
            }

            $failureUrl .= sprintf('%s%s=%s', $separator, $targetPathParameter, rawurlencode($targetUrl));

            // If failure url is the same with current host we add a login_required=1 parameter
            // To be able to suppress SSO Authentication attempt.
            // Make sure that your failure url is accessible as guest.
            if (strpos($failureUrl, $request->getSchemeAndHttpHost()) === 0) {
                $failureUrl .= sprintf('&%s=%s', $loginRequiredParameter, $options->get('sso_login_required'));
            }
        }

        $redirectUri = sprintf('%s/?%s=%s', rtrim($otpValidateUrl, '/'), $targetPathParameter, rawurlencode($targetUrl));

        // Build SSO login URL.
        $redirectUri = sprintf('%s://%s%s/?%s=%s', $scheme, $host, rtrim($path, '/'), $targetPathParameter, rawurlencode($redirectUri));
        $redirectUri = sprintf('%s&%s=%s', $redirectUri, $failurePathParameter, rawurlencode($failureUrl));

        // Append service provider name to root sso login url to be able determine it on identity provider.
        if ($context->getService()) {
            $redirectUri .= sprintf('&%s=%s', $serviceParameter, $context->getService());
        }

        // Append all extra parameters to root sso login url
        if ($context->getService() && $context->getServiceExtra()->count()) {
            $redirectUri .= sprintf('&%s', http_build_query(array(
                $serviceExtraParameter => $context->getServiceExtra()->all()
            )));
        }

        // Sign data
        $redirectUri = $this->uriSigner->sign($redirectUri);

        return new RedirectResponse($this->httpUtils->generateUri($request, $redirectUri), 302);
    }
}
