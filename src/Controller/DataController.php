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
        return $this->render('data/index.html.twig', [
            'course_count' => $course_count,
            'classlist_count' => $classlist_count,
        ]);
    }
}
