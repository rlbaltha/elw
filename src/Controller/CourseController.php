<?php

namespace App\Controller;

use App\Entity\Classlist;
use App\Entity\Course;
use App\Form\AnnouncementType;
use App\Form\IrbType;
use App\Form\CourseType;
use App\Repository\CourseRepository;
use App\Service\Permissions;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/course")
 */
class CourseController extends AbstractController
{

    /** @var ManagerRegistry */
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }
    
    /**
     * @Route("/admin", name="course_admin", methods={"GET"})
     */
    public function admin(CourseRepository $courseRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_INSTRUCTOR');
        $header = 'Admin Course List';
        return $this->render('course/index.html.twig', [
            'courses' => $courseRepository->findAll(),
            'header' => $header
        ]);
    }


    /**
     * @Route("/{status}/", name="course_index", methods={"GET"}, defaults={"status" = "default"})
     */
    public function index(CourseRepository $courseRepository, string $status): Response
    {
        $username = $this->getUser()->getUsername();
        $user = $this->doctrine->getManager()->getRepository('App:User')->findOneByUsername($username);
        $courses = $this->doctrine->getManager()->getRepository('App:Course')->findByUserAndTerm($user, $status);
        if($status == 'default') {
            $header = 'My Current Courses';
        }
        else{
            $header = 'My Course Archive';
        }
        return $this->render('course/index.html.twig', [
            'courses' => $courses,
            'header' => $header
        ]);
    }

    /**
     * @Route("/new", name="course_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_INSTRUCTOR');

        $username = $this->getUser()->getUsername();
        $user = $this->doctrine->getManager()->getRepository('App:User')->findOneByUsername($username);

        $labelsets = $this->doctrine->getManager()->getRepository('App:Labelset')->findDefault();
        $markupsets = $this->doctrine->getManager()->getRepository('App:Markupset')->findDefault();
        $course = new Course();
        foreach ($labelsets as $labelset) {
            $course->addLabelset($labelset);
        }
        foreach ($markupsets as $markupset) {
            $course->addMarkupset($markupset);
        }
        $options = ['user' => $user];
        $form = $this->createForm(CourseType::class, $course, ['options' => $options]);
        $form->handleRequest($request);
        $classlist = new Classlist();
        $classlist->setUser($user);
        $classlist->setCourse($course);
        $classlist->setRole('Instructor');
        $classlist->setStatus('Approved');

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->doctrine->getManager();
            $entityManager->persist($course);
            $entityManager->persist($classlist);
            $entityManager->flush();

            return $this->redirectToRoute('course_index');
        }

        return $this->render('course/new.html.twig', [
            'course' => $course,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{courseid}/show", name="course_show", methods={"GET"})
     */
    public function show(Permissions $permissions, string $courseid): Response
    {
        //discover needed info on request
        $course = $this->doctrine->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $username = $this->getUser()->getUsername();
        $user = $this->doctrine->getManager()->getRepository('App:User')->findOneByUsername($username);
        $classuser = $this->doctrine->getManager()->getRepository('App:Classlist')->findCourseUser($course, $user);

        $role = $permissions->getCourseRole($courseid);
        //check status and show course page
        $status = $classuser->getStatus();
        $course = $this->doctrine->getManager()->getRepository('App:Course')->find($courseid);
        $classlists = $this->doctrine->getManager()->getRepository('App:Classlist')->findByCourseid($courseid);

        $irb = $this->doctrine->getManager()->getRepository('App:Card')->findOneByType('irb');

        $form = $this->createForm(IrbType::class, $user, [
            'action' => $this->generateUrl('user_irb', ['courseid' => $courseid]),
            'method' => 'POST',
        ]);
        return $this->render('course/show.html.twig', [
            'course' => $course,
            'classlists' => $classlists,
            'role' => $role,
            'user' => $user,
            'status' => $status,
            'irb' => $irb,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/{courseid}/edit", name="course_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Permissions $permissions, $courseid): Response
    {
        $allowed = ['Instructor'];
        $permissions->restrictAccessTo($courseid, $allowed);

        $username = $this->getUser()->getUsername();
        $user = $this->doctrine->getManager()->getRepository('App:User')->findOneByUsername($username);
        $course = $this->doctrine->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $options = ['user' => $user];
        $form = $this->createForm(CourseType::class, $course, ['options' => $options]);
        $form->handleRequest($request);
        $message = 'Your course has been updated. <br/>  Be sure that you have at least one Project for your course.  
            <a aria-label="Add project to {{ course.name }}" class="btn btn-primary btn-sm"
                   href="/project/'.$courseid.'/new">Add
                    Project</a>';

        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->getManager()->flush();
            $this->addFlash('notice', $message);
            return $this->redirectToRoute('course_show', ['courseid' => $courseid]);
        }

        return $this->render('course/edit.html.twig', [
            'course' => $course,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{courseid}/announcement", name="course_announcement", methods={"GET","POST"})
     */
    public function announcement(Request $request, Permissions $permissions, $courseid): Response
    {
        $allowed = ['Instructor'];
        $permissions->restrictAccessTo($courseid, $allowed);

        $course = $this->doctrine->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $form = $this->createForm(AnnouncementType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->getManager()->flush();
            $this->addFlash('notice', 'Your eLW Reminder has been updated.');
            return $this->redirectToRoute('course_show', ['courseid' => $courseid]);
        }

        return $this->render('course/edit.html.twig', [
            'course' => $course,
            'form' => $form->createView(),
        ]);
    }

    /**
     *  Approves all pending student
     * @Route("/approve_all_pending/{courseid}" , name="approve_all_pending")
     *
     */
    public function approveAllAction(Permissions $permissions, $courseid)
    {
        $allowed = ['Instructor'];
        $permissions->restrictAccessTo($courseid, $allowed);

        $entityManager = $this->doctrine->getManager();
        $classlists = $this->doctrine->getManager()->getRepository('App:Classlist')->findByCourseid($courseid);
        foreach ($classlists as $classlist) {
            if ($classlist->getStatus() == 'Pending') {
                $classlist->setStatus('Approved');
                $entityManager->persist($classlist);
            }
        }
        $entityManager->flush();
        return $this->redirect($this->generateUrl('course_show', ['courseid' => $courseid]));

    }


    /**
     * @Route("/{id}", name="course_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Permissions $permissions, Course $course): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('delete' . $course->getId(), $request->request->get('_token'))) {
            $entityManager = $this->doctrine->getManager();
            $entityManager->remove($course);
            $entityManager->flush();
        }
        $this->addFlash('notice', 'This course has been deleted.');
        return $this->redirectToRoute('course_index');
    }
}
