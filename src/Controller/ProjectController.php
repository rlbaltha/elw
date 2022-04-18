<?php

namespace App\Controller;

use App\Entity\Project;
use App\Form\ProjectType;
use App\Repository\DocRepository;
use App\Repository\ProjectRepository;
use App\Service\Permissions;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route("/project")
 */
class ProjectController extends AbstractController
{

    /** @var ManagerRegistry */
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }
    
    /**
     * @Route("/{courseid}/index", name="project_index", methods={"GET"})
     */
    public function index(ProjectRepository $projectRepository, Permissions $permissions, string $courseid): Response
    {
        $this->denyAccessUnlessGranted('ROLE_INSTRUCTOR');
        $role = $permissions->getCourseRole($courseid);
        $course = $this->doctrine->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        return $this->render('project/index.html.twig', [
            'projects' => $projectRepository->findByCourse($courseid),
            'course' => $course,
            'role' => $role
        ]);
    }
    /**
     * @Route("/{courseid}/new", name="project_new", methods={"GET","POST"})
     */
    public function new(Request $request, $courseid): Response
    {
        $this->denyAccessUnlessGranted('ROLE_INSTRUCTOR');

        $username = $this->getUser()->getUsername();
        $user = $this->doctrine->getManager()->getRepository('App:User')->findOneByUsername($username);
        $course = $this->doctrine->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $rubrics = $this->doctrine->getManager()->getRepository('App:Rubric')->findByUser($user);
        $markupsets = $this->doctrine->getManager()->getRepository('App:Markupset')->findByUser($user);
        $options = ['user' => $user, 'courseid' => $courseid];
        $defaultrubrics = $this->doctrine->getManager()->getRepository('App:Rubric')->findDefaults();
        $project = new Project();
        foreach ($defaultrubrics as $rubric) {
            $project->addRubric($rubric);
        };
        $form = $this->createForm(ProjectType::class, $project, ['options' => $options]);
        $form->handleRequest($request);
        $entityManager = $this->doctrine->getManager();

        $project->setCourse($course);
        $project->setUser($user);
        
        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->persist($project);
            $entityManager->flush();
            $this->addFlash('notice', 'Your Project has been created.');
            return $this->redirectToRoute('course_show', ['courseid'=> $courseid]);
        }

        return $this->render('project/new.html.twig', [
            'rubrics' => $rubrics,
            'markupsets' => $markupsets,
            'project' => $project,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit", name="project_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Project $project, Permissions $permissions, DocRepository $docRepository): Response
    {

        $this->denyAccessUnlessGranted('ROLE_INSTRUCTOR');

        $username = $this->getUser()->getUsername();
        $user = $this->doctrine->getManager()->getRepository('App:User')->findOneByUsername($username);
        $courseid = $project->getCourse()->getId();
        $role = $permissions->getCourseRole($courseid);
        $course = $this->doctrine->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $rubrics = $this->doctrine->getManager()->getRepository('App:Rubric')->findByUser($user);
        $markupsets = $this->doctrine->getManager()->getRepository('App:Markupset')->findByUser($user);

        $docs[] = $this->doctrine->getManager()->getRepository('App:Doc')->findByProject($course, $role, $project);
        $countDocs = count($docs);
        $options = ['user' => $user, 'courseid' => $courseid];
        $form = $this->createForm(ProjectType::class, $project, ['options' => $options]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->getManager()->flush();
            $this->addFlash('notice', 'Your Project has been updated.');
            return $this->redirectToRoute('course_show', ['courseid'=> $courseid]);
        }

        return $this->render('project/edit.html.twig', [
            'rubrics' => $rubrics,
            'markupsets' => $markupsets,
            'project' => $project,
            'form' => $form->createView(),
            'countDocs'=> $countDocs
        ]);
    }

    /**
     * @Route("/{id}", name="project_delete", methods={"POST"})
     */
    public function delete(Request $request, Project $project): Response
    {
        $this->denyAccessUnlessGranted('ROLE_INSTRUCTOR');

        if ($project->getLabelset()) {
            $courses = $project->getLabelset()->getCourses();
            $courseid = $courses[0]->getId();
        }
        else {
            $courseid = $project->getCourse()->getId();
        }

        if ($this->isCsrfTokenValid('delete'.$project->getId(), $request->request->get('_token'))) {
            $entityManager = $this->doctrine->getManager();
            $entityManager->remove($project);
            $entityManager->flush();
        }
        $this->addFlash('notice', 'Your Project has been deleted.');
        return $this->redirectToRoute('project_index', ['courseid'=> $courseid]);
    }
}

