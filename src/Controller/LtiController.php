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
use Doctrine\Persistence\ManagerRegistry;
use GuzzleHttp\Exception\ClientException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use OAT\Bundle\Lti1p3Bundle\Security\Authentication\Token\Message\LtiToolMessageSecurityToken;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use Symfony\Component\Security\Core\Security;
use GuzzleHttp\ClientInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use OAT\Library\Lti1p3Core\Message\Launch\Validator\Tool\ToolLaunchValidator;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceRepositoryInterface;
use Psr\Http\Message\ServerRequestInterface;

class LtiController extends AbstractController
{
    /** @var ManagerRegistry */
    private ManagerRegistry $doctrine;
    
    /** @var Security */
    private $security;

    /** @var RegistrationRepositoryInterface */
    private $repository;

    /** @var ClientInterface */
    private $guzzle;

    /** @var UserPasswordHasherInterface */
    private $passwordHasher;

    /** @var RequestStack */
    private $requestStack;

    /** @var NonceRepositoryInterface $nonceRepository */
    private $nonceRepository;


    public function __construct(
        Security $security,
        RegistrationRepositoryInterface $repository,
        ClientInterface $guzzle,
        ManagerRegistry $doctrine,
        UserPasswordHasherInterface $passwordHasher,
        RequestStack $requestStack,
        NonceRepositoryInterface $nonceRepository
    )
    {
        $this->security = $security;
        $this->repository = $repository;
        $this->guzzle = $guzzle;
        $this->requestStack = $requestStack;
        $this->passwordHasher = $passwordHasher;
        $this->doctrine = $doctrine;
        $this->nonceRepository = $nonceRepository;
    }


