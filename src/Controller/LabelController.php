<?php

namespace App\Controller;

use App\Entity\Label;
use App\Form\LabelType;
use App\Repository\LabelRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/label")
 */
class LabelController extends AbstractController
{
    /**
     * @Route("/", name="label_index", methods={"GET"})
     */
    public function index(LabelRepository $labelRepository): Response
    {
        return $this->render('label/index.html.twig', [
            'labels' => $labelRepository->findAll(),
        ]);
    }

    /**
     * @Route("/{labelsetid}/new", name="label_new", methods={"GET","POST"})
     */
    public function new(Request $request, $labelsetid): Response
    {
        $username = $this->getUser()->getUsername();
        $user = $this->getDoctrine()->getManager()->getRepository('App:User')->findOneByUsername($username);
        $label = new Label();
        $form = $this->createForm(LabelType::class, $label);
        $form->handleRequest($request);
        $entityManager = $this->getDoctrine()->getManager();
        $labelset = $this->getDoctrine()->getManager()->getRepository('App:Labelset')->findOneById($labelsetid);
        $label->setLabelset( $labelset);
        $label->setUser($user);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->persist($label);
            $entityManager->flush();

            return $this->redirectToRoute('labelset_show', ['id'=> $labelsetid]);
        }

        return $this->render('label/new.html.twig', [
            'label' => $label,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="label_show", methods={"GET"})
     */
    public function show(Label $label): Response
    {
        return $this->render('label/show.html.twig', [
            'label' => $label,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="label_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Label $label): Response
    {
        $labelset = $label->getLabelset();
        $form = $this->createForm(LabelType::class, $label);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('labelset_show', ['id'=> $labelset->getId()]);
        }

        return $this->render('label/edit.html.twig', [
            'label' => $label,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="label_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Label $label): Response
    {
        $labelset = $label->getLabelset();
        if ($this->isCsrfTokenValid('delete'.$label->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($label);
            $entityManager->flush();
        }

        return $this->redirectToRoute('labelset_show', ['id'=> $labelset->getId()]);
    }
}
