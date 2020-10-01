<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use OAT\Bundle\Lti1p3Bundle\Security\Authentication\Token\Message\LtiMessageToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class DefaultController extends AbstractController
{
    /** @var Security */
    private $security;
    private $session;

    public function __construct(Security $security, SessionInterface $session)
    {
        $this->security = $security;
        $this->session = $session;
    }

    /**
     * @Route("/home", name="default")
     */
    public function index()
    {
        return $this->render('default/index.html.twig', [
            'controller_name' => 'DefaultController',
        ]);
    }

    /**
     * @Route("/lti_test", name="lti_test")
     */
    public function testing()
    {
        $this->session->set('user', 'rlbaltha');
//        /** @var LtiMessageToken $token */
//        $token = $this->security->getToken();
//
//        // Related registration
//        $registration = $token->getRegistration();

        // Related LTI message
        $ltiMessage = "It worked";

//        // You can even access validation results
//        $validationResults = $token->getValidationResult();

//        return $this->render('default/index.html.twig', [
//            'lti_message' => $ltiMessage,
//        ]);
        return $this->redirectToRoute('course_index');
    }


}