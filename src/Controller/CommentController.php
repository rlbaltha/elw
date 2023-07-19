<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Notification;
use App\Form\CommentJournalType;
use App\Form\CommentType;
use App\Repository\CommentRepository;
use App\Repository\DocRepository;
use App\Service\Permissions;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @Route("/comment")
 */
class CommentController extends AbstractController
{
    /** @var ManagerRegistry */
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @Route("/{courseid}/{docid}/{source}/ajax_new", name="comment_ajax_new", methods={"GET","POST"})
     */
    public function ajax_new(Request $request, Permissions $permissions, $docid, $courseid, $source): Response
    {
        $username = $this->getUser()->getUsername();
        $user = $this->doctrine->getManager()->getRepository('App:User')->findOneByUsername($username);
        $role = $permissions->getCourseRole($courseid);
        $doc = $this->doctrine->getManager()->getRepository('App:Doc')->findOneById($docid);
        $request_url = $this->generateUrl('comment_ajax_new', ['courseid'=> $courseid, 'docid'=> $docid, 'source'=> $source]);
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
            $entityManager = $this->doctrine->getManager();
            $entityManager->persist($comment);

            if ($source=='journal'){
                $notification = new Notification();
                $notification->setAction('journal_comment');
                $notification->setDocid($docid);
                $notification->setCourseid($courseid);
                $notification->setFromUser($user);
                $notification->setForUser($doc->getUser()->getId());
                $entityManager->persist($notification);
            }

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
            'request_url' => $request_url,
        ]);
    }

    /**
     * @Route("/{docid}/{source}/{id}/ajax_edit", name="comment_ajax_edit", methods={"GET","POST"})
     */
    public function ajax_edit(SerializerInterface $serializer, Request $request, Comment $comment, string $docid, string $source, string $id): Response
    {
        $doc = $this->doctrine->getManager()->getRepository('App:Doc')->findOneById($docid);
        $form = $this->createForm(CommentJournalType::class, $comment, [
            'action' => '#',
            'method' => 'POST',
        ] );
        $form->handleRequest($request);
        $request_url = $this->generateUrl('comment_ajax_edit', ['id' => $comment->getId(),  'docid'=> $docid, 'source'=> $source]);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->getManager()->flush();
            if ($source!='doc') {
                $return = "success";
                return new Response($return, 200, array('Content-Type' => 'application/json'));
            }
            return $this->redirectToRoute('doc_show', ['id' => $doc->getId(),  'target' => $doc->getId()]);
        }

        return $this->render('comment/ajax.html.twig', [
            'form' => $form->createView(),
            'comment' => $comment,
            'request_url' => $request_url,
            'doc' => $doc,
        ]);
    }

    /**
     * @Route("/{courseid}/{docid}/ajax_show", name="comment_ajax_show", methods={"GET","POST"})
     */
    public function ajax_show(Permissions $permissions, string $docid, string $courseid): Response
    {
        $role = $permissions->getCourseRole($courseid);
        $doc = $this->doctrine->getManager()->getRepository('App:Doc')->find($docid);
        return $this->render('comment/ajax_show.html.twig', [
            'doc' => $doc,
            'courseid' => $doc->getCourse()->getId(),
            'role' => $role,
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

        $course = $this->doctrine->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $username = $this->getUser()->getUsername();
        $user = $this->doctrine->getManager()->getRepository('App:User')->findOneByUsername($username);
        $entityManager = $this->doctrine->getManager();
        $docs = $docRepository->findDocComments($course, $user);
        foreach($docs as $doc){
            if ($doc->getComments()){
                foreach ($doc->getComments() as $comment) {
                    if ($comment->getAccess() !== 'Private'){
                        $comment->setAccess('Private');
                        // if get comment on my doc from someone else
                        if ($comment->getUser() !== $comment->getDoc()->getUser()){
                            $notification = new Notification();
                            $notification->setAction('comment');
                            $notification->setDocid($doc->getId());
                            $notification->setCourseid($courseid);
                            $notification->setFromUser($user);
                            $notification->setForUser($doc->getUser()->getId());
                            $entityManager->persist($notification);
                            $entityManager->persist($comment);
                            }
                        // if get comment on review of my doc from some else
                        elseif  (($comment->getDoc()->getOrigin()) and ($comment->getUser() !== $comment->getDoc()->getOrigin()->getUser())){
                            $notification = new Notification();
                            $notification->setAction('review_comment');
                            $notification->setDocid($doc->getOrigin()->getId());
                            $notification->setReviewid($doc->getId());
                            $notification->setCourseid($courseid);
                            $notification->setFromUser($user);
                            $notification->setForUser($doc->getOrigin()->getUser()->getId());
                            $entityManager->persist($notification);
                            $entityManager->persist($comment);
                        }

                    }
                }
            }
        }
        $this->addFlash('notice', 'Your end comment has been released.');
        $entityManager->flush();
        return $this->redirectToRoute('doc_index', ['courseid' => $courseid, 'findtype' => $findtype]);

    }

    /**
     * @Route("/{courseid}/{docid}/{target}/{source}/{id}/delete", name="comment_delete", methods={"POST"})
     */
    public function delete(Request $request, Comment $comment, $docid, $courseid, $source, $target): Response
    {
        $doc = $this->doctrine->getManager()->getRepository('App:Doc')->findOneById($docid);
        if ($this->isCsrfTokenValid('delete'.$comment->getId(), $request->request->get('_token'))) {
            $entityManager = $this->doctrine->getManager();
            $entityManager->remove($comment);
            $entityManager->flush();
        }
        $this->addFlash('notice', 'Your end comment has been deleted.');
        if ($source!='doc') {
            return $this->redirectToRoute('journal_index', ['docid' => $doc->getId(), 'userid' => $doc->getUser()->getId(), 'courseid' => $courseid]);
        }
        return $this->redirectToRoute('doc_show', ['id' => $docid, 'courseid' => $courseid, 'target' => $target]);
    }


    /**
     * checks if the current user is allowed to view the doc
     * if not, throws an access denied exception
     * if so, returns true
     */
    public function isAllowedToView($courseid, $doc)
    {
        //grab our current user's role in our current course
        $currentUserRole = $this->getCourseRole($courseid);
        //test if user is allowed to see the doc
        if($currentUserRole === 'Instructor' or $doc->getAccess()==='Shared' or $this->isOwner($doc) or $doc->getOrigin()==='Shared'){
            return true;
        } else {
            throw new AccessDeniedException();
        }
    }

    /**
     * @Route("/{courseid}/{docid}/{target}/{source}/new", name="comment_new", methods={"GET","POST"})
     */
    public function new(Request $request, Permissions $permissions, $docid, $courseid, $source, $target): Response
    {

        $header = 'End Comment New';
        $username = $this->getUser()->getUsername();
        $user = $this->doctrine->getManager()->getRepository('App:User')->findOneByUsername($username);
        $role = $permissions->getCourseRole($courseid);
        $course = $this->doctrine->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $doc = $this->doctrine->getManager()->getRepository('App:Doc')->findOneById($target);
        $comment = new Comment();
        if ($role=='Instructor' and $source=='doc') {
            $comment->setAccess('Hidden');
        }
        $comment->setUser($user);
        $comment->setDoc($doc);
        $comment->setType('Holistic Feedback');
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->doctrine->getManager();
            $request_data = $request->request->get('comment');
            $access = $request_data['access'];
                // if get comment on my doc from someone else
                if ($access === 'Private' and $comment->getUser() !== $comment->getDoc()->getUser()){
                    $notification = new Notification();
                    $notification->setAction('comment');
                    $notification->setDocid($doc->getId());
                    $notification->setCourseid($courseid);
                    $notification->setFromUser($user);
                    $notification->setForUser($doc->getUser()->getId());
                    $entityManager->persist($notification);
                }
                // if get comment on review of my doc from some else
                elseif  (($access === 'Private') and ($comment->getDoc()->getOrigin()) and ($comment->getUser() !== $comment->getDoc()->getOrigin()->getUser())){
                    $notification = new Notification();
                    $notification->setAction('review_comment');
                    $notification->setDocid($doc->getOrigin()->getId());
                    $notification->setReviewid($doc->getId());
                    $notification->setCourseid($courseid);
                    $notification->setFromUser($user);
                    $notification->setForUser($doc->getOrigin()->getUser()->getId());
                    $entityManager->persist($notification);
                }
            $entityManager->persist($comment);
            $entityManager->flush();
            $this->addFlash('notice', 'Your end comment has been added.');
            if ($source!='doc') {
                return $this->redirectToRoute('comment_ajax_show', ['docid' => $doc->getId(), 'id' => $comment->getId()]);
            }
            return $this->redirectToRoute('doc_show', ['id' => $docid, 'courseid' => $courseid, 'target' => $target]);
        }

        return $this->render('comment/new.html.twig', [
            'comment' => $comment,
            'doc' => $doc,
            'source' => $source,
            'course' => $course,
            'role' => $role,
            'header' => $header,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{courseid}/{docid}/{target}/{source}/{id}/edit", name="comment_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Permissions $permissions, Comment $comment, $docid, $courseid, $source, $target): Response
    {
        $header = 'End Comment Edit';
        $username = $this->getUser()->getUsername();
        $user = $this->doctrine->getManager()->getRepository('App:User')->findOneByUsername($username);
        $doc = $this->doctrine->getManager()->getRepository('App:Doc')->findOneById($docid);
        $course = $this->doctrine->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $role = $permissions->getCourseRole($courseid);
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->doctrine->getManager();
            $entityManager->persist($comment);
            $request_data = $request->request->get('comment');
            $access = $request_data['access'];

            // if get comment on my doc from someone else
            if ($access === 'Private' and $comment->getUser() !== $comment->getDoc()->getUser()){
                $notification = new Notification();
                $notification->setAction('comment');
                $notification->setDocid($doc->getId());
                $notification->setCourseid($courseid);
                $notification->setFromUser($user);
                $notification->setForUser($doc->getUser()->getId());
                $entityManager->persist($notification);
                $entityManager->persist($comment);
            }
            // if get comment on review of my doc from some else
            elseif  (($access === 'Private') and ($comment->getDoc()->getOrigin()) and ($comment->getUser() !== $comment->getDoc()->getOrigin()->getUser())){
                $notification = new Notification();
                $notification->setAction('review_comment');
                $notification->setDocid($doc->getOrigin()->getId());
                $notification->setReviewid($doc->getId());
                $notification->setCourseid($courseid);
                $notification->setFromUser($user);
                $notification->setForUser($doc->getOrigin()->getUser()->getId());
                $entityManager->persist($notification);
                $entityManager->persist($comment);
            }

            $this->doctrine->getManager()->flush();
            $this->addFlash('notice', 'Your end comment has been updated.');
            if ($source!='doc') {
                return $this->redirectToRoute('journal_index', ['docid' => $doc->getId(), 'userid' => $doc->getUser()->getId(), 'courseid' => $courseid]);
            }
            return $this->redirectToRoute('doc_show', ['id' => $docid, 'courseid' => $courseid, 'target' => $target]);
        }

        return $this->render('comment/edit.html.twig', [
            'comment' => $comment,
            'header' => $header,
            'doc' => $doc,
            'course' => $course,
            'role' => $role,
            'source' => $source,
            'target' => $target,
            'form' => $form->createView(),
        ]);
    }


}
