<?php

namespace Krtv\Bundle\SingleSignOnServiceProviderBundle\Context;

use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class AuthenticationContext
 * @package Krtv\Bundle\SingleSignOnServiceProviderBundle\Context
 */
class AuthenticationContextFactory
{
    /**
     * @var ParameterBag
     */
    private $options;

    /**
     * @var string
     */
    private $attribute;

    /**
     * @param array        $options
     * @param string       $attribute
     */
    public function __construct(array $options, $attribute)
    {
        $this->options   = new ParameterBag($options);
        $this->attribute = $attribute;
    }

    /**
     * @return string
     */
    public function getAttribute()
    {
        return $this->attribute;
    }

    /**
     * @return AuthenticationContext
     */
    public function getContext()
    {
        return new AuthenticationContext($this->options);
    }
}
