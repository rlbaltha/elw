<?php

namespace App\Controller;

use App\Entity\Access;
use App\Form\AccessType;
use App\Form\LabelType;
use App\Repository\AccessRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/access")
 */
class AccessController extends AbstractController
{
    /**
     * @Route("/", name="access_index", methods={"GET"})
     */
    public function index(AccessRepository $accessRepository): Response
    {
        return $this->render('access/index.html.twig', [
            'accesses' => $accessRepository->findAll(),
        ]);
    }


    /**
     * @Route("/{labelsetid}/new", name="access_new", methods={"GET","POST"})
     */
    public function new(Request $request, $labelsetid): Response
    {
        $username = $this->getUser()->getUsername();
        $user = $this->getDoctrine()->getManager()->getRepository('App:User')->findOneByUsername($username);
        $access = new Access();
        $form = $this->createForm(AccessType::class, $access);
        $form->handleRequest($request);
        $entityManager = $this->getDoctrine()->getManager();
        $labelset = $this->getDoctrine()->getManager()->getRepository('App:Labelset')->findOneById($labelsetid);
        $access->setLabelset( $labelset);
        $access->setUser($user);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->persist($access);
            $entityManager->flush();

            return $this->redirectToRoute('labelset_show', ['id'=> $labelsetid]);
        }

        return $this->render('access/new.html.twig', [
            'access' => $access,
            'form' => $form->createView(),
        ]);
    }


    /**
     * @Route("/{id}", name="access_show", methods={"GET"})
     */
    public function show(Access $access): Response
    {
        return $this->render('access/show.html.twig', [
            'access' => $access,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="access_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Access $access): Response
    {
        $form = $this->createForm(AccessType::class, $access);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('access_index');
        }

        return $this->render('access/edit.html.twig', [
            'access' => $access,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="access_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Access $access): Response
    {
        if ($this->isCsrfTokenValid('delete'.$access->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($access);
            $entityManager->flush();
        }

        return $this->redirectToRoute('access_index');
    }
}
