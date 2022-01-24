<?php

namespace App\Controller;

use App\Entity\Classlist;
use App\Form\ClasslistType;
use App\Repository\ClasslistRepository;
use App\Service\Permissions;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/classlist")
 */
class ClasslistController extends AbstractController
{
    /** @var ManagerRegistry */
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

//    /**
//     * @Route("/{courseid}/new", name="classlist_new", methods={"GET","POST"})
//     */
//    public function new(Request $request, $courseid): Response
//    {
//        $classlist = new Classlist();
//        $form = $this->createForm(ClasslistType::class, $classlist);
//        $form->handleRequest($request);
//
//        if ($form->isSubmitted() && $form->isValid()) {
//            $entityManager = $this->doctrine->getManager();
//            $entityManager->persist($classlist);
//            $entityManager->flush();
//
//            return $this->redirectToRoute('course_show', ['courseid' => $classlist->getCourse()->getId()]);
//        }
//
//        return $this->render('classlist/new.html.twig', [
//            'classlist' => $classlist,
//            'form' => $form->createView(),
//        ]);
//    }



    /**
     * @Route("/{id}/edit", name="classlist_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Classlist $classlist): Response
    {
        $form = $this->createForm(ClasslistType::class, $classlist);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->getManager()->flush();
            $this->addFlash('notice', 'This student has been updated.');
            return $this->redirectToRoute('course_show', ['courseid' => $classlist->getCourse()->getId()]);
        }

        return $this->render('classlist/edit.html.twig', [
            'classlist' => $classlist,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="classlist_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Classlist $classlist): Response
    {
        $courseid = $classlist->getCourse()->getId();
        if ($this->isCsrfTokenValid('delete'.$classlist->getId(), $request->request->get('_token'))) {
            $entityManager = $this->doctrine->getManager();
            $entityManager->remove($classlist);
            $entityManager->flush();
        }
        $this->addFlash('notice', 'The student has been removed.');
        return $this->redirectToRoute('course_show', ['courseid' => $courseid]);
    }
}
