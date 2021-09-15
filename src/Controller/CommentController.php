<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Form\CommentJournalType;
use App\Form\CommentType;
use App\Repository\CommentRepository;
use App\Repository\DocRepository;
use App\Service\Permissions;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @Route("/comment")
 */
class CommentController extends AbstractController
{

    /**
     * @Route("/{courseid}/{docid}/{source}/ajax_new", name="comment_ajax_new", methods={"GET","POST"})
     */
    public function ajax_new(Request $request, Permissions $permissions, $docid, $courseid, $source): Response
    {
        $username = $this->getUser()->getUsername();
        $user = $this->getDoctrine()->getManager()->getRepository('App:User')->findOneByUsername($username);
        $role = $permissions->getCourseRole($courseid);
        $doc = $this->getDoctrine()->getManager()->getRepository('App:Doc')->findOneById($docid);
        $comment = new Comment();
        if ($role=='Instructor' and $source=='doc') {
            $comment->setAccess('Hidden');
        }
        if ($source=='journal') {
            $comment->setAccess('Private');
        }
        $comment->setUser($user);
        $comment->setDoc($doc);
        $comment->setType('Holistic Feedback');
        $form = $this->createForm(CommentJournalType::class, $comment, ['action' => $this->generateUrl('comment_ajax_new', ['courseid' => $courseid, 'docid' => $doc->getId(), 'source' => $source]),
            'method' => 'POST',]          );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($comment);
            $entityManager->flush();
            $this->addFlash('notice', 'Your end comment has been added.');
            if ($source!='doc') {
                return $this->redirectToRoute('journal_index', ['docid' => $doc->getId(), 'userid' => $doc->getUser()->getId(), 'courseid' => $courseid]);
            }
            return $this->redirectToRoute('doc_show', ['id' => $doc->getId(), 'courseid' => $courseid, 'target' => $doc->getId()]);
        }

