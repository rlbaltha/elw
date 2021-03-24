<?php

namespace App\Controller;

use App\Entity\Rubricset;
use App\Form\RubricsetType;
use App\Repository\RubricsetRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/rubricset")
 */
class RubricsetController extends AbstractController
{
    /**
     * @Route("/", name="rubricset_index", methods={"GET"})
     */
    public function index(RubricsetRepository $rubricsetRepository): Response
    {
        return $this->render('rubricset/index.html.twig', [
            'rubricsets' => $rubricsetRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="rubricset_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $rubricset = new Rubricset();
        $form = $this->createForm(RubricsetType::class, $rubricset);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($rubricset);
            $entityManager->flush();

            return $this->redirectToRoute('rubricset_index');
        }

        return $this->render('rubricset/new.html.twig', [
            'rubricset' => $rubricset,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="rubricset_show", methods={"GET"})
     */
    public function show(Rubricset $rubricset): Response
    {
        return $this->render('rubricset/show.html.twig', [
            'rubricset' => $rubricset,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="rubricset_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Rubricset $rubricset): Response
    {
        $form = $this->createForm(RubricsetType::class, $rubricset);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('rubricset_index');
        }

        return $this->render('rubricset/edit.html.twig', [
            'rubricset' => $rubricset,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="rubricset_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Rubricset $rubricset): Response
    {
        if ($this->isCsrfTokenValid('delete'.$rubricset->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($rubricset);
            $entityManager->flush();
        }

        return $this->redirectToRoute('rubricset_index');
    }
}
