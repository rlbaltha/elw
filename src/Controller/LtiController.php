<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OAT\Bundle\Lti1p3Bundle\Security\Authentication\Token\Message\LtiMessageToken;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;

class LtiController extends AbstractController
{
    /** @var Security */
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * @Route("/lti_login", name="lti")
     */
    public function index(Request $request)
    {
        /** @var LtiMessageToken $token */
        $token = $this->security->getToken();

        // Related registration
        $registration = $token->getRegistration();

        // Related LTI message
        $ltiMessage = $token->getLtiMessage();

        // You can even access validation results
        $validationResults = $token->getValidationResult();


        $lti_message = $request->get('ltiMessage');
        return $this->render('lti/index.html.twig', [
            'lti_message' => $lti_message,
            'registration' => $registration,
            'validationResults' => $validationResults,
            'controller_name' => 'LtiController',
        ]);
    }
}