        return $this->render('comment/ajax.html.twig', [
            'form' => $form->createView(),
            'comment' => $comment,
            'doc' => $doc,
            'courseid' => $courseid,
        ]);
    }


    /**
     * @Route("/{courseid}/{docid}/{source}/new", name="comment_new", methods={"GET","POST"})
     */
    public function new(Request $request, Permissions $permissions, $docid, $courseid, $source): Response
    {

        $header = 'End Comment New';
        $username = $this->getUser()->getUsername();
        $user = $this->getDoctrine()->getManager()->getRepository('App:User')->findOneByUsername($username);
        $role = $permissions->getCourseRole($courseid);
        $course = $this->getDoctrine()->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $doc = $this->getDoctrine()->getManager()->getRepository('App:Doc')->findOneById($docid);
        $comment = new Comment();
        if ($role=='Instructor' and $source=='doc') {
            $comment->setAccess('Hidden');
        }
        if ($source=='journal') {
            $comment->setAccess('Private');
        }
        $comment->setUser($user);
        $comment->setDoc($doc);
        $comment->setType('Holistic Feedback');
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($comment);
            $entityManager->flush();
            $this->addFlash('notice', 'Your end comment has been added.');
            if ($source!='doc') {
                return $this->redirectToRoute('comment_ajax_show', ['docid' => $doc->getId(), 'id' => $comment->getId()]);
            }
            return $this->redirectToRoute('doc_show', ['id' => $doc->getId(), 'courseid' => $courseid, 'target' => $doc->getId()]);
        }

        return $this->render('comment/new.html.twig', [
            'comment' => $comment,
            'doc' => $doc,
            'course' => $course,
            'role' => $role,
            'header' => $header,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{courseid}/{docid}/{source}/{id}/ajax_edit", name="comment_ajax_edit", methods={"GET","POST"})
     */
    public function ajax_edit(SerializerInterface $serializer, Request $request, Comment $comment, string $docid, string $courseid, string $source, string $id): Response
    {
        $doc = $this->getDoctrine()->getManager()->getRepository('App:Doc')->findOneById($docid);
        $form = $this->createForm(CommentJournalType::class, $comment, [
            'action' => '#',
            'method' => 'POST',
        ] );
        $form->handleRequest($request);
        $request_url = $this->generateUrl('comment_ajax_edit', ['id' => $comment->getId(), 'courseid'=> $courseid, 'docid'=> $docid, 'source'=> $source]);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            if ($source!='doc') {
                $return = "success";
                return new Response($return, 200, array('Content-Type' => 'application/json'));
            }
            return $this->redirectToRoute('doc_show', ['id' => $doc->getId(), 'courseid' => $courseid, 'target' => $doc->getId()]);
        }

        return $this->render('comment/ajax.html.twig', [
            'form' => $form->createView(),
            'comment' => $comment,
            'request_url' => $request_url,
            'doc' => $doc,
            'courseid' => $courseid,
        ]);
    }



    /**
     * @Route("/{docid}/ajax_show", name="comment_ajax_show", methods={"GET","POST"})
     */
    public function ajax_show(string $docid): Response
    {
        $doc = $this->getDoctrine()->getManager()->getRepository('App:Doc')->find($docid);
        return $this->render('comment/ajax_show.html.twig', [
            'doc' => $doc,
            'courseid' => $doc->getCourse()->getId(),
        ]);
    }

    /**
     * @Route("/{courseid}/{docid}/{source}/{id}/edit", name="comment_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Permissions $permissions, Comment $comment, $docid, $courseid, $source): Response
    {
        $header = 'End Comment Edit';
        $doc = $this->getDoctrine()->getManager()->getRepository('App:Doc')->findOneById($docid);
        $course = $this->getDoctrine()->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $role = $permissions->getCourseRole($courseid);
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('notice', 'Your end comment has been updated.');
            if ($source!='doc') {
                return $this->redirectToRoute('journal_index', ['docid' => $doc->getId(), 'userid' => $doc->getUser()->getId(), 'courseid' => $courseid]);
            }
            return $this->redirectToRoute('doc_show', ['id' => $doc->getId(), 'courseid' => $courseid, 'target' => $doc->getId()]);
        }

        return $this->render('comment/edit.html.twig', [
            'comment' => $comment,
            'header' => $header,
            'doc' => $doc,
            'course' => $course,
            'role' => $role,
            'source' => $source,
            'form' => $form->createView(),
        ]);
    }

    /**
     *  Release All Hidden
     * @Route("/release_all_comments/{courseid}/{findtype}" , name="release_all_comments")
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
        $docs = $docRepository->findDocComments($course, $user);
        foreach($docs as $doc){
            if ($doc->getComments()){
                foreach ($doc->getComments() as $comment) {
                    $comment->setAccess('Private');
                    $entityManager->persist($comment);
                }
            }
        }
        $this->addFlash('notice', 'Your end comment has been released.');
        $entityManager->flush();
        return $this->redirectToRoute('doc_index', ['courseid' => $courseid, 'findtype' => $findtype]);

    }

    /**
     * @Route("/{courseid}/{docid}/{source}/{id}", name="comment_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Comment $comment, $docid, $courseid, $source): Response
    {
        $doc = $this->getDoctrine()->getManager()->getRepository('App:Doc')->findOneById($docid);
        if ($this->isCsrfTokenValid('delete'.$comment->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($comment);
            $entityManager->flush();
        }
        $this->addFlash('notice', 'Your end comment has been deleted.');
        if ($source!='doc') {
            return $this->redirectToRoute('journal_index', ['docid' => $doc->getId(), 'userid' => $doc->getUser()->getId(), 'courseid' => $courseid]);
        }
        return $this->redirectToRoute('doc_show', ['id' => $doc->getId(), 'courseid' => $courseid, 'target' => $doc->getId()]);
    }
}