    /**
     * @Route("/lti_launch", name="lti_launch")
     */
    public function lti_launch(CourseRepository $courseRepository, LtiAuthenticator $ltiAuthenticator, Request $request, ServerRequestInterface $serverRequest, RegistrationRepositoryInterface $repository, NonceRepositoryInterface $nonceRepository)
    {

        // Create the validator
        $validator = new ToolLaunchValidator($repository, $nonceRepository);

        // Perform validation
        $launch = $validator->validatePlatformOriginatingLaunch($serverRequest);
        $ltiMessage = $launch->getPayload();
        $registration = $launch->getRegistration();



//        $security = $this->security;
//        /** @var LtiToolMessageSecurityToken $token */
//        $token = $this->security->getToken();
//
//
//        if (!$token instanceof LtiToolMessageSecurityToken) {
//            return $this->redirectToRoute('course_index');
//        }
//        // Related registration
//        $registration = $token->getRegistration();
//        // You can even access validation results
//        $validationResults = $token->getValidationResult();
//
//
//        // Related LTI message
//        //all the payload from ELC; payload depend on how Deployment is created on platform;
//        // be sure to include all user and course info in Security Settings
//        $ltiMessage = $token->getPayload();


        $this->requestStack->getSession()->set('lti_registration', $registration->getIdentifier());
        $this->requestStack->getSession()->set('deployment_id', $ltiMessage->getDeploymentId());


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
    public function nrps(Permissions $permissions, string $courseid, Lti $lti)
    {
        $course = $this->doctrine->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $classlists = $this->doctrine->getManager()->getRepository('App:Classlist')->findByCourseid($courseid);
        $role = $permissions->getCourseRole($courseid);

        $registration = $this->requestStack->getSession()->get('lti_registration');
        $deployment_id = $this->requestStack->getSession()->get('deployment_id');
        $method = 'get';
        $scope = 'https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly';
        $accept_header = 'application/vnd.ims.lti-nrps.v2.membershipcontainer+json';

        $registration = $this->repository->find($registration);
        $uri = $registration->getPlatform()->getAudience() . '/d2l/api/lti/nrps/2.0/deployment/' . $deployment_id . '/orgunit/' . $course->getLtiId() . '/memberships';
        $access_token = $lti->getAccessToken($registration, $scope);
        $options = $lti->getHeaderOptions($access_token, $accept_header);
        $response = $this->guzzle->request($method, $uri, $options);
        $data = json_decode($response->getBody()->__toString(), true);
        return $this->render('lti/nrps_ajax.html.twig', [
            'membership' => $data,
        ]);
    }


    /**
     * @Route("/lti/{test}/guzzle", name="guzzle_test", methods={"GET"})
     */
    public function guzzle(string $test)
    {
        if ($test == 1) {
            $uri = 'https://sso.uga.edu/cas/login?service=https%3A%2F%2Felw.english.uga.edu%2Fcourse';

        } elseif ($test == 2) {
            $uri = 'https://sso.uga.edu/cas/serviceValidate?ticket=ST-33357-D85QATI0vpmmMT8SS4l8kuVRrcQ-sso.uga.edu&service=https%3A%2F%2Felw.english.uga.edu%2Fcourse';
        } else {
            $uri = 'https://www.nytimes.com/';
        }
        $method = 'get';
        $options = [];

        $response = $this->guzzle->request($method, $uri, $options);
        $data = json_decode($response->getBody()->__toString(), true);
        dd($data);

    }

    /**
     * @Route("/lti/{courseid}/ags_index", name="ags_index", methods={"GET"})
     */
    public function ags(Permissions $permissions, string $courseid, Lti $lti)
    {
        $course = $this->doctrine->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $classlists = $this->doctrine->getManager()->getRepository('App:Classlist')->findByCourseid($courseid);
        $role = $permissions->getCourseRole($courseid);

        $registration = $this->requestStack->getSession()->get('lti_registration');
        $deployment_id = $this->requestStack->getSession()->get('deployment_id');

        $method = 'get';
        $scopes = ['https://purl.imsglobal.org/spec/lti-ags/scope/lineitem'];
        $accept_header = 'application/vnd.ims.lis.v2.lineitemcontainer+json';

        $registration = $this->repository->find($registration);
        $uri = $registration->getPlatform()->getAudience() . '/d2l/api/lti/ags/2.0/deployment/' . $deployment_id . '/orgunit/' . $course->getLtiId() . '/lineitems';
        $access_token = $lti->getAccessToken($registration, $scopes);
        $options = $lti->getHeaderOptions($access_token, $accept_header);
        $response = $this->guzzle->request($method, $uri, $options);
        $data = json_decode($response->getBody()->__toString(), true);

        return $this->render('lti/ags_index.html.twig', [
            'lineitems' => $data,
            'classlists' => $classlists,
            'course' => $course,
            'role' => $role,
        ]);
    }


    /**
     * @Route("/lti/{courseid}/{agsid}/ags_delete", name="ags_delete", methods={"GET"})
     */
    public function ags_delete(Permissions $permissions, string $courseid, string $agsid, Lti $lti)
    {
        $course = $this->doctrine->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $classlists = $this->doctrine->getManager()->getRepository('App:Classlist')->findByCourseid($courseid);
        $role = $permissions->getCourseRole($courseid);

        $local_ags = $this->doctrine->getManager()->getRepository('App:LtiAgs')->findOneByAgsid($agsid);
        $registration = $this->requestStack->get('lti_registration');
        $method = 'DELETE';
        $scope = 'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem';
        $accept_header = 'application/vnd.ims.lis.v2.lineitemcontainer+json';

        $registration = $this->repository->find($registration);
        $uri = $local_ags->getLtiId();
        $access_token = $lti->getAccessToken($registration, $scope);
        $options = $lti->getHeaderOptions($access_token, $accept_header);
        $response = $this->guzzle->request($method, $uri, $options);
        $data = json_decode($response->getBody()->__toString(), true);
//        dd($data);
        //delete local as well
        $ltiAg = $this->doctrine->getManager()->getRepository('App:LtiAgs')->findOneByAgsid($agsid);
        $entityManager = $this->doctrine->getManager();
        $entityManager->remove($ltiAg);
        $entityManager->flush();
        $this->addFlash('notice', 'The grade columns was deleted.');
        return $this->redirectToRoute('ags_index', ['courseid' => $courseid]);
    }


    /**
     * @Route("/lti/{courseid}/ags_new", name="ags_new", methods={"GET","POST"})
     */
    public function ags_new(Request $request, Permissions $permissions, string $courseid, Lti $lti)
    {
        $form = $this->createForm(LtiAgsLineitemType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $course = $this->doctrine->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
            $role = $permissions->getCourseRole($courseid);

            $registration = $this->requestStack->get('lti_registration');
            $deployment_id = $this->requestStack->get('deployment_id');

            $method = 'POST';
            $scope = 'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem';
            $accept_header = 'application/vnd.ims.lis.v2.lineitem+json';
            $data = $form->getData();

            $registration = $this->repository->find($registration);
            $uri = $registration->getPlatform()->getAudience() . '/d2l/api/lti/ags/2.0/deployment/' . $deployment_id . '/orgunit/' . $course->getLtiId() . '/lineitems';
            $access_token = $lti->getAccessToken($registration, $scope);
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
            $this->doctrine->getManager()->persist($ags);
            $this->doctrine->getManager()->flush();
            $this->addFlash('notice', 'The grade column was added.');
            return $this->redirectToRoute('course_show', ['courseid' => $courseid]);

        }

        return $this->render('lti/new_ags_lineitem.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/lti/{courseid}/{docid}/{source}/ags_score", name="ags_score_new", methods={"GET","POST"})
     */
    public function ags_score_new(Request $request, Permissions $permissions, Lti $lti, string $courseid, string $docid, string $source)
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
            $results = $lti->getLtiResult($doc->getAgsResultId());
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
            $registration = $this->requestStack->get('lti_registration');
            $method = 'POST';
            $scope = 'https://purl.imsglobal.org/spec/lti-ags/scope/score';
            $accept_header = 'application/vnd.ims.lis.v1.score+json';
            $data = $form->getData();
            $agsid = $data['uri'];
            $local_ags = $this->doctrine->getManager()->getRepository('App:LtiAgs')->findOneByAgsid($agsid);
            $uri = $local_ags->getLtiId() . '/scores';
            $scoreMaximum = $local_ags->getMax();
            $timestamp = date(\DateTime::ISO8601);
            $registration = $this->repository->find($registration);
            $access_token = $lti->getAccessToken($registration, $scope);
            if (is_null($data['comment'])) {
                $datacomment = '';
            }
            else {
                $datacomment = $data['comment'];
            }
            $options = [
                'headers' => ['Authorization' => sprintf('Bearer %s', $access_token), 'Accept' => $accept_header],
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
                $response = $this->guzzle->request($method, $uri, $options);
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
