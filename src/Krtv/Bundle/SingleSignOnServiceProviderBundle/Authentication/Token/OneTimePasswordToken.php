<?php

namespace Krtv\Bundle\SingleSignOnServiceProviderBundle\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\Role\RoleInterface;

/**
 * Class OneTimePasswordToken
 * @package Krtv\Bundle\SingleSignOnServiceProviderBundle\Authentication\Token
 */
class OneTimePasswordToken extends AbstractToken
{
    /**
     * @var string
     */
    private $credentials;

    /**
     * @param string $credentials
     * @param array|RoleInterface[] $roles
     */
    public function __construct($credentials, array $roles = array())
    {
        parent::__construct($roles);

        $this->credentials = $credentials;
    }

    /**
     * @return string
     */
    public function getCredentials()
    {
        return $this->credentials;
    }
}