<?php

namespace App\Controller;

use App\Entity\Classlist;
use App\Entity\Course;
use App\Entity\User;
use App\Repository\CourseRepository;
use App\Security\LtiAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use OAT\Bundle\Lti1p3Bundle\Security\Authentication\Token\Message\LtiMessageToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;

class DefaultController extends AbstractController
{
    /** @var Security */
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
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
    public function lti_launch(CourseRepository $courseRepository, GuardAuthenticatorHandler $guardAuthenticatorHandler, LtiAuthenticator $ltiAuthenticator, Request $request)
    {
        /** @var LtiMessageToken $token */
        $token = $this->security->getToken();
        if (!$token instanceof LtiMessageToken) {
            throw $this->createAccessDeniedException("This page is not available.");
        }

        // Related registration
        $registration = $token->getRegistration();
        // You can even access validation results
        $validationResults = $token->getValidationResult();

        // Related LTI message
        //all the payload from ELC; payload depend on how Deployment is created on platform;
        // be sure to include all user and course info in Security Settings
        $ltiMessage = $token->getLtiMessage();

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

        // Actual passing of auth to Symfony firewall and sessioning
        $guardAuthenticatorHandler->authenticateUserAndHandleSuccess($user, $request, $ltiAuthenticator, 'main');

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

        return $this->redirectToRoute('course_show', ['courseid' => $courseid]);

    }


}