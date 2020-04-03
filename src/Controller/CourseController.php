<?php

namespace App\Controller;

use App\Entity\Classlist;
use App\Entity\Course;
use App\Form\AnnouncementType;
use App\Form\CourseType;
use App\Repository\CourseRepository;
use App\Service\Permissions;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/course")
 */
class CourseController extends AbstractController
{
    /**
     * @Route("/", name="course_index", methods={"GET"})
     */
    public function index(CourseRepository $courseRepository): Response
    {
        return $this->render('course/index.html.twig', [
            'courses' => $courseRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="course_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_INSTRUCTOR');

        $username = $this->getUser()->getUsername();
        $user = $this->getDoctrine()->getManager()->getRepository('App:User')->findOneByUsername($username);

        $course = new Course();
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);
        $classlist = new Classlist();
        $classlist->setUser($user);
        $classlist->setCourse($course);
        $classlist->setRole('Instructor');
        $classlist->setStatus('Admitted');

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
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
    public function show(Permissions $permissions, String $courseid): Response
    {
        //discover need info on request
        $course = $this->getDoctrine()->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $username = $this->getUser()->getUsername();
        $user = $this->getDoctrine()->getManager()->getRepository('App:User')->findOneByUsername($username);
        $classuser = $this->getDoctrine()->getManager()->getRepository('App:Classlist')->findCourseUser($course, $user);

        // check if on classlist
        if (!$classuser) {
            $classlist = new Classlist();
            $classlist->setUser($user);
            $classlist->setCourse($course);
            $classlist->setRole('Student');
            $classlist->setStatus('Pending');
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($classlist);
            $entityManager->flush();
            $this->addFlash('notice', 'Your admission to the course has not yet been approved.');
            return $this->redirectToRoute('course_show', ['courseid' => $courseid]);
        }  // test role access
        else {
            $allowed = ['Instructor', 'Student'];
            $permissions->restrictAccessTo($courseid, $allowed);

            $course = $this->getDoctrine()->getManager()->getRepository('App:Course')->find($courseid);
            return $this->render('course/show.html.twig', [
                'course' => $course,
            ]);
        }
    }

    /**
     * @Route("/{courseid}/edit", name="course_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Permissions $permissions, $courseid): Response
    {
        $allowed = ['Instructor'];
        $permissions->restrictAccessTo($courseid, $allowed);

        $course = $this->getDoctrine()->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

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

        $course = $this->getDoctrine()->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $form = $this->createForm(AnnouncementType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

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

        $entityManager = $this->getDoctrine()->getManager();
        $classlists = $this->getDoctrine()->getManager()->getRepository('App:Classlist')->findByCourseid($courseid);
        foreach($classlists as $classlist){
            if ($classlist->getStatus() == 'Pending'){
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

        if ($this->isCsrfTokenValid('delete'.$course->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($course);
            $entityManager->flush();
        }

        return $this->redirectToRoute('course_index');
    }
}
