<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class LtiController extends AbstractController
{
    /**
     * @Route("/lti_login", name="lti")
     */
    public function index()
    {
        return $this->render('lti/index.html.twig', [
            'controller_name' => 'LtiController',
        ]);
    }
}
