<?php

namespace App\Controller;

use App\Entity\Doc;
use App\Form\DocType;
use App\Repository\DocRepository;
use App\Service\Lti;
use App\Service\Permissions;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Extension\AbstractExtension;
use Nucleos\DompdfBundle\Wrapper\DompdfWrapperInterface;
use Caxy\HtmlDiff\HtmlDiff;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse;
use Knp\Snappy\Pdf;


/**
 * @Route("/doc")
 */
class DocController extends AbstractController
{

    /** @var DompdfWrapperInterface */
    private $wrapper;

    /** @var Pdf  */
    private $pdf;

    public function __construct(DompdfWrapperInterface $wrapper, Pdf $pdf)
    {
        $this->wrapper = $wrapper;
        $this->pdf = $pdf;
    }

    /**
     * @Route("/{courseid}/{findtype}/index", name="doc_index", methods={"GET"}, defaults={"findtype":"MyDocs"})
     */
    public function index(PaginatorInterface $paginator, Request $request, Permissions $permissions, DocRepository $docRepository, $courseid, $findtype): Response
    {
        $allowed = ['Student', 'Instructor'];
        $permissions->restrictAccessTo($courseid, $allowed);
        $role = $permissions->getCourseRole($courseid);
        $page_limit = 50;

        $course = $this->getDoctrine()->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $username = $this->getUser()->getUsername();
        $user = $this->getDoctrine()->getManager()->getRepository('App:User')->findOneByUsername($username);
        $hidden_reviews = $docRepository->countHiddenReviews($course);
        $hidden_comments = $docRepository->countHiddenComments($course);
        $classlists = $this->getDoctrine()->getManager()->getRepository('App:Classlist')->findByCourseid($courseid);

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

        $course = $this->getDoctrine()->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $username = $this->getUser()->getUsername();
        $user = $this->getDoctrine()->getManager()->getRepository('App:User')->findOneByUsername($username);
        $entityManager = $this->getDoctrine()->getManager();
        $docs = $docRepository->findHiddenDocs($course, $user);
        foreach ($docs as $doc) {
            $doc->setAccess('Private');
            $entityManager->persist($doc);
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
        $role = $permissions->getCourseRole($courseid);
        $page_limit = 50;
        $course = $this->getDoctrine()->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $hidden_reviews = $docRepository->countHiddenReviews($course);
        $hidden_comments = $docRepository->countHiddenComments($course);
        $classlists = $this->getDoctrine()->getManager()->getRepository('App:Classlist')->findByCourseid($courseid);
        $user = $this->getDoctrine()->getManager()->getRepository('App:User')->findOneById($userid);
        $course = $this->getDoctrine()->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
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
        $role = $permissions->getCourseRole($courseid);
        $page_limit = 50;
        $course = $this->getDoctrine()->getManager()->getRepository('App:Course')->find($courseid);
        $hidden_reviews = $docRepository->countHiddenReviews($course);
        $hidden_comments = $docRepository->countHiddenComments($course);
        $classlists = $this->getDoctrine()->getManager()->getRepository('App:Classlist')->findByCourseid($courseid);
        $project = $this->getDoctrine()->getManager()->getRepository('App:Project')->find($projectid);
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
        $user = $this->getDoctrine()->getManager()->getRepository('App:User')->findOneByUsername($username);
        $course = $this->getDoctrine()->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $project = $this->getDoctrine()->getManager()->getRepository('App:Project')->find($projectid);
        $stages = $project->getStages();
        $doc->setUser($user);
        $doc->setCourse($course);
        $doc->setProject($project);
        $doc->setStage($stages[0]);
        $doc->setTitle('Essay for ' . $project->getName());
        $entityManager = $this->getDoctrine()->getManager();
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

        $doc = new Doc();
        $username = $this->getUser()->getUsername();
        $user = $this->getDoctrine()->getManager()->getRepository('App:User')->findOneByUsername($username);
        $course = $this->getDoctrine()->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $origin = $this->getDoctrine()->getManager()->getRepository('App:Doc')->findOneById($docid);
        $doc_title = 'for ' . $origin->getUser()->getFirstname() . ' ' . $origin->getUser()->getLastname();
        $doc->setUser($user);
        $doc->setCourse($course);
        $doc->setOrigin($origin);
        $doc->setTitle($doc_title);
        $doc->setBody($origin->getBody());
        ($permissions->getCourseRole($courseid) === 'Instructor' ? $doc->setAccess('Hidden') : $doc->setAccess('Review'));
        $doc->setProject($origin->getProject());
        $doc->setStage($origin->getStage());
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($doc);
        $entityManager->flush();
        return $this->redirectToRoute('doc_edit', ['id' => $doc->getId(), 'courseid' => $courseid]);
    }

    /**
     * @Route("/{id}/{courseid}/{target}/show", name="doc_show", methods={"GET"}, defaults={"target" = "0" })
     */
    public function show(Doc $doc, string $courseid, Permissions $permissions, Request $request, Lti $lti, string $target): Response
    {
        $permissions->isAllowedToView($courseid, $doc);

        $course = $this->getDoctrine()->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
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
     * @Route("/{id1}/{id2}/{courseid}/diff", name="doc_diff", methods={"GET"}, defaults={})
     */
    public function diff(string $id1, string $id2, string $courseid, Permissions $permissions): Response
    {
        $course = $this->getDoctrine()->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $doc1 = $this->getDoctrine()->getManager()->getRepository('App:Doc')->find($id1);
        $doc2 = $this->getDoctrine()->getManager()->getRepository('App:Doc')->find($id2);
        $permissions->isAllowedToView($courseid, $doc1);
        $permissions->isAllowedToView($courseid, $doc2);
        if ($doc1->getCreated() > $doc2->getCreated()) {
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
        $doc = $this->getDoctrine()->getManager()->getRepository('App:Doc')->find($docid);
        $role = $permissions->getCourseRole($courseid);
        $scores = [];
        $column = '';
        if ($doc->getAgsResultId() != null) {
            $ltiid = strstr($doc->getAgsResultId(), "/results", true);
            $column = $this->getDoctrine()->getManager()->getRepository('App:LtiAgs')->findOneByLtiid($ltiid)->getLabel();
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

    /**
     * @Route("/{id}/{courseid}/doc_display", name="doc_display", methods={"GET"})
     */
    public function docDisplay(Doc $doc, string $courseid, Permissions $permissions)
    {
        $permissions->isAllowedToView($courseid, $doc);
        $course = $this->getDoctrine()->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        if ($doc->getProject()->getMarkupsets()) {
            $markupsets = $doc->getProject()->getMarkupsets();
        } else {
            $markupsets = $course->getMarkupsets();
        }
        return $this->render('doc/pdf.html.twig', [
            'doc' => $doc,
            'markupsets' => $markupsets,
        ]);

    }

    /**
     * @Route("/pdf", name="doc_pdf", methods={"POST"})
     */
    public function pdf(Permissions $permissions, Request $request)
    {
        $doc_html = $request->get('html2pdf');
        $title = $request->get('title');
        $docid = $request->get('docid');
        $courseid = $request->get('courseid');
        $doc = $this->getDoctrine()->getManager()->getRepository('App:Doc')->find($docid);
        $permissions->isAllowedToView($courseid, $doc);
        $html = $this->renderView('doc/pdf.html.twig', [
            'doc_html' => $doc_html,
        ]);
        $filename = 'PDF_of_' . $title . '.pdf';
        return new PdfResponse(
            $this->pdf->getOutputFromHtml($html),
            $filename
        );
    }

    /**
     * @Route("/{id}/{courseid}/edit", name="doc_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Permissions $permissions, Doc $doc, string $courseid): Response
    {
        $allowed = ['Instructor', 'Student'];
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
        $course = $this->getDoctrine()->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $options = ['courseid' => $courseid, 'choices' => $choices, 'stages' => $stages];
        $form = $this->createForm(DocType::class, $doc, ['attr' => ['id' => 'doc-form'], 'options' => $options]);
        $form->handleRequest($request);
        $markupsets = $doc->getProject()->getMarkupsets();
        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
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
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($doc);
        $entityManager->flush();

        $return = "success";
        return new Response($return, 200, array('Content-Type' => 'application/json'));
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
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($doc);
        $entityManager->flush();

        return $this->redirectToRoute('doc_show', ['id' => $doc->getId(), 'courseid' => $courseid, 'target' => $doc->getId()]);
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
