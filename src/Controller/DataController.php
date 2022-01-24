<?php

namespace App\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DataController extends AbstractController
{
    /** @var ManagerRegistry */
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }
    
    /**
     * @Route("/data", name="data")
     */
    public function index(): Response
    {
        $course_count = $this->doctrine->getManager()->getRepository('App:Course')->countByTerm();
        $classlist_count = $this->doctrine->getManager()->getRepository('App:Classlist')->countByTerm();
        $doc_count = $this->doctrine->getManager()->getRepository('App:Doc')->countDocsByTerm();
        $journal_count = $this->doctrine->getManager()->getRepository('App:Doc')->countJournalByTerm();
        $term = $this->doctrine->getManager()->getRepository('App:Term')->findOneBy(['status'=>'Default']);
        $coursetype_count = $this->doctrine->getManager()->getRepository('App:Course')->countByCoursetype($term->getId());
        $rubric_count = $this->doctrine->getManager()->getRepository('App:Rubric')->countRubricsByTerm($term->getId());
        return $this->render('data/index.html.twig', [
            'course_count' => $course_count,
            'coursetype_count' => $coursetype_count,
            'classlist_count' => $classlist_count,
            'doc_count' => $doc_count,
            'journal_count' => $journal_count,
            'rubric_count' => $rubric_count,
            'term' => $term,
        ]);
    }


    /**
     * @Route("/{termid}/{rubricid}/data", name="rubric_data")
     */
    public function rubricdata($termid, $rubricid): Response
    {;
        $term = $this->doctrine->getManager()->getRepository('App:Term')->find($termid);
        $rubric = $this->doctrine->getManager()->getRepository('App:Rubric')->find($rubricid);
        $rubric_count = $this->doctrine->getManager()->getRepository('App:Rubric')->countRubricsByTerm($term->getId());
        $ratings_count = $this->doctrine->getManager()->getRepository('App:Rating')->countRatingsByRubricByTerm($termid, $rubricid);
        return $this->render('data/rubric.html.twig', [
            'rubric_count' => $rubric_count,
            'term' => $term,
            'rubric' => $rubric,
            'ratings_count' => $ratings_count,
        ]);
    }
}
