<?php

namespace App\Controller;

use App\Entity\Project;
use App\Form\ProjectType;
use App\Repository\ProjectRepository;
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

    /**
     * @Route("/{labelsetid}/new", name="project_new", methods={"GET","POST"})
     */
    public function new(Request $request, $labelsetid): Response
    {
        $username = $this->getUser()->getUsername();
        $user = $this->getDoctrine()->getManager()->getRepository('App:User')->findOneByUsername($username);
        $options = ['user' => $user];
        $project = new Project();
        $form = $this->createForm(ProjectType::class, $project, ['options' => $options]);
        $form->handleRequest($request);
        $entityManager = $this->getDoctrine()->getManager();
        $labelset = $this->getDoctrine()->getManager()->getRepository('App:Labelset')->findOneById($labelsetid);
        $project->setLabelset( $labelset);
        $project->setUser($user);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->persist($project);
            $entityManager->flush();
            $this->addFlash('notice', 'Your Project has been created.');
            return $this->redirectToRoute('labelset_show', ['id'=> $labelsetid]);
        }

        return $this->render('project/new.html.twig', [
            'project' => $project,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit", name="project_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Project $project): Response
    {
        if (!($this->getUser() == $project->getUser() or $this->isGranted('ROLE_ADMIN'))) {
            throw new AccessDeniedException('You do not have permissions to do this!');
        }
        $username = $this->getUser()->getUsername();
        $user = $this->getDoctrine()->getManager()->getRepository('App:User')->findOneByUsername($username);
        $labelset = $project->getLabelset();
        $options = ['user' => $user];
        $form = $this->createForm(ProjectType::class, $project, ['options' => $options]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('notice', 'Your Project has been updated.');
            return $this->redirectToRoute('labelset_show', ['id'=> $labelset->getId()]);
        }

        return $this->render('project/edit.html.twig', [
            'project' => $project,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="project_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Project $project): Response
    {
        $labelset = $project->getLabelset();

        if ($this->isCsrfTokenValid('delete'.$project->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($project);
            $entityManager->flush();
        }
        $this->addFlash('notice', 'Your Project has been deleted.');
        return $this->redirectToRoute('labelset_show', ['id'=> $labelset->getId()]);
    }
}

