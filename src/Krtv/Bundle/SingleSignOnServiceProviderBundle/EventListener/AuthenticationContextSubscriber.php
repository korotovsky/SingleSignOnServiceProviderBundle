<?php

namespace Krtv\Bundle\SingleSignOnServiceProviderBundle\EventListener;

use Krtv\Bundle\SingleSignOnServiceProviderBundle\Context\AuthenticationContextFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class AuthenticationContextSubscriber
 * @package Krtv\Bundle\SingleSignOnServiceProviderBundle\EventListener
 */
class AuthenticationContextSubscriber implements EventSubscriberInterface
{
    /**
     * @var AuthenticationContextFactory
     */
    private $contextFactory;

    /**
     * @param AuthenticationContextFactory $contextFactory
     */
    public function __construct(AuthenticationContextFactory $contextFactory)
    {
        $this->contextFactory = $contextFactory;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     *
     * @api
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => array('onKernelRequest', 4096),
        );
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($event->getRequestType() !== HttpKernelInterface::MASTER_REQUEST) {
            return;
        }

        $contextFactory = $this->contextFactory;

        $request = $event->getRequest();
        $request->attributes->set($contextFactory->getAttribute(), $contextFactory->getContext());
    }
}
