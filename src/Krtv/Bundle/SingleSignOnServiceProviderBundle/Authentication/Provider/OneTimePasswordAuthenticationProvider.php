<?php

namespace Krtv\Bundle\SingleSignOnServiceProviderBundle\Authentication\Provider;

use Krtv\Bundle\SingleSignOnServiceProviderBundle\Authentication\Token\OneTimePasswordToken;
use Krtv\SingleSignOn\Model\OneTimePassword;
use Krtv\SingleSignOn\Encoder\OneTimePasswordEncoder;
use Krtv\SingleSignOn\Manager\OneTimePasswordManagerInterface;
use Krtv\SingleSignOn\Model\OneTimePasswordInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationServiceException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
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
     * @var UserProviderInterface
     */
    private $userProvider;

    /**
     * @var UserCheckerInterface
     */
    private $userChecker;

    /**
     * @var OneTimePasswordEncoder
     */
    private $otpEncoder;

    /**
     * @var OneTimePasswordManagerInterface
     */
    private $otpManager;

    /**
     * @var string
     */
    private $providerKey;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param UserProviderInterface $userProvider
     * @param UserCheckerInterface $userChecker
     * @param OneTimePasswordManagerInterface $otpManager
     * @param OneTimePasswordEncoder $otpEncoder
     * @param string $providerKey
     * @param LoggerInterface $logger
     */
    public function __construct(UserProviderInterface $userProvider, UserCheckerInterface $userChecker, OneTimePasswordManagerInterface $otpManager, OneTimePasswordEncoder $otpEncoder, $providerKey, LoggerInterface $logger = null)
    {
        $this->userProvider = $userProvider;
        $this->userChecker = $userChecker;
        $this->otpManager = $otpManager;
        $this->otpEncoder = $otpEncoder;
        $this->providerKey = $providerKey;
        $this->logger = $logger;
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
     * @return PreAuthenticatedToken
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
                $this->logger->warning('User class for OneTimePassword not supported.');
            }
        } catch (AuthenticationException $invalid) {
            if (null !== $this->logger) {
                $this->logger->debug('OneTimePassword authentication failed: '.$invalid->getMessage());
            }
        }

        throw new AuthenticationException('OneTimePassword authentication failed.');
    }

    /**
     * @param OneTimePasswordInterface $otp
     * @return UserInterface
     */
    public function authenticateOneTimePassword(OneTimePasswordInterface $otp)
    {
        $parts = $this->otpEncoder->decodeHash($otp->getHash());

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
                throw new AuthenticationServiceException('loadUserByUsername() must return a UserInterface.');
            }

            $this->userChecker->checkPreAuth($user);

            if (true !== $this->otpEncoder->compareHashes($hash, $this->otpEncoder->generateHash($username, $expires))) {
                throw new AuthenticationException('The hash is invalid.');
            }

            if ($expires < microtime(true)) {
                throw new AuthenticationException('The hash has expired.');
            }

            $this->userChecker->checkPostAuth($user);

            return $user;
        } catch (\Exception $ex) {
            if (!$ex instanceof AuthenticationException) {
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