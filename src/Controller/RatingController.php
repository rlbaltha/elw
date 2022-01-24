<?php

namespace App\Controller;

use App\Entity\Rating;
use App\Form\RatingType;
use App\Repository\RatingRepository;
use App\Service\Permissions;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/rating")
 */
class RatingController extends AbstractController
{
    /** @var ManagerRegistry */
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }
    
    /**
     * @Route("/", name="rating_index", methods={"GET"})
     */
    public function index(RatingRepository $ratingRepository): Response
    {
        return $this->render('rating/index.html.twig', [
            'ratings' => $ratingRepository->findAll(),
        ]);
    }

    /**
     * @Route("/{docid}/{rubricid}/{courseid}/new", name="rating_new", methods={"GET","POST"})
     */
    public function new(Request $request, Permissions $permissions, int $docid, int $rubricid, int $courseid): Response
    {
        $header = 'Rubric Rating';
        $course = $this->doctrine->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $role = $permissions->getCourseRole($courseid);
        $username = $this->getUser()->getUsername();
        $user = $this->doctrine->getManager()->getRepository('App:User')->findOneByUsername($username);
        $doc = $this->doctrine->getManager()->getRepository('App:Doc')->find($docid);
        $rubric = $this->doctrine->getManager()->getRepository('App:Rubric')->find($rubricid);
        $rating = new Rating();
        $rating->setUser($user);
        $rating->setDoc($doc);
        $rating->setRubric($rubric);
        $form = $this->createForm(RatingType::class, $rating);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->doctrine->getManager();
            $entityManager->persist($rating);
            $entityManager->flush();

            return $this->redirectToRoute('doc_show', ['id' => $doc->getId(), 'courseid' => $courseid, 'target' => $doc->getId()]);
        }

        return $this->render('rating/new.html.twig', [
            'rating' => $rating,
            'doc' => $doc,
            'course' => $course,
            'role' => $role,
            'header' => $header,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="rating_show", methods={"GET"})
     */
    public function show(Rating $rating): Response
    {
        return $this->render('rating/show.html.twig', [
            'rating' => $rating,
        ]);
    }

    /**
     * @Route("/{docid}/{rubricid}/{courseid}/rating_view", name="rating_view", methods={"GET"})
     */
    public function ajax_view(Permissions $permissions, int $docid, int $rubricid, int $courseid): Response
    {
        $role = $permissions->getCourseRole($courseid);
        $doc = $this->doctrine->getManager()->getRepository('App:Doc')->find($docid);
        $ratings = $this->doctrine->getManager()->getRepository('App:Rating')->findAjax($docid, $rubricid);
        return $this->render('rating/rating_ajax.html.twig', [
            'rubricid' => $rubricid,
            'ratings' => $ratings,
            'role' => $role,
            'doc' => $doc
        ]);
    }

    /**
     * @Route("/{id}/{courseid}/edit", name="rating_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Permissions $permissions, Rating $rating): Response
    {
        $header = 'Rubric Rating';
        $doc = $rating->getDoc();
        $course = $doc->getCourse();
        $role = $permissions->getCourseRole($course->getId());

        $form = $this->createForm(RatingType::class, $rating);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->getManager()->flush();

            return $this->redirectToRoute('doc_show', ['id' => $doc->getId(), 'courseid' => $course->getId(), 'target' => $doc->getId()]);
        }

        return $this->render('rating/edit.html.twig', [
            'rating' => $rating,
            'doc' => $doc,
            'course' => $course,
            'role' => $role,
            'header' => $header,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="rating_delete", methods={"POST"})
     */
    public function delete(Request $request, Rating $rating): Response
    {
        $doc = $rating->getDoc();
        $course = $doc->getCourse();

        if ($this->isCsrfTokenValid('delete'.$rating->getId(), $request->request->get('_token'))) {
            $entityManager = $this->doctrine->getManager();
            $entityManager->remove($rating);
            $entityManager->flush();
        }

        return $this->redirectToRoute('doc_show', ['id' => $doc->getId(), 'courseid' => $course->getId(), 'target' => $doc->getId()]);
    }
}
