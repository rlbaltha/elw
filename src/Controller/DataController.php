<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DataController extends AbstractController
{
    /**
     * @Route("/data", name="data")
     */
    public function index(): Response
    {
        $course_count = $this->getDoctrine()->getManager()->getRepository('App:Course')->countByTerm();
        $classlist_count = $this->getDoctrine()->getManager()->getRepository('App:Classlist')->countByTerm();
        $doc_count = $this->getDoctrine()->getManager()->getRepository('App:Doc')->countDocsByTerm();
        $journal_count = $this->getDoctrine()->getManager()->getRepository('App:Doc')->countJournalByTerm();
        return $this->render('data/index.html.twig', [
            'course_count' => $course_count,
            'classlist_count' => $classlist_count,
            'doc_count' => $doc_count,
            'journal_count' => $journal_count,
        ]);
    }
}
