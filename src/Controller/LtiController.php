<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Classlist;
use App\Entity\Course;
use App\Entity\User;
use App\Repository\CourseRepository;
use App\Security\LtiAuthenticator;
use Carbon\Carbon;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Message\Payload\MessagePayloadInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Service\Server\Grant\ClientAssertionCredentialsGrant;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OAT\Bundle\Lti1p3Bundle\Security\Authentication\Token\Message\LtiToolMessageSecurityToken;
use OAT\Library\Lti1p3Nrps\Service\Client\MembershipServiceClient;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use Psr\Log\LoggerInterface;
use OAT\Library\Lti1p3Core\Service\Client\ServiceClientInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use OAT\Library\Lti1p3Core\Service\Client\ServiceClient;

class LtiController extends AbstractController
{
    /** @var Security */
    private $security;

    /** @var MembershipServiceClient */
    private $client;

    /** @var ServiceClientInterface */
    private $service_client;

    /** @var RegistrationRepositoryInterface */
    private $repository;

    /** @var ClientInterface */
    private $guzzle;

    /** @var Builder */
    private $builder;

    /** @var Signer */
    private $signer;

    public function __construct(
        Security $security,
        MembershipServiceClient $client,
        RegistrationRepositoryInterface $repository,
        ClientInterface $guzzle,
        ServiceClientInterface $service_client,
        Builder $builder,
        Signer $signer
)
    {
        $this->security = $security;
        $this->client = $client;
        $this->repository = $repository;
        $this->guzzle = $guzzle;
        $this->service_client = $service_client;
        $this->builder = $builder;
        $this->signer = $signer;
    }


    /**
     * @Route("/lti_test", name="lti_test")
     */
    public function lti_launch(CourseRepository $courseRepository, GuardAuthenticatorHandler $guardAuthenticatorHandler, LtiAuthenticator $ltiAuthenticator, Request $request)
    {
        /** @var LtiToolMessageSecurityToken $token */
        $token = $this->security->getToken();
        if (!$token instanceof LtiToolMessageSecurityToken) {
            throw $this->createAccessDeniedException("This page is not available.");
        }

        // Related registration
        $registration = $token->getRegistration();
        // You can even access validation results
        $validationResults = $token->getValidationResult();

        // Related LTI message
        //all the payload from ELC; payload depend on how Deployment is created on platform;
        // be sure to include all user and course info in Security Settings
        $ltiMessage = $token->getPayload();

        $userIdentity = $ltiMessage->getUserIdentity();
        $firstname = $userIdentity->getGivenName();
        $lastname = $userIdentity->getFamilyName();
        $roles = $ltiMessage->getClaim("https://purl.imsglobal.org/spec/lti/claim/roles");

        $username_claim = $ltiMessage->getClaim("http://www.brightspace.com");
        $username_key='username';
        $username = $username_claim[$username_key];

        $user = $this->getDoctrine()->getManager()->getRepository(User::class)->findOneBy(['username' => $username]);
        if (!$user) {
            $user = New User();
            $user->setUsername($username);
            //Set User Role
            //Check if Instructor
            if (in_array("http://purl.imsglobal.org/vocab/lis/v2/institution/person#Instructor", $roles)) {
                $user->setRoles(["ROLE_INSTRUCTOR"]);
            }
            else {
                $user->setRoles(["ROLE_USER"]);
            }
            $user->setLastname($lastname);
            $user->setFirstname($firstname);
            $this->getDoctrine()->getManager()->persist($user);
            $this->getDoctrine()->getManager()->flush();
        }


        $context = $ltiMessage->getClaim("https://purl.imsglobal.org/spec/lti/claim/context");
        $context_key_id = 'id';
        $context_key_name = 'title';
        $lti_id = $context[$context_key_id];
        $course_name = $context[$context_key_name];
        $course =  $courseRepository->findOneByLtiId($lti_id);

        //Check for Course
        if (!$course) {
            //Check if Instructor
            if (in_array("http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor", $roles)) {
                $labelsets = $this->getDoctrine()->getManager()->getRepository('App:Labelset')->findDefault();
                $markupsets = $this->getDoctrine()->getManager()->getRepository('App:Markupset')->findDefault();
                $course = new Course();
                $course->setName($course_name);
                $course->setLtiId($lti_id);
                foreach($labelsets as $labelset){
                    $course->addLabelset($labelset);
                }
                foreach($markupsets as $markupset){
                    $course->addMarkupset($markupset);
                }
                $classlist = new Classlist();
                $classlist->setUser($user);
                $classlist->setCourse($course);
                $classlist->setRole('Instructor');
                $classlist->setStatus('Approved');
                $this->getDoctrine()->getManager()->persist($classlist);
                $this->getDoctrine()->getManager()->persist($course);
                $this->getDoctrine()->getManager()->flush();
                return $this->redirectToRoute('course_edit', ['courseid' => $course->getId()]);
            }
            else {
                throw $this->createAccessDeniedException("This course is not yet available in ELW");
            }

        }

        //Check if on Roll (Classlist)
        $classuser = $this->getDoctrine()->getManager()->getRepository('App:Classlist')->findCourseUser($course, $user);
        if (!$classuser) {
            $classlist = new Classlist();
            $classlist->setUser($user);
            $classlist->setCourse($course);
            $classlist->setRole('Student');
            $classlist->setStatus('Approved');
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($classlist);
            $entityManager->flush();
        }
        $courseid = $course->getId();

        // Actual passing of auth to Symfony firewall and sessioning
        $guardAuthenticatorHandler->authenticateUserAndHandleSuccess($user, $request, $ltiAuthenticator, 'main');

        if ($this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
            return $this->render('lti/ltiLaunch.html.twig', [
                'course' => $course,
                'token' => $token,
            ]);
        }
        else {
            return $this->redirectToRoute('course_show', ['courseid' => $courseid]);
        }
    }

