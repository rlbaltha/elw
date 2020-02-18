<?php

namespace App\Controller;

use App\Entity\Markupset;
use App\Form\MarkupsetType;
use App\Repository\MarkupsetRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/markupset")
 */
class MarkupsetController extends AbstractController
{
    /**
     * @Route("/", name="markupset_index", methods={"GET"})
     */
    public function index(MarkupsetRepository $markupsetRepository): Response
    {
        return $this->render('markupset/index.html.twig', [
            'markupsets' => $markupsetRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="markupset_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $markupset = new Markupset();
        $form = $this->createForm(MarkupsetType::class, $markupset);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($markupset);
            $entityManager->flush();

            return $this->redirectToRoute('markupset_index');
        }

        return $this->render('markupset/new.html.twig', [
            'markupset' => $markupset,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="markupset_show", methods={"GET"})
     */
    public function show(Markupset $markupset): Response
    {
        return $this->render('markupset/show.html.twig', [
            'markupset' => $markupset,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="markupset_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Markupset $markupset): Response
    {
        $form = $this->createForm(MarkupsetType::class, $markupset);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('markupset_index');
        }

        return $this->render('markupset/edit.html.twig', [
            'markupset' => $markupset,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="markupset_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Markupset $markupset): Response
    {
        if ($this->isCsrfTokenValid('delete'.$markupset->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($markupset);
            $entityManager->flush();
        }

        return $this->redirectToRoute('markupset_index');
    }
}
