<?php

namespace App\Controller;

use App\Entity\Labelset;
use App\Form\LabelsetType;
use App\Repository\LabelsetRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/labelset")
 */
class LabelsetController extends AbstractController
{
    /**
     * @Route("/", name="labelset_index", methods={"GET"})
     */
    public function index(LabelsetRepository $labelsetRepository): Response
    {
        return $this->render('labelset/index.html.twig', [
            'labelsets' => $labelsetRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="labelset_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $labelset = new Labelset();
        $form = $this->createForm(LabelsetType::class, $labelset);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($labelset);
            $entityManager->flush();

            return $this->redirectToRoute('labelset_index');
        }

        return $this->render('labelset/new.html.twig', [
            'labelset' => $labelset,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="labelset_show", methods={"GET"})
     */
    public function show(Labelset $labelset): Response
    {
        return $this->render('labelset/show.html.twig', [
            'labelset' => $labelset,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="labelset_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Labelset $labelset): Response
    {
        $form = $this->createForm(LabelsetType::class, $labelset);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('labelset_index');
        }

        return $this->render('labelset/edit.html.twig', [
            'labelset' => $labelset,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="labelset_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Labelset $labelset): Response
    {
        if ($this->isCsrfTokenValid('delete'.$labelset->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($labelset);
            $entityManager->flush();
        }

        return $this->redirectToRoute('labelset_index');
    }
}