    /**
     * @Route("/lti_nrps", name="lti_nrps", methods={"GET","POST"})
     */
    public function nrps(Request $request)
    {
        $registration = $request->get('registration');
        dd($registration);
        $membership = $this->client->getContextMembership(
            $this->repository->find(),
            $request->get('url'),
            $request->get('role'),
            intval($request->get('limit'))
        );

        return $this->render('lti/nrps.html.twig', [
            'membership' => $membership,
        ]);
    }

//    /**
//     * @Route("/lti_ags", name="lti_ags", methods={"GET","POST"})
//     */
//    public function access_token()
//    {
//            $scopes = ['https://purl.imsglobal.org/spec/lti-ags/scope/lineitem'];
//            $registration= $this->repository->find('ugatest2');
////        $now = Carbon::now();
////        $tokenBuilder = $this->builder
////            ->withHeader(MessagePayloadInterface::HEADER_KID, $registration->getToolKeyChain()->getIdentifier())
////            ->identifiedBy(sprintf('%s-%s', $registration->getIdentifier(), $now->getTimestamp()))
////            ->issuedBy($registration->getTool()->getAudience())
////            ->relatedTo($registration->getClientId())
////            ->permittedFor('https://api.brightspace.com/auth/token')
////            ->issuedAt($now->getTimestamp())
////            ->expiresAt($now->addSeconds(MessagePayloadInterface::TTL)->getTimestamp())
////            ->getToken($this->signer, $registration->getToolKeyChain()->getPrivateKey());
////         dd($tokenBuilder, $tokenBuilder->verify($this->signer, $registration->getToolKeyChain()->getPublicKey()));
//
//
//            $access_token = $this->guzzle->request('POST', $registration->getPlatform()->getOAuth2AccessTokenUrl(), [
//                'form_params' => [
//                    'grant_type' => 'client_credentials',
//                    'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
//                    'client_assertion' => $this->generateCredentials($registration),
//                    'scope' => implode(' ', $scopes)
//                ]
//            ]);
//            dd($access_token);
//
//
//    }
//
//    /**
//     * @throws LtiExceptionInterface
//     */
//    public function generateCredentials(RegistrationInterface $registration): string
//    {
//
//            $now = Carbon::now();
//
//            return $this->builder
//                ->withHeader(MessagePayloadInterface::HEADER_KID, $registration->getToolKeyChain()->getIdentifier())
//                ->identifiedBy(sprintf('%s-%s', $registration->getIdentifier(), $now->getTimestamp()))
//                ->issuedBy($registration->getTool()->getAudience())
//                ->relatedTo($registration->getClientId())
//                ->permittedFor($registration->getPlatform()->getAudience())
//                ->issuedAt($now->getTimestamp())
//                ->expiresAt($now->addSeconds(MessagePayloadInterface::TTL)->getTimestamp())
//                ->getToken($this->signer, $registration->getToolKeyChain()->getPrivateKey())
//                ->__toString();
//
//
//    }
}
