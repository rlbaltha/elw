<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Classlist;
use App\Entity\Course;
use App\Entity\LtiAgs;
use App\Entity\User;
use App\Form\LtiAgsLineitemType;
use App\Form\LtiAgsScoreType;
Use App\Form\LtiAgsResultsType;
use App\Repository\CourseRepository;
use App\Security\LtiAuthenticator;
use App\Service\Permissions;
use Carbon\Carbon;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Message\Payload\MessagePayloadInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OAT\Bundle\Lti1p3Bundle\Security\Authentication\Token\Message\LtiToolMessageSecurityToken;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use OAT\Library\Lti1p3Core\Service\Client\ServiceClientInterface;
use GuzzleHttp\ClientInterface;
use Throwable;
use RuntimeException;
use DateTime;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class LtiController extends AbstractController
{
    /** @var Security */
    private $security;

    /** @var RegistrationRepositoryInterface */
    private $repository;

    /** @var ClientInterface */
    private $guzzle;

    /** @var Builder */
    private $builder;

    /** @var Signer */
    private $signer;

    /** @var SessionInterface */
    private $session;


    public function __construct(
        Security $security,
        RegistrationRepositoryInterface $repository,
        ClientInterface $guzzle,
        Builder $builder,
        Signer $signer,
        SessionInterface $session

    )
    {
        $this->security = $security;
        $this->repository = $repository;
        $this->guzzle = $guzzle;
        $this->builder = $builder;
        $this->signer = $signer;
        $this->session = $session;
    }


    /**
     * @Route("/lti_launch", name="lti_launch")
     */
    public function lti_launch(CourseRepository $courseRepository, GuardAuthenticatorHandler $guardAuthenticatorHandler, LtiAuthenticator $ltiAuthenticator, Request $request)
    {
        /** @var LtiToolMessageSecurityToken $token */
        $token = $this->security->getToken();
        if (!$token instanceof LtiToolMessageSecurityToken) {
            return $this->redirectToRoute('course_index');
        }

        // Related registration
        $registration = $token->getRegistration();
        // You can even access validation results
        $validationResults = $token->getValidationResult();


        // Related LTI message
        //all the payload from ELC; payload depend on how Deployment is created on platform;
        // be sure to include all user and course info in Security Settings
        $ltiMessage = $token->getPayload();


        $this->session->set('lti_registration', $registration->getIdentifier());
        $this->session->set('deployment_id', $ltiMessage->getDeploymentId());


        $userIdentity = $ltiMessage->getUserIdentity();
        $firstname = $userIdentity->getGivenName();
        $lastname = $userIdentity->getFamilyName();
        $d2l_id = $userIdentity->getIdentifier();

        $roles = $ltiMessage->getClaim("https://purl.imsglobal.org/spec/lti/claim/roles");

        $username_claim = $ltiMessage->getClaim("http://www.brightspace.com");
        $username_key = 'username';
        $user_id_key = 'user_id';
        $username = $username_claim[$username_key];
        $lti_id = $username_claim[$user_id_key];

        $user = $this->getDoctrine()->getManager()->getRepository(User::class)->findOneBy(['username' => $username]);
        if (!$user) {
            $user = new User();
            $user->setUsername($username);
            //Set User Role
            //Check if Instructor
            if (in_array("http://purl.imsglobal.org/vocab/lis/v2/institution/person#Instructor", $roles)) {
                $user->setRoles(["ROLE_INSTRUCTOR"]);
            } else {
                $user->setRoles(["ROLE_USER"]);
            }
            $user->setLastname($lastname);
            $user->setFirstname($firstname);
            $user->setLtiId($lti_id);
            $user->setD2lId($d2l_id);
            $this->getDoctrine()->getManager()->persist($user);
            $this->getDoctrine()->getManager()->flush();
        }
        if (is_null($user->getLtiId())) {
            $user->setLtiId($lti_id);
            $this->getDoctrine()->getManager()->persist($user);
            $this->getDoctrine()->getManager()->flush();
        }
        if (is_null($user->getD2lId())) {
            $user->setD2lId($d2l_id);
            $this->getDoctrine()->getManager()->persist($user);
            $this->getDoctrine()->getManager()->flush();
        }

        // Actual passing of auth to Symfony firewall and sessioning
        $guardAuthenticatorHandler->authenticateUserAndHandleSuccess($user, $request, $ltiAuthenticator, 'main');

        $context = $ltiMessage->getClaim("https://purl.imsglobal.org/spec/lti/claim/context");
        $context_key_id = 'id';
        $context_key_name = 'title';
        $lti_id = $context[$context_key_id];
        $course_name = $context[$context_key_name];
        $course = $courseRepository->findOneByLtiId($lti_id);

        //Check for Course
        if (!$course) {
            //Check if Instructor
            if (in_array("http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor", $roles)) {
                $labelsets = $this->getDoctrine()->getManager()->getRepository('App:Labelset')->findDefault();
                $markupsets = $this->getDoctrine()->getManager()->getRepository('App:Markupset')->findDefault();
                $course = new Course();
                $course->setName($course_name);
                $course->setLtiId($lti_id);
                foreach ($labelsets as $labelset) {
                    $course->addLabelset($labelset);
                }
                foreach ($markupsets as $markupset) {
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
            } else {
                throw $this->createAccessDeniedException("This course is not yet available in ELW");
            }

        }
        else {
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

            return $this->redirectToRoute('course_show', ['courseid' => $courseid]);
        }
    }



    /**
     * @Route("/lti/{courseid}/nrps", name="lti_nrps", methods={"GET","POST"})
     */
    public function nrps(Permissions $permissions, String $courseid)
    {
        $course = $this->getDoctrine()->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $classlists = $this->getDoctrine()->getManager()->getRepository('App:Classlist')->findByCourseid($courseid);
        $role = $permissions->getCourseRole($courseid);

        $registration = $this->session->get('lti_registration');
        $deployment_id = $this->session->get('deployment_id');
        $method = 'get';
        $scope = 'https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly';
        $accept_header = 'application/vnd.ims.lti-nrps.v2.membershipcontainer+json';

        $registration = $this->repository->find($registration);
        $uri = $registration->getPlatform()->getAudience().'/d2l/api/lti/nrps/2.0/deployment/'.$deployment_id.'/orgunit/'.$course->getLtiId().'/memberships';
        $access_token = $this->getAccessToken($registration, $scope);
        $options = $this->getHeaderOptions($access_token, $accept_header);
        $response = $this->guzzle->request($method, $uri, $options);
        $data = json_decode($response->getBody()->__toString(), true);

        return $this->render('lti/nrps.html.twig', [
            'membership' => $data,
            'classlists' => $classlists,
            'course' => $course,
            'role' => $role,
        ]);
    }


    /**
     * @Route("/lti/{courseid}/ags_index", name="ags_index", methods={"GET"})
     */
    public function ags(Permissions $permissions, String $courseid)
    {
        $course = $this->getDoctrine()->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $classlists = $this->getDoctrine()->getManager()->getRepository('App:Classlist')->findByCourseid($courseid);
        $role = $permissions->getCourseRole($courseid);

        $registration = $this->session->get('lti_registration');
        $deployment_id = $this->session->get('deployment_id');

        $method = 'get';
        $scope = 'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem';
        $accept_header = 'application/vnd.ims.lis.v2.lineitemcontainer+json';

        $registration = $this->repository->find($registration);
        $uri = $registration->getPlatform()->getAudience().'/d2l/api/lti/ags/2.0/deployment/'.$deployment_id.'/orgunit/'.$course->getLtiId().'/lineitems';
        $access_token = $this->getAccessToken($registration, $scope);
        $options = $this->getHeaderOptions($access_token, $accept_header);
        $response = $this->guzzle->request($method, $uri, $options);
        $data = json_decode($response->getBody()->__toString(), true);

        return $this->render('lti/ags_index.html.twig', [
            'lineitems' => $data,
            'classlists' => $classlists,
            'course' => $course,
            'role' => $role,
        ]);
    }

//    /**
//     * @Route("/lti/{courseid}/ags_show", name="ags_show", methods={"GET"})
//     */
//    public function ags_show(Permissions $permissions, String $courseid)
//    {
//        $course = $this->getDoctrine()->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
//        $classlists = $this->getDoctrine()->getManager()->getRepository('App:Classlist')->findByCourseid($courseid);
//        $role = $permissions->getCourseRole($courseid);
//
//        $registration_name = $this->getParameter('lti_registration');
//        $deployment_id = $this->getParameter('lti_deployment_id');
//        $method = 'get';
//        $scope = 'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem';
//        $accept_header = 'application/vnd.ims.lis.v2.lineitemcontainer+json';
//
//        $registration = $this->repository->find($registration_name);
//        $uri = 'https://ugatest2.view.usg.edu/d2l/api/lti/ags/2.0/deployment/ce0f6d44-e598-4400-a2bd-ce6884eb416d/orgunit/2000652/lineitems/7566cb31-ce09-4437-b0a0-955cacefbef4';
//        $access_token = $this->getAccessToken($registration, $scope);
//        $options = $this->getHeaderOptions($access_token, $accept_header);
//        $response = $this->guzzle->request($method, $uri, $options);
//        $data = json_decode($response->getBody()->__toString(), true);
//        dd($data);
//
//        return $this->render('lti/ags_index.html.twig', [
//            'lineitems' => $data,
//            'classlists' => $classlists,
//            'course' => $course,
//            'role' => $role,
//        ]);
//    }


    /**
     * @Route("/lti/{courseid}/{agsid}/ags_delete", name="ags_delete", methods={"GET"})
     */
    public function ags_delete(Permissions $permissions, String $courseid, String $agsid)
    {
        $course = $this->getDoctrine()->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $classlists = $this->getDoctrine()->getManager()->getRepository('App:Classlist')->findByCourseid($courseid);
        $role = $permissions->getCourseRole($courseid);

        $local_ags = $this->getDoctrine()->getManager()->getRepository('App:LtiAgs')->findOneByAgsid($agsid);
        $registration = $this->session->get('lti_registration');
        $method = 'DELETE';
        $scope = 'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem';
        $accept_header = 'application/vnd.ims.lis.v2.lineitemcontainer+json';

        $registration = $this->repository->find($registration);
        $uri = $local_ags->getLtiId();
        $access_token = $this->getAccessToken($registration, $scope);
        $options = $this->getHeaderOptions($access_token, $accept_header);
        $response = $this->guzzle->request($method, $uri, $options);
        $data = json_decode($response->getBody()->__toString(), true);
//        dd($data);
        //delete local as well
        $ltiAg = $this->getDoctrine()->getManager()->getRepository('App:LtiAgs')->findOneByAgsid($agsid);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($ltiAg);
        $entityManager->flush();
        return $this->redirectToRoute('lti_ags_index', ['courseid' => $courseid]);
    }


    /**
     * @Route("/lti/{courseid}/ags_new", name="ags_new", methods={"GET","POST"})
     */
    public function ags_new(Request $request, Permissions $permissions, string $courseid)
    {
        $form = $this->createForm(LtiAgsLineitemType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $course = $this->getDoctrine()->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
            $role = $permissions->getCourseRole($courseid);

            $registration = $this->session->get('lti_registration');
            $deployment_id = $this->session->get('deployment_id');

            $method = 'POST';
            $scope = 'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem';
            $accept_header = 'application/vnd.ims.lis.v2.lineitem+json';
            $data = $form->getData();

            $registration = $this->repository->find($registration);
            $uri = $registration->getPlatform()->getAudience().'/d2l/api/lti/ags/2.0/deployment/'.$deployment_id.'/orgunit/'.$course->getLtiId().'/lineitems';
            $access_token = $this->getAccessToken($registration, $scope);
            $options = [
                'headers' => ['Authorization' => sprintf('Bearer %s', $access_token), 'Accept' => $accept_header],
                'json' => [
                    "scoreMaximum" => $data['scoreMaximum'],
                    "label" => $data['label'],
                    ""
                ]
            ];
            $response = $this->guzzle->request($method, $uri, $options);
            $data = json_decode($response->getBody()->__toString(), true);

            //write the new lineitem locally
            $ags = new LtiAgs();
            $ags->setLabel($data['label']);
            $ags->setLtiId($data['id']);
            $ags->setMax(intval($data['scoreMaximum']));
            $ags->setCourse($course);
            $this->getDoctrine()->getManager()->persist($ags);
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('lti_ags_index', ['courseid' => $courseid]);

        }

        return $this->render('lti/new_ags_lineitem.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/lti/{courseid}/ags_score", name="ags_score_new", methods={"GET","POST"})
     */
    public function ags_score_new(Request $request, Permissions $permissions, string $courseid)
    {
        $course = $this->getDoctrine()->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $form = $this->createForm(LtiAgsScoreType::class, null, ['course' => $course]);
        $role = $permissions->getCourseRole($courseid);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $registration = $this->session->get('lti_registration');
            $method = 'POST';
            $scope = 'https://purl.imsglobal.org/spec/lti-ags/scope/score';
            $accept_header = 'application/vnd.ims.lis.v1.score+json';
            $data = $form->getData();
            $agsid = $data['uri'];
            $local_ags = $this->getDoctrine()->getManager()->getRepository('App:LtiAgs')->findOneByAgsid($agsid);
            $uri = $local_ags->getLtiId().'/scores';
            $userid = $data['userId'];
            $user = $this->getDoctrine()->getManager()->getRepository('App:User')->find($userid);
            $userD2lId = $user->getD2lId();
            $timestamp = date(\DateTime::ISO8601);
            $registration = $this->repository->find($registration);
            $access_token = $this->getAccessToken($registration, $scope);
            $options = [
                'headers' => ['Authorization' => sprintf('Bearer %s', $access_token), 'Accept' => $accept_header],
                'json' => [
                    "userId" => $userD2lId,
                    "scoreGiven" => $data['scoreGiven'],
                    "scoreMaximum" => $data['scoreMaximum'],
                    "comment" => $data['comment'],
                    "timestamp" => $timestamp,
                    "activityProgress"=> 'Completed',
                    "gradingProgress"=> 'FullyGraded'
                ]
            ];
            $response = $this->guzzle->request($method, $uri, $options);
            $data = json_decode($response->getBody()->__toString(), true);
            dd($data);
        }

        return $this->render('lti/new_ags_score.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    /**
     * @Route("/lti/{courseid}/ags_results", name="ags_results", methods={"GET","POST"})
     */
    public function ags_results(Request $request, Permissions $permissions, String $courseid)
    {
        $course = $this->getDoctrine()->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $form = $this->createForm(LtiAgsResultsType::class, null, ['course' => $course]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $registration = $this->session->get('lti_registration');
            $method = 'GET';
            $scope = 'https://purl.imsglobal.org/spec/lti-ags/scope/result.readonly';
            $accept_header = 'application/vnd.ims.lis.v2.resultcontainer+json';
            $data = $form->getData();
            $agsid = $data['uri'];
            $local_ags = $this->getDoctrine()->getManager()->getRepository('App:LtiAgs')->findOneByAgsid($agsid);
            $uri = $local_ags->getLtiId().'/results';

            $registration = $this->repository->find($registration);
            $access_token = $this->getAccessToken($registration, $scope);
            $options = $this->getHeaderOptions($access_token, $accept_header);
            $response = $this->guzzle->request($method, $uri, $options);
            $data = json_decode($response->getBody()->__toString(), true);
            dd($data);
        }

        return $this->render('lti/new_ags_results.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    private function getHeaderOptions($access_token, $accept_header) {
        $options = [
            'headers' => ['Authorization' => sprintf('Bearer %s', $access_token), 'Accept' => $accept_header]
        ];
        return $options;
    }

    /**
     * @throws LtiExceptionInterface
     */
    private function getAccessToken(RegistrationInterface $registration, $scope): string
    {
            $response = $this->guzzle->request('POST', $registration->getPlatform()->getOAuth2AccessTokenUrl(), [
                'form_params' => [
                    'grant_type' => 'client_credentials',
                    'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
                    'client_assertion' => $this->generateCredentials($registration),
                    'scope' => $scope
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new RuntimeException('invalid response http status code');
            }

            $responseData = json_decode($response->getBody()->__toString(), true);

            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new RuntimeException(sprintf('json_decode error: %s', json_last_error_msg()));
            }

            $accessToken = $responseData['access_token'] ?? '';
            $expiresIn = $responseData['expires_in'] ?? '';

            if (empty($accessToken) || empty($expiresIn)) {
                throw new RuntimeException('invalid response body');
            }

            return $accessToken;
    }

    /**
     * @throws LtiExceptionInterface
     */
    public function generateCredentials(RegistrationInterface $registration): string
    {
        try {
            if (null === $registration->getToolKeyChain()) {
                throw new LtiException('Tool key chain is not configured');
            }

            $now = Carbon::now();
            return $this->builder
                ->withHeader(MessagePayloadInterface::HEADER_KID, $registration->getToolKeyChain()->getIdentifier())
                ->identifiedBy(sprintf('%s-%s', $registration->getIdentifier(), $now->getTimestamp()))
                ->issuedBy($registration->getClientId())
                ->relatedTo($registration->getClientId())
                ->permittedFor($registration->getTool()->getAudience())
                ->issuedAt($now->getTimestamp())
                ->expiresAt($now->addSeconds(MessagePayloadInterface::TTL)->getTimestamp())
                ->getToken($this->signer, $registration->getToolKeyChain()->getPrivateKey())
                ->__toString();

        } catch (Throwable $exception) {
            throw new LtiException(
                sprintf('Cannot generate credentials: %s', $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }

    }
}
