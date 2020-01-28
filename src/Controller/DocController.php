<?php

namespace App\Controller;

use App\Entity\Doc;
use App\Form\DocType;
use App\Repository\DocRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Extension\AbstractExtension;


/**
 * @Route("/doc")
 */
class DocController extends AbstractController
{

    /**
     * @Route("/{courseid}/index", name="doc_index", methods={"GET"})
     */
    public function index(DocRepository $docRepository): Response
    {

        return $this->render('doc/index.html.twig', [
            'docs' => $docRepository->findAll(),
        ]);
    }

    /**
     * @Route("/{courseid}/new", name="doc_new", methods={"GET","POST"})
     */
    public function new(Request $request, $courseid): Response
    {
        $doc = new Doc();
        $username = $this->getUser()->getUsername();
        $user = $this->getDoctrine()->getManager()->getRepository('App:User')->findOneByUsername($username);
        $course = $this->getDoctrine()->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $doc->setUser($user);
        $doc->setCourse($course);
        $form = $this->createForm(DocType::class, $doc);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($doc);
            $entityManager->flush();

            return $this->redirectToRoute('doc_show', ['id' => $doc->getId(), 'courseid' => $courseid]);
        }

        return $this->render('doc/new.html.twig', [
            'doc' => $doc,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/{courseid}/show", name="doc_show", methods={"GET"})
     */
    public function show(Doc $doc, string $courseid): Response
    {
        return $this->render('doc/show.html.twig', [
            'doc' => $doc,
        ]);
    }

    /**
     * @Route("/{id}/{courseid}/edit", name="doc_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Doc $doc, string $courseid): Response
    {
        $form = $this->createForm(DocType::class, $doc);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('doc_show', ['id' => $doc->getId(), 'courseid' => $courseid]);
        }

        return $this->render('doc/edit.html.twig', [
            'doc' => $doc,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{courseid}/{id}/delete", name="doc_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Doc $doc, string $courseid): Response
    {
        if ($this->isCsrfTokenValid('delete'.$doc->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($doc);
            $entityManager->flush();
        }

        return $this->redirectToRoute('doc_index', ['courseid' => $courseid]);
    }
}
