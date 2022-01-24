<?php

namespace App\Controller;

use App\Entity\Markupset;
use App\Form\MarkupsetType;
use App\Repository\MarkupsetRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route("/markupset")
 */
class MarkupsetController extends AbstractController
{
    /** @var ManagerRegistry */
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }
    
    /**
     * @Route("/", name="markupset_index", methods={"GET"})
     */
    public function index(MarkupsetRepository $markupsetRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('markupset/index.html.twig', [
            'markupsets' => $markupsetRepository->findAll(),
        ]);
    }


    /**
     * @Route("/byuser", name="markupset_byuser", methods={"GET"})
     */
    public function byuser(MarkupsetRepository $markupsetRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_INSTRUCTOR');

        $username = $this->getUser()->getUsername();
        $user = $this->doctrine->getManager()->getRepository('App:User')->findOneByUsername($username);

        return $this->render('markupset/index.html.twig', [
            'markupsets' => $markupsetRepository->findByUser($user),
        ]);
    }
    /**
     * @Route("/new", name="markupset_new", methods={"GET","POST"})
     */
    public function new(Request $request, AuthorizationCheckerInterface $authorizationChecker): Response
    {
        $username = $this->getUser()->getUsername();
        $user = $this->doctrine->getManager()->getRepository('App:User')->findOneByUsername($username);

        $markupset = new Markupset();
        $markupset->setUser($user);

        $form = $this->createForm(MarkupsetType::class, $markupset);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->doctrine->getManager();
            $entityManager->persist($markupset);
            $entityManager->flush();
            $this->addFlash('notice', 'Your Markup Set has been created.');
            if ($authorizationChecker->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('markupset_index');
            }
            return $this->redirectToRoute('markupset_byuser');
        }

        return $this->render('markupset/new.html.twig', [
            'markupset' => $markupset,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="markupset_show", methods={"GET"})
     */
    public function show(Markupset $markupset): Response
    {
        return $this->render('markupset/show.html.twig', [
            'markupset' => $markupset,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="markupset_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Markupset $markupset, AuthorizationCheckerInterface $authorizationChecker): Response
    {
        if (!($this->getUser() == $markupset->getUser() or $this->isGranted('ROLE_ADMIN'))) {
            throw new AccessDeniedException('You do not have permissions to do this!');
        }

        $form = $this->createForm(MarkupsetType::class, $markupset);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->getManager()->flush();
            $this->addFlash('notice', 'Your Markup Set has been updated.');
            if ($authorizationChecker->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('markupset_index');
            }
            return $this->redirectToRoute('markupset_byuser');
        }

        return $this->render('markupset/edit.html.twig', [
            'markupset' => $markupset,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="markupset_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Markupset $markupset): Response
    {
        if ($this->isCsrfTokenValid('delete'.$markupset->getId(), $request->request->get('_token'))) {
            $entityManager = $this->doctrine->getManager();
            $entityManager->remove($markupset);
            $entityManager->flush();
        }
        $this->addFlash('notice', 'Your Markup Set has been deleted.');
        return $this->redirectToRoute('markupset_byuser');
    }
}
