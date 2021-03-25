<?php

namespace App\Controller;

use App\Entity\Rubric;
use App\Form\RubricType;
use App\Repository\RubricRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/rubric")
 */
class RubricController extends AbstractController
{
    /**
     * @Route("/", name="rubric_index", methods={"GET"})
     */
    public function index(RubricRepository $rubricRepository): Response
    {
        return $this->render('rubric/index.html.twig', [
            'rubrics' => $rubricRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="rubric_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $username = $this->getUser()->getUsername();
        $user = $this->getDoctrine()->getManager()->getRepository('App:User')->findOneByUsername($username);
        $rubric = new Rubric();
        $form = $this->createForm(RubricType::class, $rubric);
        $form->handleRequest($request);
        $rubric->setUser($user);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($rubric);
            $entityManager->flush();
            $this->addFlash('notice', 'Your Rubric has been created.');
            return $this->redirectToRoute('rubric_index');
        }

        return $this->render('rubric/new.html.twig', [
            'rubric' => $rubric,
            'form' => $form->createView(),
        ]);
    }


    /**
     * @Route("/{id}/edit", name="rubric_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Rubric $rubric, string $rubricsetid): Response
    {
        $form = $this->createForm(RubricType::class, $rubric);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('rubric_index');
        }

        return $this->render('rubric/edit.html.twig', [
            'rubric' => $rubric,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="rubric_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Rubric $rubric): Response
    {
        if ($this->isCsrfTokenValid('delete'.$rubric->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($rubric);
            $entityManager->flush();
        }

        return $this->redirectToRoute('rubric_index');
    }
}
