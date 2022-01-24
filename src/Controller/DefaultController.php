<?php

namespace App\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;


class DefaultController extends AbstractController
{
    /** @var ManagerRegistry */
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

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

    /**
     * @Route("/keep-alive", name="keep_alive", methods={"POST"})
     */
    public function keepalive(SerializerInterface $serializer): Response
    {
        $return = "success";
        return new JsonResponse($return);
    }
}