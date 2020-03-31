<?php

namespace App\Controller;

use App\Entity\Classlist;
use App\Entity\Doc;
use App\Form\DocType;
use App\Repository\DocRepository;
use App\Service\Permissions;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Exception\AccessDeniedException;
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
     * @Route("/{courseid}/{findtype}/index", name="doc_index", methods={"GET"}, defaults={"findtype":"MyDocs"})
     */
    public function index(Request $request, Permissions $permissions, DocRepository $docRepository, $courseid, $findtype): Response
    {
        //discover need info on request
        $course = $this->getDoctrine()->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $username = $this->getUser()->getUsername();
        $user = $this->getDoctrine()->getManager()->getRepository('App:User')->findOneByUsername($username);
        $classuser =  $this->getDoctrine()->getManager()->getRepository('App:Classlist')->findCourseUser($course, $user);

        if (!$classuser) {
            $classlist = new Classlist();
            $classlist->setUser($user);
            $classlist->setCourse($course);
            $classlist->setRole('Student');
            $classlist->setStatus('Pending');
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($classlist);
            $entityManager->flush();
            return $this->redirectToRoute('course_show', ['courseid' => $courseid]);
        }
        // check classlist status
        elseif ($classuser->getStatus() == 'Pending') {
            $this->addFlash('notice', 'Your addmission to the course has not yet been approved.');
            return $this->redirectToRoute('course_show', ['courseid' => $courseid]);
        }
        // test role access
        else {
            $allowed = ['Student','Instructor'];
            $permissions->restrictAccessTo($courseid, $allowed);

            if ($findtype == 'SharedDocs') {
                $label = $this->getDoctrine()->getManager()->getRepository('App:Label')->findOneByName('Shared');
                $docs = $docRepository->findDocsByLabel($course, $label);
                $header = 'Shared Docs';
            } elseif ($findtype == 'ReviewDocs') {
                $label = $this->getDoctrine()->getManager()->getRepository('App:Label')->findOneByName('Review');
                $docs = $docRepository->findDocsByLabel($course, $label);
                $header = 'Review Docs By Me';
            } else {
                $docs = $docRepository->findMyDocs($course, $user);
                $header = 'My Docs';
            }
            return $this->render('doc/index.html.twig', [
                'header' => $header,
                'docs' => $docs,
                'course' => $course
            ]);
        }
    }

    /**
     * @Route("/{courseid}/new", name="doc_new", methods={"GET","POST"})
     */
    public function new(Request $request, Permissions $permissions, $courseid): Response
    {
        $allowed = ['Instructor', 'Student'];
        $permissions->restrictAccessTo($courseid, $allowed);

        $doc = new Doc();
        $username = $this->getUser()->getUsername();
        $user = $this->getDoctrine()->getManager()->getRepository('App:User')->findOneByUsername($username);
        $course = $this->getDoctrine()->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $markupsets = $course->getMarkupsets();
        $doc->setUser($user);
        $doc->setCourse($course);
        $form = $this->createForm(DocType::class, $doc, ['attr' => ['id' => 'doc-form']]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($doc);
            $entityManager->flush();

            return $this->redirectToRoute('doc_show', ['id' => $doc->getId(), 'courseid' => $courseid]);
        }

        return $this->render('doc/new.html.twig', [
            'doc' => $doc,
            'markupsets' => $markupsets,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{courseid}/{docid}/review", name="doc_review", methods={"GET","POST"})
     */
    public function review(Request $request, Permissions $permissions, $courseid, $docid): Response
    {
        $allowed = ['Instructor', 'Student'];
        $permissions->restrictAccessTo($courseid, $allowed);

        $doc = new Doc();
        $username = $this->getUser()->getUsername();
        $user = $this->getDoctrine()->getManager()->getRepository('App:User')->findOneByUsername($username);
        $course = $this->getDoctrine()->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $origin = $this->getDoctrine()->getManager()->getRepository('App:Doc')->findOneById($docid);
        $reviewlabel = $this->getDoctrine()->getManager()->getRepository('App:Label')->findOneByName('Review');
        $markupsets = $course->getMarkupsets();
        $doc_title = 'Review for '.$origin->getUser()->getFirstname().' '.$origin->getUser()->getLastname();
        $labels = $origin->getLabels();
        foreach ($labels as &$label) {
            if ($label->getName() != 'Shared') {
                $doc->addLabel($label);
            }
        }
        $doc->setUser($user);
        $doc->setCourse($course);
        $doc->setOrigin($origin);
        $doc->setTitle($doc_title);
        $doc->setBody($origin->getBody());
        $doc->addLabel($reviewlabel);
        $form = $this->createForm(DocType::class, $doc, ['attr' => ['id' => 'doc-form']]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($doc);
            $entityManager->flush();

            return $this->redirectToRoute('doc_show', ['id' => $doc->getId(), 'courseid' => $courseid]);
        }

        return $this->render('doc/new.html.twig', [
            'doc' => $doc,
            'markupsets' => $markupsets,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/{courseid}/show", name="doc_show", methods={"GET"})
     */
    public function show(Doc $doc, string $courseid, Permissions $permissions, Request $request): Response
    {
        $allowed = ['Instructor', 'Student'];
        $permissions->restrictAccessTo($courseid, $allowed);

        $course = $this->getDoctrine()->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $markupsets = $course->getMarkupsets();
        return $this->render('doc/show.html.twig', [
            'doc' => $doc,
            'markupsets' => $markupsets,
        ]);
    }

    /**
     * @Route("/{id}/{courseid}/edit", name="doc_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Permissions $permissions, Doc $doc, string $courseid): Response
    {
        $allowed = ['Instructor', 'Student'];
        $permissions->restrictAccessTo($courseid, $allowed);

        $username = $this->getUser()->getUsername();
        $user = $this->getDoctrine()->getManager()->getRepository('App:User')->findOneByUsername($username);
        if (!$doc->isOwner($user)) {
            throw new AccessDeniedException();
        }
        $course = $this->getDoctrine()->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $form = $this->createForm(DocType::class, $doc, ['attr' => ['id' => 'doc-form']]);
        $form->handleRequest($request);
        $markupsets = $course->getMarkupsets();

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('doc_show', ['id' => $doc->getId(), 'courseid' => $courseid]);
        }

        return $this->render('doc/edit.html.twig', [
            'doc' => $doc,
            'markupsets' => $markupsets,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{courseid}/{id}/delete", name="doc_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Permissions $permissions, Doc $doc, string $courseid): Response
    {
        $allowed = ['Instructor', 'Student'];
        $permissions->restrictAccessTo($courseid, $allowed);

        $username = $this->getUser()->getUsername();
        $user = $this->getDoctrine()->getManager()->getRepository('App:User')->findOneByUsername($username);
        if (!$doc->isOwner($user)) {
            throw new AccessDeniedException();
        }
        if ($this->isCsrfTokenValid('delete'.$doc->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($doc);
            $entityManager->flush();
        }

        return $this->redirectToRoute('doc_index', ['courseid' => $courseid]);
    }
}
