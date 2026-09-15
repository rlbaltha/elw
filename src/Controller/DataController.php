<?php

namespace App\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\CourseRepository;
use App\Repository\ClasslistRepository;
use App\Repository\DocRepository;
use App\Repository\RatingRepository;
use App\Repository\RubricRepository;
use App\Repository\TermRepository;

class DataController extends AbstractController
{
    /** @var ManagerRegistry */
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }
    
    #[Route(path: '/data', name: 'data')]
    public function index(CourseRepository $courseRepository, ClasslistRepository $classlistRepository, DocRepository $docRepository, RubricRepository $rubricRepository, TermRepository $termRepository): Response
    {
        $course_count = $courseRepository->countByTerm();
        $classlist_count = $classlistRepository->countByTerm();
        $doc_count = $docRepository->countDocsByTerm();
        $journal_count = $docRepository->countJournalByTerm();
        $term = $termRepository->findOneBy(['status'=>'Default']);
        $terms = $termRepository->findAll();
        $coursetype_count = $courseRepository->countByCoursetype($term->getId());
        $rubric_count = $rubricRepository->countRubricsByTerm($term->getId());
        return $this->render('data/index.html.twig', [
            'course_count' => $course_count,
            'coursetype_count' => $coursetype_count,
            'classlist_count' => $classlist_count,
            'doc_count' => $doc_count,
            'journal_count' => $journal_count,
            'rubric_count' => $rubric_count,
            'term' => $term,
            'terms'=> $terms
        ]);
    }


    #[Route(path: '/{termid}/{rubricid}/data', name: 'rubric_data')]
    public function rubricdata($termid, $rubricid, RatingRepository $ratingRepository, RubricRepository $rubricRepository, TermRepository $termRepository): Response
    {
        $rubric = null;
        $ratings_count = null;
        if ($rubricid !== 0)
        {
            $rubric = $rubricRepository->find($rubricid);
            $ratings_count = $ratingRepository->countRatingsByRubricByTerm($termid, $rubricid);
        }
        $term = $termRepository->find($termid);
        $terms = $termRepository->findAll();

        $rubric_count = $rubricRepository->countRubricsByTerm($term->getId());

        return $this->render('data/rubric.html.twig', [
            'rubric_count' => $rubric_count,
            'term' => $term,
            'terms'=> $terms,
            'rubric' => $rubric,
            'ratings_count' => $ratings_count,
        ]);
    }
}
