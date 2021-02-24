<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;


class DefaultController extends AbstractController
{
    /**
     * @Route("/home", name="home")
     */
    public function home()
    {
        $login_access = $_ENV['LOGIN_ACCESS'];
        $login_note = $_ENV['LOGIN_NOTE'];
        return $this->render('default/index.html.twig', [
            'controller_name' => 'DefaultController',
            'login_access' => $login_access,
            'login_note' => $login_note,

        ]);
    }
}