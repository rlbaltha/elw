<?php

namespace App\Controller;

use App\Entity\Ratingset;
use App\Form\RatingsetType;
use App\Repository\RatingsetRepository;
use App\Service\Permissions;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Rating;


#[Route('/ratingset')]
class RatingsetController extends AbstractController
{
    /** @var ManagerRegistry */
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    #[Route('/', name: 'app_ratingset_index', methods: ['GET'])]
    public function index(RatingsetRepository $ratingsetRepository): Response
    {
        return $this->render('ratingset/index.html.twig', [
            'ratingsets' => $ratingsetRepository->findAll(),
        ]);
    }

    // set the choices for the new and edit rating form (level denotes default).  These options must match the rating_ajax.html.twig
    // template
    public function choices($rubric_level) {
        if ($rubric_level !== 0) {
            $choices = ['1: Holistic revision necessary. The student may need to revise the full
                    document.' => '1',
                '2: Substantial revision necessary. The student may need to revise elements in a majority of the document.' => '2',
                ' 3: Some revision necessary. The student may need to rethink or
                    restructure one or more paragraphs or large sections.' => '3',
                '4: Slight revision necessary. Some adjustments on the sentence or
                    paragraph level would help the document stand out.' => '4',
                '5: No revision necessary. The document is exemplary as it stands. While
                    further improvement is still (and always) possible, time would be better
                    spent elsewhere.' => '5',];
        }
        else {
            $choices = ['1: Unengaged with the writing process or no evidence of revision.' => '1',
                '2: Minimally engaged with the writing process.' => '2',
                ' 3: Somewhat engaged with the writing process.' => '3',
                '4: Highly engaged with the writing process.' => '4',
                '5: Exemplary engagement with the draft structure or revision process.' => '5',];
        }
        return $choices;
    }

    #[Route('/{docid}/{courseid}/new', name: 'app_ratingset_new', methods: ['GET', 'POST'])]
    public function new(Request $request, RatingsetRepository $ratingsetRepository, Permissions $permissions, int $docid, int $courseid): Response
    {
        $ratingset = new Ratingset();
        $header = 'Rubric Collection Ratings';
        $course = $this->doctrine->getManager()->getRepository('App:Course')->findOneByCourseid($courseid);
        $role = $permissions->getCourseRole($courseid);
        $username = $this->getUser()->getUsername();
        $user = $this->doctrine->getManager()->getRepository('App:User')->findOneByUsername($username);
        $doc = $this->doctrine->getManager()->getRepository('App:Doc')->find($docid);
        $project = $doc->getProject();
        $rubrics = $project->getRubrics();
        $cnt = count($rubrics);

        for ($i = 0; $i < $cnt; $i++) {
            $rubric = $rubrics[$i];
            $rating = new Rating();
            $rating->setRatingset($ratingset);
            $rating->setUser($user);
            $rating->setDoc($doc);
            $rating->setRubric($rubric);
            $entityManager = $this->doctrine->getManager();
            $entityManager->persist($rating);
        }
        $entityManager->persist($ratingset);
        $entityManager->flush();
        return $this->redirect($this->generateUrl('app_ratingset_edit', array('id' => $ratingset->getId(),'courseid' => $courseid, )));
    }

    #[Route('/{id}', name: 'app_ratingset_show', methods: ['GET'])]
    public function show(Ratingset $ratingset): Response
    {
        return $this->render('ratingset/show.html.twig', [
            'ratingset' => $ratingset,
        ]);
    }

    #[Route('/{id}/{courseid}/edit', name: 'app_ratingset_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Ratingset $ratingset, RatingsetRepository $ratingsetRepository, Permissions $permissions): Response
    {
        $doc = $ratingset->getRating()[0]->getDoc();
        $course = $this->doctrine->getManager()->getRepository('App:Course')->findOneByCourseid($doc->getCourse());
        $header = 'Rubric Collection Ratings';
        $role = $permissions->getCourseRole($course->getId());

        $role = $permissions->getCourseRole($course->getId());
        if ($doc->getProject()) {
            $markupsets = $doc->getProject()->getMarkupsets();
        } elseif ($course->getMarkupsets()) {
            $markupsets = $course->getMarkupsets();
        }
        else {
            $markupsets = [];
        }

        $form = $this->createForm(RatingsetType::class, $ratingset);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ratingsetRepository->add($ratingset, true);

            return $this->redirectToRoute('doc_show', ['id' => $doc->getId(), 'courseid' => $course->getId(), 'target' => $doc->getId()]);
        }

        return $this->renderForm('ratingset/edit.html.twig', [
            'doc' => $doc,
            'markupsets' => $markupsets,
            'ratingset' => $ratingset,
            'course' => $course,
            'role' => $role,
            'header' => $header,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_ratingset_delete', methods: ['POST'])]
    public function delete(Request $request, Ratingset $ratingset, RatingsetRepository $ratingsetRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$ratingset->getId(), $request->request->get('_token'))) {
            $ratingsetRepository->remove($ratingset, true);
        }

        return $this->redirectToRoute('app_ratingset_index', [], Response::HTTP_SEE_OTHER);
    }
}
