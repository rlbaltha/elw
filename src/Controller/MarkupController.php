<?php

namespace App\Controller;

use App\Entity\Markup;
use App\Form\MarkupType;
use App\Repository\MarkupRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route("/markup")
 */
class MarkupController extends AbstractController
{
    /**
     * @Route("/", name="markup_index", methods={"GET"})
     */
    public function index(MarkupRepository $markupRepository): Response
    {
        return $this->render('markup/index.html.twig', [
            'markups' => $markupRepository->findAll(),
        ]);
    }

    /**
     * @Route("/{markupsetid}/new", name="markup_new", methods={"GET","POST"})
     */
    public function new(Request $request, $markupsetid): Response
    {
        $username = $this->getUser()->getUsername();
        $user = $this->getDoctrine()->getManager()->getRepository('App:User')->findOneByUsername($username);
        $markup = new Markup();
        $form = $this->createForm(MarkupType::class, $markup);
        $form->handleRequest($request);
        $entityManager = $this->getDoctrine()->getManager();
        $markupset = $this->getDoctrine()->getManager()->getRepository('App:Markupset')->findOneById($markupsetid);
        $markup->setMarkupset( $markupset);
        $markup->setUser($user);


        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($markup);
            $entityManager->flush();

            return $this->redirectToRoute('markupset_show', ['id'=> $markupsetid]);
        }

        return $this->render('markup/new.html.twig', [
            'markup' => $markup,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="markup_show", methods={"GET"})
     */
    public function show(Markup $markup): Response
    {
        return $this->render('markup/show.html.twig', [
            'markup' => $markup,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="markup_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Markup $markup): Response
    {
        if (!($this->getUser() == $markup->getUser() or $this->isGranted('ROLE_ADMIN'))) {
            throw new AccessDeniedException('You do not have permissions to do this!');
        }
        $markupset = $markup->getMarkupset();
        $form = $this->createForm(MarkupType::class, $markup);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('markupset_show', ['id'=> $markupset->getId()]);
        }

        return $this->render('markup/edit.html.twig', [
            'markup' => $markup,
            'form' => $form->createView(),
        ]);
    }


    /**
     * @Route("/{id}", name="markup_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Markup $markup): Response
    {
        $markupset = $markup->getMarkupset();

        if ($this->isCsrfTokenValid('delete'.$markup->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($markup);
            $entityManager->flush();
        }

        return $this->redirectToRoute('markupset_show', ['id'=> $markupset->getId()]);
    }
}
