<?php

namespace CPLogger\CoreBundle\EventListener;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use CPLogger\CoreBundle\Controller\AuthControllerInterface;
use CPLogger\CoreBundle\Entity\User;

class AuthListener {

	const DenyMessage = "You need to be signed in!";
	
	public function onKernelController(FilterControllerEvent $event) {
		$controller = $event->getController();

        /*
         * $controller passed can be either a class or a Closure. This is not usual in Symfony2 but it may happen.
         * If it is a class, it comes in array format
         */
        if (!is_array($controller)) {
            return;
        }

        if ($controller[0] instanceof AuthControllerInterface) {
            $request = $event->getRequest();
            $em = $controller[0]->getDoctrine()->getManager();

            $apiId  = $request->get('apiId');
            if ($apiId) {
                $user = $em->getRepository('CPLoggerCoreBundle:User')->findOneBy(['apiId' => $apiId]);
                $apiKey = $request->get('apiKey');

                if (!$user || !$user->validateApi($apiKey))
                    throw new AccessDeniedHttpException(self::DenyMessage . " Hash mismatch for " . $apiId);

                return; // Early return if API validation occurs
            }   

            $session = $request->getSession();

            $userId = $session->get('user_id');
            $userHash = $session->get('user_hash');

            if (!$userId) {
            	throw new AccessDeniedHttpException(self::DenyMessage);
            }

        	$user = $em->getRepository('CPLoggerCoreBundle:User')->find($userId);
        	if (!$user || !$userHash || !$user->verifyHash($userHash)) {
        		throw new AccessDeniedHttpException(self::DenyMessage);
        	}
        }
	}

}