<?php

namespace Krtv\Bundle\SingleSignOnServiceProviderBundle\Context;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class AuthenticationContext
 * @package Krtv\Bundle\SingleSignOnServiceProviderBundle\Context
 */
class AuthenticationContext
{
    const CTX_TARGET_PATH  = '_target_path';
    const CTX_FAILURE_PATH = '_failure_path';

    /**
     * Firewall options.
     *
     * @var ParameterBag
     */
    private $options;

    /**
     * Options with a highest priority of firewall options.
     *
     * @var ParameterBag
     */
    private $context;

    /**
     * Options to be proxied to IdP.
     *
     * @var ParameterBag
     */
    private $extra;

    /**
     * Options to be proxied to IdP and come back to SP.
     *
     * @var ParameterBag
     */
    private $proxy;

    /**
     * @param ParameterBag $options
     */
    public function __construct(ParameterBag $options)
    {
        $this->options = $options;
        $this->context = new ParameterBag();
        $this->extra   = new ParameterBag();
        $this->proxy   = new ParameterBag();
    }

    /**
     * @param string $name
     * @param string $value
     * @param bool   $proxy
     */
    public function setServiceExtra($name, $value, $proxy = false)
    {
        $this->extra->set($name, $value);

        if ($proxy === true) {
            $this->proxy->set($name, true);
        }
    }

    /**
     * @param string $name
     * @param string $value
     */
    public function setContext($name, $value)
    {
        $this->context->set($name, $value);
    }

    /**
     * @return string|null
     */
    public function getService()
    {
        return $this->options->get('sso_service');
    }

    /**
     * @return ParameterBag
     */
    public function getServiceExtra()
    {
        return $this->extra;
    }

    /**
     * @return ParameterBag
     */
    public function getServiceProxy()
    {
        return $this->proxy;
    }

    /**
     * @return ParameterBag
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    public function getTargetPath(Request $request)
    {
        if ($this->context->has(static::CTX_TARGET_PATH)) {
            return $this->context->get(static::CTX_TARGET_PATH);
        }

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
     *
     * @return string
     */
    public function getFailurePath(Request $request)
    {
        if ($this->context->has(static::CTX_FAILURE_PATH)) {
            return $this->context->get(static::CTX_FAILURE_PATH);
        }

        if ($failureUrl = $request->get($this->options->get('failure_path_parameter'), null, true)) {
            return $failureUrl;
        }

        $session = $request->getSession();
        if ($failureUrl = $session->get(sprintf('_security.%s.failure_path', $this->options->get('firewall_id')))) {
            return $failureUrl;
        }

        return $this->options->get('failure_path');
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    public function getOtpValidationPath(Request $request)
    {
        $scheme    = $this->options->get('sso_otp_scheme');
        $host      = $this->options->get('sso_otp_host');
        $checkPath = $this->options->get('check_path');

        if ($scheme !== null && $host !== null) {
            return sprintf('%s://%s%s', $scheme, $host, $checkPath);
        }

        return $request->getUriForPath($checkPath);
    }
}
