<?php

namespace App\Controller;

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
        $allowed = ['Student', 'Instructor'];
        $permissions->restrictAccessTo($courseid, $allowed);
        $role = $permissions->getCourseRole($courseid);

        $course = $this->getDoctrine()->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $username = $this->getUser()->getUsername();
        $user = $this->getDoctrine()->getManager()->getRepository('App:User')->findOneByUsername($username);
        $hidden_reviews = $docRepository->countHiddenReviews($course);
        $hidden_comments = $docRepository->countHiddenComments($course);
        if ($findtype == 'SharedDocs') {
            $docs = $docRepository->findSharedDocs($course, $role);
            $header = 'Shared Docs';
        } else {
            $docs = $docRepository->findMyDocs($course, $user);
            $header = 'My Docs';
        }
        return $this->render('doc/index.html.twig', [
            'header' => $header,
            'findtype' => $findtype,
            'docs' => $docs,
            'course' => $course,
            'hidden_comments' => $hidden_comments,
            'hidden_reviews' => $hidden_reviews
        ]);
    }

    /**
     *  Release All Hidden
     * @Route("/release_all_hidden/{courseid}/{findtype}" , name="release_all_hidden")
     *
     */
    public function releaseAllAction(Permissions $permissions, DocRepository $docRepository, $courseid, $findtype)
    {
        $allowed = ['Instructor'];
        $permissions->restrictAccessTo($courseid, $allowed);

        $course = $this->getDoctrine()->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $username = $this->getUser()->getUsername();
        $user = $this->getDoctrine()->getManager()->getRepository('App:User')->findOneByUsername($username);
        $entityManager = $this->getDoctrine()->getManager();
        $docs = $docRepository->findMyDocs($course, $user);

        foreach($docs as $doc){
            if ($doc->getAccess() == 'Hidden'){
                $doc->setAccess('Private');
                $entityManager->persist($doc);
            }
        }
        $entityManager->flush();
        return $this->redirectToRoute('doc_index', ['courseid' => $courseid, 'findtype' => $findtype]);

    }


    /**
     * @Route("/{courseid}/{findtype}/{userid}/byuser", name="doc_byuser", methods={"GET"}, defaults={"findtype":"MyDocs"})
     */
    public function byuser(Request $request, Permissions $permissions, DocRepository $docRepository, $courseid, $userid): Response
    {
        $allowed = ['Student', 'Instructor'];
        $permissions->restrictAccessTo($courseid, $allowed);
        $role = $permissions->getCourseRole($courseid);

        $user = $this->getDoctrine()->getManager()->getRepository('App:User')->findOneById($userid);
        $course = $this->getDoctrine()->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $docs = $docRepository->findByUser($course, $role, $user);
        $header = 'Docs by '. $user->getFirstname().' '.$user->getLastname();
        return $this->render('doc/index.html.twig', [
            'header' => $header,
            'docs' => $docs,
            'course' => $course
        ]);
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
        $stages = $this->getDoctrine()->getManager()->getRepository('App:Stage')->findStagesByCourse($courseid);
        $projects = $this->getDoctrine()->getManager()->getRepository('App:Project')->findProjectsByCourse($courseid);
        $markupsets = $course->getMarkupsets();
        $doc->setUser($user);
        $doc->setCourse($course);
        $doc->setProject($projects[0]);
        $doc->setStage($stages[0]);
        $options = ['courseid' => $courseid];
        $form = $this->createForm(DocType::class, $doc, ['attr' => ['id' => 'doc-form'], 'options' => $options]);
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
        $markupsets = $course->getMarkupsets();
        $doc_title = 'for ' . $origin->getUser()->getFirstname() . ' ' . $origin->getUser()->getLastname();
        $doc->setUser($user);
        $doc->setCourse($course);
        $doc->setOrigin($origin);
        $doc->setTitle($doc_title);
        $doc->setBody($origin->getBody());
        ($permissions->getCourseRole($courseid)==='Instructor' ? $doc->setAccess('Hidden') : $doc->setAccess('Private'));
        $doc->setProject($origin->getProject());
        $doc->setStage($origin->getStage());
        $options = ['courseid' => $courseid];
        $form = $this->createForm(DocType::class, $doc, ['attr' => ['id' => 'doc-form'], 'options' => $options]);
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
        $permissions->isAllowedToView($courseid, $doc);

        $course = $this->getDoctrine()->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $role = $permissions->getCourseRole($courseid);
        $markupsets = $course->getMarkupsets();
        return $this->render('doc/show.html.twig', [
            'doc' => $doc,
            'markupsets' => $markupsets,
            'role' => $role
        ]);
    }

    /**
     * @Route("/{id}/{courseid}/edit", name="doc_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Permissions $permissions, Doc $doc, string $courseid): Response
    {
        $allowed = ['Instructor', 'Student'];
        $permissions->restrictAccessTo($courseid, $allowed);
        $permissions->isOwner($doc);

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
     * @Route("/{id}/{courseid}/access", name="doc_access", methods={"GET","POST"})
     */
    public function access(Request $request, Permissions $permissions, Doc $doc, string $courseid): Response
    {
        $allowed = ['Instructor', 'Student'];
        $permissions->restrictAccessTo($courseid, $allowed);
        $permissions->isOwner($doc);
        $access = $doc->getAccess();
        ($access==='Shared' ? $doc->setAccess('Private') : $doc->setAccess('Shared'));
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($doc);
        $entityManager->flush();

        return $this->redirectToRoute('doc_show', ['id' => $doc->getId(), 'courseid' => $courseid]);
    }

    /**
     * @Route("/{id}/{courseid}/hidden", name="doc_hidden", methods={"GET","POST"})
     */
    public function hidden(Request $request, Permissions $permissions, Doc $doc, string $courseid): Response
    {
        $allowed = ['Instructor'];
        $permissions->restrictAccessTo($courseid, $allowed);
        $permissions->isOwner($doc);
        $access = $doc->getAccess();
        ($access==='Hidden' ? $doc->setAccess('Private') : $doc->setAccess('Hidden'));
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($doc);
        $entityManager->flush();

        return $this->redirectToRoute('doc_show', ['id' => $doc->getId(), 'courseid' => $courseid]);
    }

    /**
     * @Route("/{courseid}/{id}/delete", name="doc_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Permissions $permissions, Doc $doc, string $courseid): Response
    {
        $allowed = ['Instructor', 'Student'];
        $permissions->restrictAccessTo($courseid, $allowed);
        $permissions->isOwner($doc);

        if ($this->isCsrfTokenValid('delete' . $doc->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($doc);
            $entityManager->flush();
        }

        return $this->redirectToRoute('doc_index', ['courseid' => $courseid]);
    }
}
