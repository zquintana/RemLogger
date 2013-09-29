<?php

namespace CPLogger\CoreBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use CPLogger\CoreBundle\Entity\Log;
use CPLogger\CoreBundle\Entity\User;
use CPLogger\CoreBundle\Form\LogType;

/**
 * Log controller.
 *
 * @Route("/log")
 */
class LogController extends Controller implements AuthControllerInterface
{

    /**
     * Lists all Log entities.
     *
     * @Route("/", name="log")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('CPLoggerCoreBundle:Log')->findAll();

        return array(
            'entities' => $entities,
        );
    }
    /**
     * Creates a new Log entity.
     *
     * @Route("/", name="log_create")
     * @Method("POST")
     * @Template("CPLoggerCoreBundle:Log:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity  = new Log();
        $form = $this->createForm(new LogType(), $entity);
        $form->bind($request);


        $accept = AcceptHeader::fromString($request->headers->get('Accept'));
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();


            if ($accept->has('application/json')) {
                $users = $em->getRepository('CPLoggerCoreBundle:User')->findAll();
                $emails = [];
                foreach ($users as $user) { $emails[] = $user->getEmail(); }

                $transport = \Swift_SendmailTransport::newInstance('/usr/sbin/sendmail -bs');
                $mailer = $mailer = \Swift_Mailer::newInstance($transport);
                $message = \Swift_Message::newInstance()
                    ->setSubject('New Log')
                    ->setFrom('donotreply@donotreply.com')
                    ->setTo($emails)
                    ->setBody(
                        $this->renderView(
                            'CPLoggerCoreBundle:Log:Mailer/create.txt.twig',
                            array('entity' => $entity)
                        )
                    )
                    ->addPart($this->renderView(
                            'CPLoggerCoreBundle:Log:Mailer/create.html.twig',
                            array('entity' => $entity)
                        ), 'text/html');
                    
                return new JsonResponse(['success' => true, 'sent' => $mailer->send($message)]);
            } else {
                return $this->redirect($this->generateUrl('log_show', array('id' => $entity->getId())));
            }
        }

        if ($accept->has('application/json')) {
            return new JsonResponse(['success' => false, 'log' => $entity]);
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Displays a form to create a new Log entity.
     *
     * @Route("/new", name="log_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new Log();
        $form   = $this->createForm(new LogType(), $entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays a Log entity.
     *
     * @Route("/{id}", name="log_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('CPLoggerCoreBundle:Log')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Log entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Log entity.
     *
     * @Route("/{id}/edit", name="log_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('CPLoggerCoreBundle:Log')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Log entity.');
        }

        $editForm = $this->createForm(new LogType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing Log entity.
     *
     * @Route("/{id}", name="log_update")
     * @Method("PUT")
     * @Template("CPLoggerCoreBundle:Log:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('CPLoggerCoreBundle:Log')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Log entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new LogType(), $entity);
        $editForm->submit($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('log_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Deletes a Log entity.
     *
     * @Route("/{id}", name="log_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('CPLoggerCoreBundle:Log')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Log entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('log'));
    }

    /**
     * Creates a form to delete a Log entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->getForm()
        ;
    }
}
