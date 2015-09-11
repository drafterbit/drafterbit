<?php

namespace Drafterbit\Bundle\SystemBundle\EventListener;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\WebProfilerBundle\EventListener\WebDebugToolbarListener as BaseListener;
use Drafterbit\Bundle\SystemBundle\Controller\FrontendController;

class WebDebugToolbarListener extends BaseListener
{
    /**
     * Turn of web profiler on preview
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $request = $event->getRequest();
        $theme = $request->query->get('theme');
        $token = $request->query->get('_token');
        $preview = $request->query->get('_pv');
        
        if($theme and $token and $preview) {
            $this->mode = self::DISABLED;
        }
    }

    public static function getSubscribedEvents()
    {
        return array_merge(
            parent::getSubscribedEvents(),
            [KernelEvents::CONTROLLER => array('onKernelController')]
        );
    }
}