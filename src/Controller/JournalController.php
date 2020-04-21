<?php

namespace App\Controller;

use App\Entity\Doc;
use App\Form\DocType;
use App\Form\JournalType;
use App\Repository\DocRepository;
use App\Service\Permissions;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class JournalController extends AbstractController
{
    /**
     * @Route("/journal", name="journal")
     */
    /**
     * @Route("/{courseid}/{docid}/index", name="journal_index", methods={"GET"}, defaults={"docid":"0"})
     */
    public function index(Request $request, Permissions $permissions, DocRepository $docRepository, $courseid, $docid): Response
    {
        $allowed = ['Student', 'Instructor'];
        $permissions->restrictAccessTo($courseid, $allowed);
        $role = $permissions->getCourseRole($courseid);

        $course = $this->getDoctrine()->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $username = $this->getUser()->getUsername();
        $user = $this->getDoctrine()->getManager()->getRepository('App:User')->findOneByUsername($username);
        $docs = $docRepository->findJournal($course, $user);
        $doc = $docRepository->findOneById($docid);
        return $this->render('journal/index.html.twig', [
            'docs' => $docs,
            'doc' => $doc,
            'course' => $course
        ]);
    }

    /**
     * @Route("/{courseid}/new", name="journal_new", methods={"GET","POST"})
     */
    public function new(Request $request, Permissions $permissions, $courseid): Response
    {
        $allowed = ['Instructor', 'Student'];
        $permissions->restrictAccessTo($courseid, $allowed);

        $doc = new Doc();
        $username = $this->getUser()->getUsername();
        $user = $this->getDoctrine()->getManager()->getRepository('App:User')->findOneByUsername($username);
        $course = $this->getDoctrine()->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $doc->setTitle('New Journal Entry');
        $doc->setUser($user);
        $doc->setCourse($course);
        $doc->setAccess('Journal');
        $form = $this->createForm(JournalType::class, $doc, ['attr' => ['id' => 'doc-form']]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($doc);
            $entityManager->flush();

            return $this->redirectToRoute('journal_index', ['docid' => $doc->getId(), 'courseid' => $courseid]);
        }

        return $this->render('journal/new.html.twig', [
            'doc' => $doc,
            'form' => $form->createView(),
        ]);
    }
}
