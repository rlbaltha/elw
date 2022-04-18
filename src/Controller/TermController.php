<?php

namespace App\Controller;

use App\Entity\Term;
use App\Form\TermType;
use App\Repository\TermRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/term")
 */
class TermController extends AbstractController
{
    /** @var ManagerRegistry */
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }
    
    /**
     * @Route("/", name="term_index", methods={"GET"})
     */
    public function index(TermRepository $termRepository): Response
    {
        return $this->render('term/index.html.twig', [
            'terms' => $termRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="term_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $term = new Term();
        $form = $this->createForm(TermType::class, $term);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->doctrine->getManager();
            $entityManager->persist($term);
            $entityManager->flush();
            $this->addFlash('notice', 'The term has been created.');
            return $this->redirectToRoute('term_index');
        }

        return $this->render('term/new.html.twig', [
            'term' => $term,
            'form' => $form->createView(),
        ]);
    }


    /**
     * @Route("/{id}/edit", name="term_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Term $term): Response
    {
        $form = $this->createForm(TermType::class, $term);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->getManager()->flush();
            $this->addFlash('notice', 'The term has been updated.');
            return $this->redirectToRoute('term_index');
        }

        return $this->render('term/edit.html.twig', [
            'term' => $term,
            'form' => $form->createView(),
        ]);
    }


    /**
     * @Route("/{id}/default", name="term_default", methods={"GET"})
     */
    public function default(string $id)
    {
        $terms = $this->doctrine->getManager()->getRepository('App:Term')->findAll();
        foreach ($terms as &$archiveterm) {
            $archiveterm->setStatus('Archive');
            $entityManager = $this->doctrine->getManager();
            $entityManager->persist($archiveterm);
        }
        $term = $this->doctrine->getManager()->getRepository('App:Term')->find($id);
        $term->setStatus('Default');
        $entityManager->persist($term);
        $entityManager->flush();

        return $this->redirectToRoute('term_index');
    }

    /**
     * @Route("/{id}", name="term_delete", methods={"POST"})
     */
    public function delete(Request $request, Term $term): Response
    {
        if ($this->isCsrfTokenValid('delete'.$term->getId(), $request->request->get('_token'))) {
            $entityManager = $this->doctrine->getManager();
            $entityManager->remove($term);
            $entityManager->flush();
        }
        $this->addFlash('notice', 'The term has been deleted.');
        return $this->redirectToRoute('term_index');
    }
}
