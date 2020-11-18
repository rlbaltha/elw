<?php

namespace App\Controller;

use App\Entity\LtiAgs;
use App\Form\LtiAgsType;
use App\Repository\LtiAgsRepository;
use App\Service\Permissions;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/lti/ags")
 */
class LtiAgsController extends AbstractController
{
    /**
     * @Route("/{courseid}/local_index", name="lti_ags_index", methods={"GET"})
     */
    public function index(Permissions $permissions, LtiAgsRepository $ltiAgsRepository, string $courseid): Response
    {
        $role = $permissions->getCourseRole($courseid);
        return $this->render('lti_ags/index.html.twig', [
            'lti_ags' => $ltiAgsRepository->findByCourseid($courseid),
            'role' => $role,
        ]);
    }

    /**
     * @Route("/new", name="lti_ags_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $courseid =26;
        $course = $this->getDoctrine()->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $ltiAg = new LtiAgs();
        $form = $this->createForm(LtiAgsType::class, $ltiAg);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ltiAg->setCourse($course);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($ltiAg);
            $entityManager->flush();

            return $this->redirectToRoute('lti_ags_index', ['courseid' => $courseid]);
        }

        return $this->render('lti_ags/new.html.twig', [
            'lti_ag' => $ltiAg,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="lti_ags_show", methods={"GET"})
     */
    public function show(LtiAgs $ltiAg): Response
    {
        return $this->render('lti_ags/show.html.twig', [
            'lti_ag' => $ltiAg,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="lti_ags_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, LtiAgs $ltiAg): Response
    {
        $form = $this->createForm(LtiAgsType::class, $ltiAg);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('lti_ags_index');
        }

        return $this->render('lti_ags/edit.html.twig', [
            'lti_ag' => $ltiAg,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="lti_ags_delete", methods={"DELETE"})
     */
    public function delete(Request $request, LtiAgs $ltiAg): Response
    {
        $courseid = $ltiAg->getCourse()->getId();
        if ($this->isCsrfTokenValid('delete'.$ltiAg->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($ltiAg);
            $entityManager->flush();
        }

        return $this->redirectToRoute('lti_ags_index', ['courseid' => $courseid]);
    }
}
