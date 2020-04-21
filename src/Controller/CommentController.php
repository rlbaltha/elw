<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Form\CommentType;
use App\Repository\CommentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/comment")
 */
class CommentController extends AbstractController
{

    /**
     * @Route("/{courseid}/{docid}/{source}/new", name="comment_new", methods={"GET","POST"})
     */
    public function new(Request $request, $docid, $courseid, $source): Response
    {

        $username = $this->getUser()->getUsername();
        $user = $this->getDoctrine()->getManager()->getRepository('App:User')->findOneByUsername($username);
        $doc = $this->getDoctrine()->getManager()->getRepository('App:Doc')->findOneById($docid);
        $comment = new Comment();
        $comment->setUser($user);
        $comment->setDoc($doc);
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($comment);
            $entityManager->flush();

            if ($source!='doc') {
                return $this->redirectToRoute('journal_index', ['id' => $doc->getId(), 'courseid' => $courseid]);
            }
            return $this->redirectToRoute('doc_show', ['id' => $doc->getId(), 'courseid' => $courseid]);
        }

        return $this->render('comment/new.html.twig', [
            'comment' => $comment,
            'doc' => $doc,
            'form' => $form->createView(),
        ]);
    }


    /**
     * @Route("/{courseid}/{docid}/{source}/{id}/edit", name="comment_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Comment $comment, $docid, $courseid, $source): Response
    {
        $doc = $this->getDoctrine()->getManager()->getRepository('App:Doc')->findOneById($docid);
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            if ($source!='doc') {
                return $this->redirectToRoute('journal_index', ['id' => $doc->getId(), 'courseid' => $courseid]);
            }
            return $this->redirectToRoute('doc_show', ['id' => $doc->getId(), 'courseid' => $courseid]);
        }

        return $this->render('comment/edit.html.twig', [
            'comment' => $comment,
            'doc' => $doc,
            'source' => $source,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{courseid}/{docid}/{source}/{id}", name="comment_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Comment $comment, $docid, $courseid, $source): Response
    {
        if ($this->isCsrfTokenValid('delete'.$comment->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($comment);
            $entityManager->flush();
        }

        return $this->redirectToRoute('doc_show', ['id' => $docid, 'courseid' => $courseid]);
    }
}
