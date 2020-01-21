<?php

namespace App\Controller;

use App\Entity\Doc;
use App\Form\DocType;
use App\Repository\DocRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/doc")
 */
class DocController extends AbstractController
{
    /**
     * @Route("/", name="doc_index", methods={"GET"})
     */
    public function index(DocRepository $docRepository): Response
    {
        return $this->render('doc/index.html.twig', [
            'docs' => $docRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="doc_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $doc = new Doc();
        $form = $this->createForm(DocType::class, $doc);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($doc);
            $entityManager->flush();

            return $this->redirectToRoute('doc_index');
        }

        return $this->render('doc/new.html.twig', [
            'doc' => $doc,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="doc_show", methods={"GET"})
     */
    public function show(Doc $doc): Response
    {
        return $this->render('doc/show.html.twig', [
            'doc' => $doc,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="doc_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Doc $doc): Response
    {
        $form = $this->createForm(DocType::class, $doc);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('doc_index');
        }

        return $this->render('doc/edit.html.twig', [
            'doc' => $doc,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="doc_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Doc $doc): Response
    {
        if ($this->isCsrfTokenValid('delete'.$doc->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($doc);
            $entityManager->flush();
        }

        return $this->redirectToRoute('doc_index');
    }
}
