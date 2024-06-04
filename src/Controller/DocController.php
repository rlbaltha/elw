<?php

namespace App\Controller;

use App\Entity\Doc;
use App\Entity\Notification;
use App\Form\DocType;
use App\Repository\DocRepository;
use App\Service\Lti;
use App\Service\Permissions;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Caxy\HtmlDiff\HtmlDiff;
//use Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\Persistence\ManagerRegistry;
//use Knp\Snappy\Pdf;
use Pontedilana\PhpWeasyPrint\Pdf;
use Pontedilana\WeasyprintBundle\WeasyPrint\Response\PdfResponse;




/**
 * @Route("/doc")
 */
class DocController extends AbstractController
{
    /** @var RequestStack */
    private $requestStack;


    /** @var ManagerRegistry */
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine, RequestStack $requestStack)
    {
        $this->doctrine = $doctrine;
        $this->requestStack = $requestStack;
    }

    /**
     * @Route("/{courseid}/{findtype}/index", name="doc_index", methods={"GET"}, defaults={"findtype":"MyDocs"})
     */
    public function index(PaginatorInterface $paginator, Request $request, Permissions $permissions, DocRepository $docRepository, $courseid, $findtype): Response
    {
        $allowed = ['Student', 'Instructor'];
        $permissions->restrictAccessTo($courseid, $allowed);

        $this->requestStack->getSession()->set('referrer', $request->getRequestUri());
        $role = $permissions->getCourseRole($courseid);
        $page_limit = 50;

        $course = $this->doctrine->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $username = $this->getUser()->getUsername();
        $user = $this->doctrine->getManager()->getRepository('App:User')->findOneByUsername($username);
        $hidden_reviews = $docRepository->countHiddenReviews($course);
        $hidden_comments = $docRepository->countHiddenComments($course);
        $classlists = $this->doctrine->getManager()->getRepository('App:Classlist')->findByCourseid($courseid);

        if ($findtype == 'SharedDocs') {
            $querybuilder = $docRepository->findSharedDocs($course, $role);
            $docs = $paginator->paginate(
                $querybuilder, /* query NOT result */
                $request->query->getInt('page', 1), /*page number*/
                $page_limit /*limit per page*/
            );
            $header = 'Shared Docs';
        } else {
            $querybuilder = $docRepository->findMyDocs($course, $user);
            $docs = $paginator->paginate(
                $querybuilder, /* query NOT result */
                $request->query->getInt('page', 1), /*page number*/
                $page_limit /*limit per page*/
            );
            $header = 'My Docs';
        }

        return $this->render('doc/index.html.twig', [
            'header' => $header,
            'page_limit' => $page_limit,
            'findtype' => $findtype,
            'docs' => $docs,
            'course' => $course,
            'classlists' => $classlists,
            'role' => $role,
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

        $course = $this->doctrine->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $username = $this->getUser()->getUsername();
        $user = $this->doctrine->getManager()->getRepository('App:User')->findOneByUsername($username);
        $entityManager = $this->doctrine->getManager();
        $docs = $docRepository->findHiddenDocs($course, $user);
        $now = new \DateTime('now');
        foreach ($docs as $doc) {
            $doc->setAccess('Private');
            $doc->setReleasedate($now);
            $entityManager->persist($doc);

            $notification = new Notification();
            $notification->setAction('review');
            $notification->setDocid($doc->getOrigin()->getId());
            $notification->setReviewid($doc->getId());
            $notification->setCourseid($courseid);
            $notification->setFromUser($user);
            $notification->setForUser($doc->getOrigin()->getUser()->getId());
            $entityManager->persist($notification);
        }
        $entityManager->flush();
        $this->addFlash('notice', 'All documents have been released.');

        return $this->redirectToRoute('doc_index', ['courseid' => $courseid, 'findtype' => $findtype]);

    }


    /**
     * @Route("/{courseid}/{findtype}/{userid}/byuser", name="doc_byuser", methods={"GET"}, defaults={"findtype":"MyDocs"})
     */
    public function byuser(PaginatorInterface $paginator, Request $request, Permissions $permissions, DocRepository $docRepository, $courseid, $userid): Response
    {
        $findtype = 'byuser';
        $allowed = ['Student', 'Instructor'];
        $permissions->restrictAccessTo($courseid, $allowed);
        $this->requestStack->getSession()->set('referrer', $request->getRequestUri());
        $role = $permissions->getCourseRole($courseid);
        $page_limit = 50;
        $course = $this->doctrine->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $hidden_reviews = $docRepository->countHiddenReviews($course);
        $hidden_comments = $docRepository->countHiddenComments($course);
        $classlists = $this->doctrine->getManager()->getRepository('App:Classlist')->findByCourseid($courseid);
        $user = $this->doctrine->getManager()->getRepository('App:User')->findOneById($userid);
        $course = $this->doctrine->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $querybuilder = $docRepository->findByUser($course, $role, $user);
        $docs = $paginator->paginate(
            $querybuilder, /* query NOT result */
            $request->query->getInt('page', 1), /*page number*/
            $page_limit /*limit per page*/
        );
        $header = 'Docs by ' . $user->getFirstname() . ' ' . $user->getLastname();
        return $this->render('doc/index.html.twig', [
            'header' => $header,
            'page_limit' => $page_limit,
            'docs' => $docs,
            'course' => $course,
            'role' => $role,
            'findtype' => $findtype,
            'classlists' => $classlists,
            'hidden_comments' => $hidden_comments,
            'hidden_reviews' => $hidden_reviews
        ]);
    }

    /**
     * @Route("/{courseid}/{findtype}/{projectid}/byproject", name="doc_byproject", methods={"GET"}, defaults={"findtype":"byproject"})
     */
    public function byProject(PaginatorInterface $paginator, Request $request, Permissions $permissions, DocRepository $docRepository, string $courseid, string $projectid): Response
    {
        $findtype = 'byproject';
        $allowed = ['Student', 'Instructor'];
        $permissions->restrictAccessTo($courseid, $allowed);
        $this->requestStack->getSession()->set('referrer', $request->getRequestUri());
        $role = $permissions->getCourseRole($courseid);
        $page_limit = 50;
        $course = $this->doctrine->getManager()->getRepository('App:Course')->find($courseid);
        $hidden_reviews = $docRepository->countHiddenReviews($course);
        $hidden_comments = $docRepository->countHiddenComments($course);
        $classlists = $this->doctrine->getManager()->getRepository('App:Classlist')->findByCourseid($courseid);
        $project = $this->doctrine->getManager()->getRepository('App:Project')->find($projectid);
        $querybuilder = $docRepository->findByProject($course, $role, $project);
        $docs = $paginator->paginate(
            $querybuilder, /* query NOT result */
            $request->query->getInt('page', 1), /*page number*/
            $page_limit /*limit per page*/
        );
        $header = 'Docs for Project ' . $project->getName();
        return $this->render('doc/index.html.twig', [
            'header' => $header,
            'page_limit' => $page_limit,
            'docs' => $docs,
            'course' => $course,
            'role' => $role,
            'findtype' => $findtype,
            'classlists' => $classlists,
            'hidden_comments' => $hidden_comments,
            'hidden_reviews' => $hidden_reviews
        ]);
    }


    /**
     * @Route("/{courseid}/{projectid}/new", name="doc_new", methods={"GET","POST"})
     */
    public function new(Request $request, Permissions $permissions, string $courseid, string $projectid): Response
    {
        $allowed = ['Instructor', 'Student'];
        $permissions->restrictAccessTo($courseid, $allowed);

        $doc = new Doc();
        $username = $this->getUser()->getUsername();
        $user = $this->doctrine->getManager()->getRepository('App:User')->findOneByUsername($username);
        $course = $this->doctrine->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $project = $this->doctrine->getManager()->getRepository('App:Project')->find($projectid);
        $stages = $project->getStages();
        $doc->setUser($user);
        $doc->setCourse($course);
        $doc->setProject($project);
        $doc->setStage($stages[0]);
        $doc->setTitle('Essay for ' . $project->getName());
        $entityManager = $this->doctrine->getManager();
        $entityManager->persist($doc);
        $entityManager->flush();
        return $this->redirectToRoute('doc_edit', ['id' => $doc->getId(), 'courseid' => $courseid]);

    }

    /**
     * @Route("/{courseid}/{docid}/review", name="doc_review", methods={"GET","POST"})
     */
    public function review(Request $request, Permissions $permissions, $courseid, $docid): Response
    {
        $allowed = ['Instructor', 'Student'];
        $permissions->restrictAccessTo($courseid, $allowed);
        $now = new \DateTime('now');
        $doc = new Doc();
        $username = $this->getUser()->getUsername();
        $user = $this->doctrine->getManager()->getRepository('App:User')->findOneByUsername($username);
        $course = $this->doctrine->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $origin = $this->doctrine->getManager()->getRepository('App:Doc')->findOneById($docid);
        $doc_title = 'for ' . $origin->getUser()->getFirstname() . ' ' . $origin->getUser()->getLastname();
        $doc->setUser($user);
        $doc->setCourse($course);
        $doc->setOrigin($origin);
        $doc->setTitle($doc_title);
        $doc->setBody($origin->getBody());
        ($permissions->getCourseRole($courseid) === 'Instructor' ? $doc->setAccess('Hidden') : ($doc->setAccess('Review') AND $doc->setReleasedate($now)));
        $doc->setProject($origin->getProject());
        $doc->setStage($origin->getStage());
        $entityManager = $this->doctrine->getManager();
        $entityManager->persist($doc);
        $entityManager->flush();
        if ($permissions->getCourseRole($courseid) !== 'Instructor'){
            $notification = new Notification();
            $notification->setAction('review');
            $notification->setDocid($docid);
            $notification->setReviewid($doc->getId());
            $notification->setCourseid($courseid);
            $notification->setFromUser($user);
            $notification->setForUser($origin->getUser()->getId());
            $entityManager->persist($notification);
            $entityManager->flush();
        }

        return $this->redirectToRoute('doc_edit', ['id' => $doc->getId(), 'courseid' => $courseid]);
    }

    /**
     * @Route("/{id}/{courseid}/{target}/show", name="doc_show", methods={"GET"}, defaults={"target" = "0" })
     */
    public function show(Doc $doc, string $courseid, Permissions $permissions, Request $request, Lti $lti, string $target): Response
    {
        $permissions->isAllowedToView($courseid, $doc);

        $course = $this->doctrine->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $scores = [];
        if ($doc->getAgsResultId() != null) {
            $scores = $lti->getLtiResult($doc->getAgsResultId());
        }

        $role = $permissions->getCourseRole($courseid);
        if ($doc->getProject()) {
            $markupsets = $doc->getProject()->getMarkupsets();
        } elseif ($course->getMarkupsets()) {
            $markupsets = $course->getMarkupsets();
        }
        else {
            $markupsets = [];
        }


        return $this->render('doc/show.html.twig', [
            'doc' => $doc,
            'course' => $course,
            'scores' => $scores,
            'markupsets' => $markupsets,
            'role' => $role,
            'target' => $target
        ]);
    }

    /**
     * @Route("/{id1}/{id2}/{courseid}/{order}/diff", name="doc_diff", methods={"GET"}, defaults={"order" = "0" })
     */
    public function diff(string $id1, string $id2, string $courseid, string $order, Permissions $permissions): Response
    {
        $course = $this->doctrine->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $doc1 = $this->doctrine->getManager()->getRepository('App:Doc')->find($id1);
        $doc2 = $this->doctrine->getManager()->getRepository('App:Doc')->find($id2);
        $permissions->isAllowedToView($courseid, $doc1);
        $permissions->isAllowedToView($courseid, $doc2);
        if ($order==="0" and $doc1->getCreated() > $doc2->getCreated()) {
            $doc_temp = $doc2;
            $doc2 = $doc1;
            $doc1 = $doc_temp;
        }
        $doc1str = $doc1->getBody();
        $doc2str = $doc2->getBody();
        $htmlDiff = new HtmlDiff($doc1str, $doc2str);
        $diff = $htmlDiff->build();

        $role = $permissions->getCourseRole($courseid);
        return $this->render('doc/diff.html.twig', [
            'diff' => $diff,
            'doc1' => $doc1,
            'doc2' => $doc2,
            'role' => $role,
            'course' => $course,
        ]);
    }


    /**
     * @Route("/{courseid}/{docid}/{source}/ags_score_view", name="ags_score_view", methods={"GET"})
     */
    public function ags_score_view(string $docid, string $courseid, Permissions $permissions, Request $request, Lti $lti, string $source): Response
    {
        $doc = $this->doctrine->getManager()->getRepository('App:Doc')->find($docid);
        $role = $permissions->getCourseRole($courseid);
        $scores = [];
        $column = '';
        if ($doc->getAgsResultId() != null) {
            $ltiid = strstr($doc->getAgsResultId(), "/results", true);
            $column = $this->doctrine->getManager()->getRepository('App:LtiAgs')->findOneByLtiid($ltiid)->getLabel();
            $scores = $lti->getLtiResult($doc->getAgsResultId());
        }
        return $this->render('lti/lti_ags_ajax.html.twig', [
            'column' => $column,
            'scores' => $scores,
            'doc' => $doc,
            'role' => $role,
            'source' => $source
        ]);
    }

//    /**
//     * @Route("/{id}/{courseid}/doc_display", name="doc_display", methods={"GET"})
//     */
//    public function docDisplay(Doc $doc, string $courseid, Permissions $permissions)
//    {
//        $permissions->isAllowedToView($courseid, $doc);
//        $course = $this->doctrine->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
//        if ($doc->getProject()->getMarkupsets()) {
//            $markupsets = $doc->getProject()->getMarkupsets();
//        } else {
//            $markupsets = $course->getMarkupsets();
//        }
//        return $this->render('doc/pdf.html.twig', [
//            'doc' => $doc,
//            'markupsets' => $markupsets,
//        ]);
//
//    }

    /**
     * @Route("/pdf", name="doc_pdf", methods={"POST"})
     */
    public function pdf(Permissions $permissions, Request $request, Pdf $pdf)
    {
        $doc_html = $request->get('html2pdf');
        $title = $request->get('title');
        $title_esc = str_replace('/', '-', $title);
        $docid = $request->get('docid');
        $courseid = $request->get('courseid');

        //check to see if request is a diff plus general permissions
        if ($docid!=0) {
            $doc = $this->doctrine->getManager()->getRepository('App:Doc')->find($docid);
            $origin = $doc->getOrigin();
            $permissions->isAllowedToView($courseid, $origin);
        }
        $html = $this->renderView('doc/pdf.html.twig', [
            'doc_html' => $doc_html,
        ]);
        $filename = 'PDF_of_' . $title_esc . '.pdf';
        return new PdfResponse(
            $pdf->getOutputFromHtml($html),
            $filename
        );
    }

    /**
     * @Route("/{id}/{courseid}/edit", name="doc_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Permissions $permissions, Doc $doc, string $courseid): Response
    {
        $allowed = ['Instructor', 'Student'];
        $username = $this->getUser()->getUsername();
        $user = $this->doctrine->getManager()->getRepository('App:User')->findOneByUsername($username);
        $entityManager = $this->doctrine->getManager();
        $permissions->restrictAccessTo($courseid, $allowed);
        $permissions->isOwner($doc);
        $role = $permissions->getCourseRole($courseid);
        if ($role == 'Instructor' and $doc->getOrigin()) {
            $choices = ['Hidden' => 'Hidden', 'Private' => 'Private'];
        } elseif ($role == 'Student' and $doc->getAccess() == 'Review') {
            $choices = ['Review' => 'Review'];
        } else {
            $choices = ['Shared' => 'Shared', 'Private' => 'Private'];
        }
        $stages = $doc->getProject()->getStages();
        $course = $this->doctrine->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $options = ['courseid' => $courseid, 'choices' => $choices, 'stages' => $stages];
        $form = $this->createForm(DocType::class, $doc, ['attr' => ['id' => 'doc-form'], 'options' => $options]);
        $form->handleRequest($request);
        $markupsets = $doc->getProject()->getMarkupsets();
        $now = new \DateTime('now');
        if ($form->isSubmitted() && $form->isValid()) {
            if ($role == 'Instructor' and $doc->getAccess()=='Private' and $doc->getOrigin()) {
                $doc->setReleasedate($now);

                $notification = new Notification();
                $notification->setAction('review');
                $notification->setDocid($doc->getOrigin()->getId());
                $notification->setReviewid($doc->getId());
                $notification->setCourseid($courseid);
                $notification->setFromUser($user);
                $notification->setForUser($doc->getOrigin()->getUser()->getId());
                $entityManager->persist($notification);
                $entityManager->persist($doc);
            }
            $this->doctrine->getManager()->flush();
            $this->addFlash('notice', 'Your  document has been saved.');
            return $this->redirectToRoute('doc_show', ['id' => $doc->getId(), 'courseid' => $courseid, 'target' => $doc->getId()]);
        }
        return $this->render('doc/edit.html.twig', [
            'doc' => $doc,
            'markupsets' => $markupsets,
            'form' => $form->createView(),
        ]);
    }


    /**
     * Saves via AJAX
     * @Route("/{courseid}/{id}/autosave", name="doc_autosave", methods={"POST"})
     */
    public function autosave(Request $request, Permissions $permissions, Doc $doc, string $courseid)
    {
        $allowed = ['Instructor', 'Student'];
        $permissions->restrictAccessTo($courseid, $allowed);
        $count = 0;
        $update = $request->request->get('docBody');
        $count = $request->request->get('count');

        if (!$doc) {
            throw $this->createNotFoundException('Unable to find Doc entity.');
        }

        $doc->setBody($update);
        $doc->setWordcount($count);
        $entityManager = $this->doctrine->getManager();
        $entityManager->persist($doc);
        $entityManager->flush();

        $return = "success";
        return new JsonResponse($return);
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
        ($access === 'Shared' ? $doc->setAccess('Private') : $doc->setAccess('Shared'));
        $entityManager = $this->doctrine->getManager();
        $entityManager->persist($doc);
        $entityManager->flush();

        return $this->redirectToRoute('doc_show', ['id' => $doc->getId(), 'courseid' => $courseid, 'target' => $doc->getId()]);
    }

    /**
     * @Route("/{courseid}/{id}/delete", name="doc_delete", methods={"POST"})
     */
    public function delete(Request $request, Permissions $permissions, Doc $doc, string $courseid): Response
    {
        $allowed = ['Instructor', 'Student'];
        $permissions->restrictAccessTo($courseid, $allowed);
        $permissions->isOwner($doc);

        if ($this->isCsrfTokenValid('delete' . $doc->getId(), $request->request->get('_token'))) {
            $entityManager = $this->doctrine->getManager();
            $entityManager->remove($doc);
            $entityManager->flush();
        }

        return $this->redirectToRoute('doc_index', ['courseid' => $courseid]);
    }
}
