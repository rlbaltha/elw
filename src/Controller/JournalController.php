<?php

namespace App\Controller;

use App\Entity\Doc;
use App\Form\JournalType;
use App\Repository\DocRepository;
use App\Service\Lti;
use App\Service\Permissions;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route("/journal")
 */
class JournalController extends AbstractController
{

    /**
     * @Route("/{courseid}/{docid}/{userid}/{index}/index", name="journal_index", methods={"GET"}, defaults={"docid":"0", "userid":"0", "index":"1"})
     */
    public function index(Request $request, Permissions $permissions, DocRepository $docRepository, Lti $lti, string $courseid, string $docid, string $userid, string $index): Response
    {
        $allowed = ['Student', 'Instructor'];
        $permissions->restrictAccessTo($courseid, $allowed);
        $role = $permissions->getCourseRole($courseid);
        $course = $this->getDoctrine()->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $classlists = $this->getDoctrine()->getManager()->getRepository('App:Classlist')->findByCourseid($courseid);
        $username = $this->getUser()->getUsername();
        $user = $this->getDoctrine()->getManager()->getRepository('App:User')->findOneByUsername($username);
        if ($userid!=0) {
            $requested_user = $this->getDoctrine()->getManager()->getRepository('App:User')->findOneById($userid);
            if ($user == $requested_user or $role=='Instructor') {
                $user = $requested_user;
            }
            else {
                throw new AccessDeniedException('You do not have permissions to do this!');
            }
        }

        $docs = $docRepository->findJournal($course, $user);
        $doc = $docRepository->findOneById($docid);
        $scores = [];
        if (count($docs)>0 and !$doc) {
            $doc = $docs[0];
            if ($doc->getAgsResultId() != null){
                $scores = $lti->getLtiResult($doc->getAgsResultId());
            }
        }
        return $this->render('journal/index.html.twig', [
            'docs' => $docs,
            'doc' => $doc,
            'scores' => $scores,
            'course' => $course,
            'classlists' => $classlists,
            'role' => $role,
            'index' => $index
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
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($doc);
        $entityManager->flush();
        return $this->redirectToRoute('journal_edit', ['id' => $doc->getId(), 'courseid' => $courseid]);
    }

    /**
     * @Route("/{id}/{courseid}/edit", name="journal_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Permissions $permissions, Doc $doc, string $courseid): Response
    {
        $allowed = ['Instructor', 'Student'];
        $permissions->restrictAccessTo($courseid, $allowed);
        $permissions->isOwner($doc);

        $form = $this->createForm(JournalType::class, $doc, ['attr' => ['id' => 'doc-form']]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('notice', 'Your journal has been saved.');
            return $this->redirectToRoute('journal_index', ['docid' => $doc->getId(), 'courseid' => $courseid]);
        }

        return $this->render('journal/edit.html.twig', [
            'doc' => $doc,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{courseid}/{id}/delete", name="journal_delete", methods={"DELETE"})
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

        return $this->redirectToRoute('journal_index', ['docid' => 0, 'courseid' => $courseid]);
    }

}
