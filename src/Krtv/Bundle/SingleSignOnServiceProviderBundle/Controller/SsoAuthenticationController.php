<?php 
namespace Krtv\Bundle\SingleSignOnServiceProviderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;

class SsoAuthenticationController extends Controller
{
    /**
     * 
     * @param type $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws type
     */
    public function authenticateUserAction(Request $request) {
        /*
         * Route called by ajax from other platforms to get user logged in on current platform
         */
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            // if user is not authenticated AccessDeniedException will trigger authentication on IDP 
            throw $this->createAccessDeniedException();
        }

        return new \Symfony\Component\HttpFoundation\JsonResponse(['success'=>true]);
    }
    
    
}
