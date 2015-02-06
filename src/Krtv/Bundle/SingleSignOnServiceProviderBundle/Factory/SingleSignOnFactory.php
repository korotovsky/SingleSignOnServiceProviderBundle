<?php

namespace Krtv\Bundle\SingleSignOnServiceProviderBundle\Factory;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AbstractFactory;

/***
 * Class SingleSignOnFactory
 * @package Krtv\Bundle\SingleSignOnServiceProviderBundle\Factory
 */
class SingleSignOnFactory extends AbstractFactory
{
    /**
     *
     */
    public function __construct()
    {
        $this->addOption('sso_scheme', 'http');
        $this->addOption('sso_host');
        $this->addOption('sso_path', '/_sso/');
        $this->addOption('sso_failure_path', '/login');

        $this->addOption('sso_service', '');
        $this->addOption('sso_service_parameter', 'service');
    }

    /**
     * @return string
     */
    public function getPosition()
    {
        return 'pre_auth';
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'sso';
    }

    /**
     * @return string
     */
    protected function getListenerId()
    {
        return 'krtv_single_sign_on_service_provider.security.authentication.listener';
    }

    /**
     * @param ContainerBuilder $container
     * @param $id
     * @param $config
     * @param $userProviderId
     * @param $defaultEntryPointId
     * @return array
     */
    public function create(ContainerBuilder $container, $id, $config, $userProviderId, $defaultEntryPointId)
    {
        list($authProviderId, $listenerId, $entryPointId) = parent::create($container, $id, $config, $userProviderId, $defaultEntryPointId);

        return array($authProviderId, $listenerId, $entryPointId);
    }

    /**
     * @param ContainerBuilder $container
     * @param string $id
     * @param array $config
     * @param string $userProviderId
     * @return string
     */
    protected function createAuthProvider(ContainerBuilder $container, $id, $config, $userProviderId)
    {
        $providerId = 'security.authentication.provider.krtv_single_sign_on_service_provider.' . $id;

        $container
            ->setDefinition($providerId, new DefinitionDecorator('krtv_single_sign_on_service_provider.security.authentication.provider'))
            ->replaceArgument(0, new Reference($userProviderId))
            ->replaceArgument(4, $id)
        ;

        return $providerId;
    }

    /**
     * @param ContainerBuilder $container
     * @param string $id
     * @param array $config
     * @param string $defaultEntryPointId
     * @return string
     */
    protected function createEntryPoint($container, $id, $config, $defaultEntryPointId)
    {
        $entryPointId = 'security.authentication.entry_point.krtv_single_sign_on_service_provider.' . $id;

        // add firewall id
        $config['firewall_id'] = $id;

        $container
            ->setDefinition($entryPointId, new DefinitionDecorator('krtv_single_sign_on_service_provider.security.authentication.entry_point'))
            ->replaceArgument(2, $config);

        // set options to container for use by other classes
        $container->setParameter('krtv_single_sign_on_service_provider.options.' . $id, $config);

        return $entryPointId;
    }

    /**
     * @param $container
     * @param $id
     * @param $config
     * @return string
     */
    protected function createAuthenticationFailureHandler($container, $id, $config)
    {
        if (isset($config['failure_handler'])) {
            return $config['failure_handler'];
        }

        $options = array_intersect_key($config, $this->defaultFailureHandlerOptions);
        if ($config['sso_scheme'] && $config['sso_host'] && $config['sso_failure_path']) {
            $options['failure_path'] = sprintf('%s://%s%s', $config['sso_scheme'], $config['sso_host'], $config['sso_failure_path']);
        } elseif ($config['sso_failure_path']) {
            $options['failure_path'] = $config['sso_failure_path'];
        }

        $id = $this->getFailureHandlerId($id);

        $failureHandler = $container->setDefinition($id, new DefinitionDecorator('krtv_single_sign_on_service_provider.authentication.handler.authentication_failure.abstract'));
        $failureHandler->replaceArgument(2, $options);
        $failureHandler->addMethodCall('setUriSigner', array(new Reference('krtv_single_sign_on_service_provider.uri_signer')));

        return $id;
    }
}
