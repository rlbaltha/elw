<?php

namespace App\Controller;

use App\Entity\Markup;
use App\Form\MarkupType;
use App\Repository\MarkupRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
     * @Route("/new", name="markup_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $markup = new Markup();
        $form = $this->createForm(MarkupType::class, $markup);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($markup);
            $entityManager->flush();

            return $this->redirectToRoute('markup_index');
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
        $form = $this->createForm(MarkupType::class, $markup);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('markup_index');
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
        if ($this->isCsrfTokenValid('delete'.$markup->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($markup);
            $entityManager->flush();
        }

        return $this->redirectToRoute('markup_index');
    }
}
