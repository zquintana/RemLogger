<?php

namespace CPLogger\CoreBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use CPLogger\CoreBundle\Entity\User;
use CPLogger\CoreBundle\Form\SigninType;
use \DateTime;

class SessionController extends Controller
{
    /**
     * @Route("/", name="root")
     * @Template()
     */
    public function indexAction()
    {
        $entity = new User();
        $form   = $this->createForm(new SigninType(), $entity);

        return [
            'entity' => $entity,
            'form'   => $form->createView(),
        ];
    }

    /**
     * @Route("/signin")
     * @Template()
     * @Method({"GET"})
     */
    public function newAction()
    {

    }

    /**
     * @Route("/signin", name="signin")
     * @Method({"POST"})
     */
    public function signinAction(Request $request)
    {
        $entity  = new User();
        $form = $this->createForm(new SigninType(), $entity);
        $form->submit($request);

        $data = $form->getData();

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('CPLoggerCoreBundle:User')->findOneBy(['email' => $data->getEmail()]);
        $session = $this->getRequest()->getSession();

        if (!$user->passwordMatch($data->getPassword())) {
            $session->getFlashBag()->add('error', 'Unable to log you in at this this');
            return [];
        } 

        $user->setLastLoginAt(new DateTime());
        $em->flush();

        $session->set('user_id', $user->getId()); 
        $session->set('user_hash', $user->getHash());   

        return $this->redirect($this->generateUrl('users'));  
    }

    /**
     * @Route("/signout", name="signout")
     * @Method({"GET"})
     * @Template("CPLoggerCoreBundle:Session:index.html.twig")
     */
    public function signoutAction() {
        $this->getRequest()->getSession()->clear();

        return $this->redirect($this->generateUrl('root'));
    }

}
