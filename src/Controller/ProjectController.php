<?php

namespace App\Controller;

use App\Entity\Project;
use App\Form\ProjectType;
use App\Repository\DocRepository;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use App\Service\Permissions;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Repository\CourseRepository;
use App\Repository\MarkupsetRepository;
use App\Repository\RubricRepository;

#[Route(path: '/project')]
class ProjectController extends AbstractController
{

    /** @var ManagerRegistry */
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }
    
    #[Route(path: '/{courseid}/index', name: 'project_index', methods: ['GET'])]
    public function index(ProjectRepository $projectRepository, Permissions $permissions, string $courseid, CourseRepository $courseRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_INSTRUCTOR');
        $role = $permissions->getCourseRole($courseid);
        $course = $courseRepository->findOneByCourseid($courseid);
        return $this->render('project/index.html.twig', [
            'projects' => $projectRepository->findByCourse($courseid),
            'course' => $course,
            'role' => $role
        ]);
    }
    #[Route(path: '/{courseid}/new', name: 'project_new', methods: ['GET', 'POST'])]
    public function new(Request $request, $courseid, UserRepository $userRepository, CourseRepository $courseRepository, MarkupsetRepository $markupsetRepository, RubricRepository $rubricRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_INSTRUCTOR');

        $username = $this->getUser()->getUsername();
        $user = $userRepository->findOneByUsername($username);
        $course = $courseRepository->findOneByCourseid($courseid);
        $rubrics = $rubricRepository->findByUser($user);
        $markupsets = $markupsetRepository->findByUser($user);
        $options = ['user' => $user, 'courseid' => $courseid];
        $defaultrubrics = $rubricRepository->findDefaults();
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

    #[Route(path: '/{id}/edit', name: 'project_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Project $project, Permissions $permissions, DocRepository $docRepository, UserRepository $userRepository, CourseRepository $courseRepository, MarkupsetRepository $markupsetRepository, RubricRepository $rubricRepository): Response
    {

        $this->denyAccessUnlessGranted('ROLE_INSTRUCTOR');

        $username = $this->getUser()->getUsername();
        $user = $userRepository->findOneByUsername($username);
        $courseid = $project->getCourse()->getId();
        $role = $permissions->getCourseRole($courseid);
        $course = $courseRepository->findOneByCourseid($courseid);
        $rubrics = $rubricRepository->findByUser($user);
        $markupsets = $markupsetRepository->findByUser($user);

        $docs_query = $docRepository->findByProject($course, $role, $project);
        $docs = $docs_query->getQuery()->getResult();

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
            'docs'=> $docs
        ]);
    }

    #[Route(path: '/{id}', name: 'project_delete', methods: ['POST'])]
    public function delete(Request $request, Project $project): Response
    {
        $this->denyAccessUnlessGranted('ROLE_INSTRUCTOR');

        $courseid = $project->getCourse()->getId();

        if ($this->isCsrfTokenValid('delete'.$project->getId(), $request->request->get('_token'))) {
            $entityManager = $this->doctrine->getManager();
            $entityManager->remove($project);
            $entityManager->flush();
        }
        $this->addFlash('notice', 'Your Project has been deleted.');
        return $this->redirectToRoute('course_show', ['courseid'=> $courseid]);
    }
}

