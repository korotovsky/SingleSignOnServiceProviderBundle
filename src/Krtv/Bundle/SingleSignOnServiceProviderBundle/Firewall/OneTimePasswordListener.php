<?php

namespace Krtv\Bundle\SingleSignOnServiceProviderBundle\Firewall;

use Krtv\Bundle\SingleSignOnServiceProviderBundle\Authentication\Token\OneTimePasswordToken;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Firewall\AbstractAuthenticationListener;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class OneTimePasswordListener
 * @package Krtv\Bundle\KrtvSingleSignOnServiceProviderBundle\Firewall
 */
class OneTimePasswordListener extends AbstractAuthenticationListener
{
    /**
     * @var string
     */
    private $otpParameter;

    /**
     * @var UriSigner
     */
    private $uriSigner;

    /**
     * @param string $name
     */
    public function setOtpParameter($name)
    {
        $this->otpParameter = $name;
    }

    /**
     * @param UriSigner $signer
     */
    public function setUriSigner(UriSigner $signer)
    {
        $this->uriSigner = $signer;
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\Security\Core\Authentication\Token\TokenInterface
     */
    protected function attemptAuthentication(Request $request)
    {
        $otp = $request->get($this->otpParameter);

        try {
            if (false === $this->uriSigner->check($request->getSchemeAndHttpHost().$request->getRequestUri())) {
                throw new BadRequestHttpException('Malformed uri');
            }

            $token = $this->authenticationManager->authenticate(new OneTimePasswordToken($otp));

            if (null !== $this->logger) {
                $this->logger->debug('SecurityContext populated with OneTimePassword token.');
            }

            return $token;
        } catch (AuthenticationException $e) {
            // you might log something here
            if (null !== $this->logger) {
                $this->logger->warn(sprintf('Not authenticated with OneTimePassword: ' . $e->getMessage()));
            }

            throw $e;
        }
    }
}
