<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Classlist;
use App\Entity\Course;
use App\Entity\LtiAgs;
use App\Entity\User;
use App\Form\LtiAgsLineitemType;
use App\Form\LtiAgsScoreType;
use App\Repository\CourseRepository;
use App\Security\LtiAuthenticator;
use App\Service\Lti;
use App\Service\Permissions;
use GuzzleHttp\Exception\ClientException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceRepositoryInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\Persistence\ManagerRegistry;
//for v6 use OAT\Library\Lti1p3Core\Message\Launch\Validator\Tool\ToolLaunchValidator;
use OAT\Library\Lti1p3Core\Message\Launch\Validator\ToolLaunchValidator;
use Psr\Http\Message\ServerRequestInterface;

class LtiController extends AbstractController
{
    /** @var ManagerRegistry */
    private ManagerRegistry $doctrine;

    /** @var UserPasswordHasherInterface */
    private $passwordHasher;

    /** @var RegistrationRepositoryInterface */
    private $repository;


    public function __construct(
        RegistrationRepositoryInterface $repository,
        ClientInterface $guzzle,
        ManagerRegistry $doctrine,
        UserPasswordHasherInterface $passwordHasher
    )
    {
        $this->repository = $repository;
        $this->guzzle = $guzzle;
        $this->doctrine = $doctrine;
        $this->passwordHasher = $passwordHasher;
    }


    /**
     * @Route("/lti_launch", name="lti_launch")
     */
    public function lti_launch(CourseRepository $courseRepository, ServerRequestInterface $serverRequest,
                               RegistrationRepositoryInterface $repository, NonceRepositoryInterface $nonceRepository,
                               UserAuthenticatorInterface $userAuthenticator, LtiAuthenticator $ltiAuthenticator,
                               Session $session, Request $request)
    {
        // Create the lti token validator
        $validator = new ToolLaunchValidator($repository, $nonceRepository);

        // Perform validation
        $launch = $validator->validatePlatformOriginatingLaunch($serverRequest);
        $ltiMessage = $launch->getPayload();

        //get registrations
        $registration = $launch->getRegistration();
        $access_registration = $registration->getIdentifier() . '_access';

        //set registration in session for lti variables in controller
        $session->set('lti_registration', $registration->getIdentifier());
        //set registration in session for ltiServiceClient
        $session->set('lti_registration_access', $access_registration);
        $session->set('deployment_id', $ltiMessage->getDeploymentId());

        // Get user
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

        $user = $this->doctrine->getManager()->getRepository(User::class)->findOneBy(['username' => $username]);
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
            $password = random_bytes(18);
            $user->setPassword($this->passwordHasher->hashPassword(
                $user,
                $password
            ));
            $this->doctrine->getManager()->persist($user);
            $this->doctrine->getManager()->flush();
        }
        if (is_null($user->getLtiId())) {
            $user->setLtiId($lti_id);
            $this->doctrine->getManager()->persist($user);
            $this->doctrine->getManager()->flush();
        }
        if (is_null($user->getD2lId())) {
            $user->setD2lId($d2l_id);
            $this->doctrine->getManager()->persist($user);
            $this->doctrine->getManager()->flush();
        }

        // Actual passing of auth to Symfony firewall and sessioning
        $request->attributes->set('username', $username);
        $ltiAuthenticator->authenticate($request);
        $userAuthenticator->authenticateUser($user, $ltiAuthenticator, $request);

