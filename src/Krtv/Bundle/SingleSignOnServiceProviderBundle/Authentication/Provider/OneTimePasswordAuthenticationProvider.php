<?php

namespace Krtv\Bundle\SingleSignOnServiceProviderBundle\Authentication\Provider;

use Krtv\SingleSignOn\Model\OneTimePassword;
use Krtv\SingleSignOn\Encoder\OneTimePasswordEncoder;
use Krtv\SingleSignOn\Manager\OneTimePasswordManagerInterface;
use Krtv\Bundle\SingleSignOnServiceProviderBundle\Authentication\Token\OneTimePasswordToken;

use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;

/**
 * Class OneTimePasswordAuthenticationProvider
 * @package Krtv\Bundle\SingleSignOnServiceProviderBundle\Authentication\Provider
 */
class OneTimePasswordAuthenticationProvider implements AuthenticationProviderInterface
{
    /**
     * @var string
     */
    private $providerKey;

    /**
     * @var OneTimePasswordEncoder
     */
    private $encoder;

    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    /**
     * @var OneTimePasswordManagerInterface
     */
    private $otpManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param UserProviderInterface $userProvider
     * @param OneTimePasswordManagerInterface $otpManager
     * @param string $providerKey
     * @param OneTimePasswordEncoder $encoder
     * @param LoggerInterface $logger
     */
    public function __construct(UserProviderInterface $userProvider, OneTimePasswordManagerInterface $otpManager, $providerKey, OneTimePasswordEncoder $encoder, LoggerInterface $logger = null)
    {
        $this->providerKey = $providerKey;
        $this->userProvider = $userProvider;
        $this->otpManager = $otpManager;
        $this->logger = $logger;
        $this->encoder = $encoder;
    }

    /**
     * @return UserProviderInterface
     */
    public function getUserProvider()
    {
        return $this->userProvider;
    }

    /**
     * @param TokenInterface $token
     * @return TokenInterface|void
     */
    public function authenticate(TokenInterface $token)
    {
        try {

            $otp = $this->otpManager->get($token->getCredentials());

            if (!$otp || !$this->otpManager->isValid($otp)) {
                throw new AuthenticationException('OTP is not valid.');
            }

            $user = $this->authenticateOneTimePassword($otp);

            $this->otpManager->invalidate($otp);

            if (!$user instanceof UserInterface) {
                throw new \RuntimeException('OneTimePassword authenticator did not return a UserInterface implementation.');
            }

            if (null !== $this->logger) {
                $this->logger->info('OTP accepted.');
            }

            return new PreAuthenticatedToken($user, $user->getPassword(), $this->providerKey, $user->getRoles());

        } catch (UsernameNotFoundException $notFound) {
            if (null !== $this->logger) {
                $this->logger->info('User for OneTimePassword not found.');
            }
        } catch (UnsupportedUserException $unSupported) {
            if (null !== $this->logger) {
                $this->logger->warn('User class for OneTimePassword not supported.');
            }
        } catch (AuthenticationException $invalid) {
            if (null !== $this->logger) {
                $this->logger->debug('OneTimePassword authentication failed: '.$invalid->getMessage());
            }
        }

        throw new AuthenticationException('OneTimePassword authentication failed.');
    }

    /**
     * @param OneTimePassword $otp
     * @return UserInterface
     */
    public function authenticateOneTimePassword(OneTimePassword $otp)
    {
        $parts = $this->encoder->decodeHash($otp->getHash());

        if (count($parts) !== 3) {
            throw new AuthenticationException('The hash is invalid.');
        }

        list($username, $expires, $hash) = $parts;
        if (false === $username = base64_decode($username, true)) {
            throw new AuthenticationException('$username contains a character from outside the base64 alphabet.');
        }

        try {

            $user = $this->getUserProvider()->loadUserByUsername($username);

            if (!$user instanceof UserInterface) {
                throw new \RuntimeException(sprintf('The UserProviderInterface implementation must return an instance of UserInterface, but returned "%s".', get_class($user)));
            }

            if (true !== $this->encoder->compareHashes($hash, $this->encoder->generateHash($username, $expires))) {
                throw new AuthenticationException('The hash is invalid.');
            }

            if ($expires < microtime(true)) {
                throw new AuthenticationException('The hash has expired.');
            }

            return $user;

        } catch (\Exception $ex) {
            if (!$ex instanceof AuthenticationException) {
                // public function __construct($message = "", $code = 0, Exception $previous = null)
                $ex = new AuthenticationException($ex->getMessage(), $ex->getCode(), $ex);
            }
            throw $ex;
        }
    }

    /**
     * @param TokenInterface $token
     * @return bool
     */
    public function supports(TokenInterface $token)
    {
        return $token instanceof OneTimePasswordToken;
    }
}