<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class LtiController extends AbstractController
{
    /**
     * @Route("/lti_login", name="lti")
     */
    public function index(Request $request)
    {
        $lti_message = $request->get('ltiMessage');
        return $this->render('lti/index.html.twig', [
            'lti_message' => $lti_message,
            'controller_name' => 'LtiController',
        ]);
    }
}