        $now = new \DateTime('now');
        $user->setLastlogin($now);
        $this->doctrine->getManager()->persist($user);
        $this->doctrine->getManager()->flush();

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
                $course = new Course();
                //Untested Default Term
                $default_term = $this->doctrine->getManager()->getRepository('App:Term')->findOneBy(['status'=>'Default']);
                $course->setTerm($default_term);
                //Untested Default Term
                $course->setName($course_name);
                $course->setLtiId($lti_id);
                $classlist = new Classlist();
                $classlist->setUser($user);
                $classlist->setCourse($course);
                $classlist->setRole('Instructor');
                $classlist->setStatus('Approved');
                $this->doctrine->getManager()->persist($classlist);
                $this->doctrine->getManager()->persist($course);
                $this->doctrine->getManager()->flush();
                return $this->redirectToRoute('course_edit', ['courseid' => $course->getId()]);
            } else {
                throw $this->createAccessDeniedException("This course is not yet available in ELW");
            }

        } else {
            //Check if on Roll (Classlist)
            $classuser = $this->doctrine->getManager()->getRepository('App:Classlist')->findCourseUser($course, $user);
            if (!$classuser) {
                $classlist = new Classlist();
                $classlist->setUser($user);
                $classlist->setCourse($course);
                $classlist->setRole('Student');
                $classlist->setStatus('Approved');
                $entityManager = $this->doctrine->getManager();
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
    public function nrps(Permissions $permissions, string $courseid, Lti $lti, Session $session)
    {
        $course = $this->doctrine->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);

        $allowed = ['Instructor'];
        $permissions->restrictAccessTo($courseid, $allowed);

        $registration = $session->get('lti_registration');
        $deployment_id = $session->get('deployment_id');
        $method = 'get';
        $scopes = ['https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly'];
        $accept_header = 'application/vnd.ims.lti-nrps.v2.membershipcontainer+json';
        $registration = $this->repository->find($registration);
        $uri = $registration->getPlatform()->getAudience() . '/d2l/api/lti/nrps/2.0/deployment/' . $deployment_id . '/orgunit/' . $course->getLtiId() . '/memberships';
        $options = [
            'headers' => ['Accept' => $accept_header]
        ];
        $response = $lti->request($method, $uri, $options, $scopes);
        $data = json_decode($response->getBody()->__toString(), true);

        return $this->render('lti/nrps_ajax.html.twig', [
            'membership' => $data,
        ]);
    }


    /**
     * @Route("/lti/{courseid}/ags_new", name="ags_new", methods={"GET","POST"})
     */
    public function ags_new(Request $request, Permissions $permissions, string $courseid, Lti $lti, Session $session)
    {
        $form = $this->createForm(LtiAgsLineitemType::class);
        $course = $this->doctrine->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $role = $permissions->getCourseRole($courseid);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $registration = $session->get('lti_registration');
            $deployment_id = $session->get('deployment_id');

            $method = 'POST';
            $scopes = ['https://purl.imsglobal.org/spec/lti-ags/scope/lineitem'];
            $accept_header = 'application/vnd.ims.lis.v2.lineitem+json';
            $data = $form->getData();

            $registration = $this->repository->find($registration);
            $uri = $registration->getPlatform()->getAudience() . '/d2l/api/lti/ags/2.0/deployment/' . $deployment_id . '/orgunit/' . $course->getLtiId() . '/lineitems';
            $options = [
                'headers' => ['Accept' => $accept_header],
                'json' => [
                    "scoreMaximum" => $data['scoreMaximum'],
                    "label" => $data['label'],
                    ""
                ]
            ];

            $response = $lti->request($method, $uri, $options, $scopes);
            $data = json_decode($response->getBody()->__toString(), true);

            //write the new lineitem locally
            $ags = new LtiAgs();
            $ags->setLabel($data['label']);
            $ags->setLtiId($data['id']);
            $ags->setMax(intval($data['scoreMaximum']));
            $ags->setCourse($course);
            $this->doctrine->getManager()->persist($ags);
            $this->doctrine->getManager()->flush();
            $this->addFlash('notice', 'The grade column was added.');
            return $this->redirectToRoute('course_show', ['courseid' => $courseid]);

        }

        return $this->render('lti/new_ags_lineitem.html.twig', [
            'form' => $form->createView(),
            'course' => $course,
            'role' => $role
        ]);
    }

    /**
     * @Route("/lti/{courseid}/{docid}/{source}/ags_score", name="ags_score_new", methods={"GET","POST"})
     */
    public function ags_score_new(Request $request, Permissions $permissions, Lti $lti, Session $session, string $courseid, string $docid, string $source)
    {
        $allowed = ['Instructor'];
        $permissions->restrictAccessTo($courseid, $allowed);
        $header = 'Grade Submit';
        $course = $this->doctrine->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $doc = $this->doctrine->getManager()->getRepository('App:Doc')->findOneById($docid);

        if ($doc->getProject() != null) {
            $uris = $doc->getProject()->getLtiGrades();
        } else {
            $uris = $course->getLtiAgs();
        }

        $comment = '';
        $score = null;
        $column = '';

        if ($doc->getAgsResultId() != null) {
            $ltiid = strstr($doc->getAgsResultId(), "/results", true);
            $column = $this->doctrine->getManager()->getRepository('App:LtiAgs')->findOneByLtiid($ltiid);
            $uri = $doc->getAgsResultId();
            $results = $lti->getLtiResult($uri);
            if (is_array($results) ) {
                $comment = strip_tags(html_entity_decode($results[0]['comment'], ENT_QUOTES | ENT_XML1, 'UTF-8'));
                $score = $results[0]['resultScore'];
            }
        }
        $form = $this->createForm(LtiAgsScoreType::class, null, ['comment' => $comment, 'score' => $score, 'column' => $column, 'uris' => $uris]);
        $role = $permissions->getCourseRole($courseid);

        if ($doc->getOrigin() != null) {
            $d2l_user = $doc->getOrigin()->getUser()->getD2lId();
        } else {
            $d2l_user = $doc->getUser()->getD2lId();
        }
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $method = 'POST';
            $scopes = ['https://purl.imsglobal.org/spec/lti-ags/scope/score'];
            $accept_header = 'application/vnd.ims.lis.v1.score+json';
            $data = $form->getData();
            $agsid = $data['uri'];
            $local_ags = $this->doctrine->getManager()->getRepository('App:LtiAgs')->findOneByAgsid($agsid);
            $uri = $local_ags->getLtiId() . '/scores';
            $scoreMaximum = $local_ags->getMax();
            $timestamp = date(\DateTime::ISO8601);
            if (is_null($data['comment'])) {
                $datacomment = '';
            }
            else {
                $datacomment = $data['comment'];
            }
            $options = [
                'headers' => ['Accept' => $accept_header],
                'json' => [
                    "userId" => $d2l_user,
                    "scoreGiven" => $data['scoreGiven'],
                    "scoreMaximum" => $scoreMaximum,
                    "comment" => strip_tags(html_entity_decode($datacomment, ENT_QUOTES | ENT_XML1, 'UTF-8')),
                    "timestamp" => $timestamp,
                    "activityProgress" => 'Completed',
                    "gradingProgress" => 'FullyGraded'
                ]
            ];
            $agsResultId = $local_ags->getLtiId() . '/results?user_id=' . $d2l_user;
            $doc->setAgsResultId($agsResultId);
            $this->doctrine->getManager()->persist($doc);
            $this->doctrine->getManager()->flush();
            try {
                $response = $lti->request($method, $uri, $options, $scopes);
                $this->addFlash('notice', 'The grade was submitted.');
            } catch (ClientException $e) {
                $this->addFlash('error', 'The grade column selected no longer exists in eLC.');
            }

            if ($source != 'doc') {
                return $this->redirectToRoute('journal_index', ['docid' => $doc->getId(), 'userid' => $doc->getUser()->getId(), 'courseid' => $courseid]);
            }
            return $this->redirectToRoute('doc_show', ['id' => $doc->getId(), 'courseid' => $courseid, 'target' => $doc->getId()]);
        }

        return $this->render('comment/new.html.twig', [
            'doc' => $doc,
            'course' => $course,
            'role' => $role,
            'header' => $header,
            'form' => $form->createView(),
        ]);
    }

}