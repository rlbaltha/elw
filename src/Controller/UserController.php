<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Form\UsernameType;
use App\Form\IrbType;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @Route("/user")
 */
class UserController extends AbstractController
{
    /** @var UserPasswordHasherInterface */
    private $passwordHasher;

    /** @var ManagerRegistry */
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine, UserPasswordHasherInterface $passwordHasher)
    {
        $this->doctrine = $doctrine;
        $this->passwordHasher = $passwordHasher;
    }
    
    /**
     * @Route("/admin/", name="user_index", methods={"GET"})
     */
    public function index(PaginatorInterface $paginator, UserRepository $userRepository, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $user = new User();
        $form = $this->createFindForm($user);
        $users = null;

        return $this->render('user/index.html.twig', [
            'users' => $users,
            'form'=>$form->createView()
        ]);
    }


    /**
     * @Route("/admin/find", name="user_find", methods={"GET","POST"})
     */
    public function find(UserRepository $userRepository, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $user = new User();
        $form = $this->createFindForm($user);
        $postData = $request->request->get('form');
        $name = $postData['lastname'];

        $users = $userRepository->findByLastname($name);

        return $this->render('user/index.html.twig', [
            'users' => $users,
            'form'=>$form->createView()
        ]);
    }


    /**
     * @Route("/admin/{id}", name="user_show", methods={"GET"})
     */
    public function show(User $user): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * Creates a form to find Users by lastname.
     *
     * @param User $user The entity
     *
     * @return Form form
     */
    private function createFindForm(User $user)
    {
        $form = $this->createFormBuilder($user)
            ->setAction($this->generateUrl('user_find'))
            ->add('lastname',TextType::class, array('label'  => 'Find by Lastname','attr' => array('class' => 'form-control'),))
            ->getForm();

        return $form;


    }

    /**
     * @Route("/admin/{id}/edit", name="user_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, User $user): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->getManager()->flush();
            $this->addFlash('notice', 'This profile has been updated.');
            return $this->redirectToRoute('user_index');
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/{courseid}/username_edit", name="username_edit", methods={"GET","POST"})
     */
    public function username_edit(Request $request, User $user, $courseid): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $form = $this->createForm(UsernameType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->getManager()->flush();
            $this->addFlash('notice', 'This profile has been updated.');
            return $this->redirectToRoute('course_show', [
                'courseid' => $courseid,
            ]);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

//    /**
//     * @Route("/admin/{id}/promote", name="user_promote", methods={"GET"})
//     */
//    public function promote(User $user): Response
//    {
//        $this->denyAccessUnlessGranted('ROLE_ADMIN');
//        $this->addFlash('notice', 'This profile has been promoted.');
//        return $this->render('user/show.html.twig', [
//            'user' => $user,
//        ]);
//    }

    /**
     * @Route("/{courseid}/theme", name="user_theme", methods={"GET"})
     */
    public function theme(string $courseid): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $username = $this->getUser()->getUsername();
        $user = $this->doctrine->getManager()->getRepository('App:User')->findOneByUsername($username);
        if ($user->getTheme() != 'dark') {
            $user->setTheme('dark');
        }
        else {
            $user->setTheme('light');
        }
        $entityManager = $this->doctrine->getManager();
        $entityManager->persist($user);
        $entityManager->flush();
        return $this->redirectToRoute('course_show', ['courseid' => $courseid]);
    }

    /**
     * @Route("/{courseid}/irb", name="user_irb", methods={"GET","POST"})
     */
    public function irb(Request $request, string $courseid): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $username = $this->getUser()->getUsername();
        $user = $this->doctrine->getManager()->getRepository('App:User')->findOneByUsername($username);
        $irb = $this->doctrine->getManager()->getRepository('App:Card')->findOneByType('irb');
        if ($user->getIrb() == null) {
            $user->setIrb(1);
        }
        $form = $this->createForm(IrbType::class, $user, [
            'action' => $this->generateUrl('user_irb', ['courseid'=>$courseid]),
            'method' => 'POST',
        ]);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->getManager()->flush();
            $this->addFlash('notice', 'Your Research Permission has been updated.');
            return $this->redirectToRoute('course_show', [
                'courseid' => $courseid,
            ]);
        }

        return $this->render('user/_irb_form.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
            'irb' => $irb
        ]);
    }

    /**
     * @Route("/admin/{id}", name="user_delete", methods={"POST"})
     */
    public function delete(Request $request, User $user): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager = $this->doctrine->getManager();
            $entityManager->remove($user);
            $entityManager->flush();
        }
        $this->addFlash('notice', 'This profile has been deleted.');
        return $this->redirectToRoute('user_index');
    }

}
