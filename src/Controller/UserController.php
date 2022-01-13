<?php

namespace App\Controller;

use App\Entity\Episode;
use App\Entity\Genre;
use App\Entity\Series;
use App\Form\SearchSerieFormType;
use App\Repository\SeriesRepository;
use App\Search\Search;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/user")
 */
class UserController extends AbstractController
{
    /**
     * @Route("/account", name="user_account")
     */
    public function account(): Response
    {
        // Verify that a user is loged in and not an admin
        if ($this->getUser() == null) {
            return $this->redirectToRoute('user_login');
        }
        if ($this->getUser()->getAdmin()) {
            return $this->redirectToRoute('home');
        }

        return $this->render('user/account.html.twig');
    }

    /**
     * @Route("/my-series", name="user_series", methods={"GET"})
     */
    public function user_series(SeriesRepository $repository, Request $request, EntityManagerInterface $entityManager): Response 
    {
        // Verify user connection
        if (!$this->getUser()) {
            return $this->redirectToRoute('user_login');
        }
        if ($this->getUser()->getAdmin()) {
            return $this->redirectToRoute('home');
        }

        $search = new Search();
        $search->page = $request->get('page', 1);
        $search->followed = true;
        if (isset($_GET['category'])) {
            $repo = $entityManager->getRepository(Genre::class);
            $c = $repo->createQueryBuilder('g')
                ->where('g.name = :name')
                ->setParameter('name', $_GET['category'])->getQuery()->getResult();

            array_push($search->categories, $c[0]);
        }

        $form = $this->createForm(SearchSerieFormType::class, $search);
        $form->handleRequest($request);

        $serie = $repository->getSeriesUserConnected($search, $this->getUser());

        return $this->render('user/user_series.html.twig', [
            'serie' => $serie,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/follow-serie/{id}", name="follow")
     */
    public function follow(Series $serie, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Verify user connection
        if (!$this->getUser()) {
            return $this->redirectToRoute('user_login');
        }
        if ($this->getUser()->getAdmin()) {
            return $this->redirectToRoute('home');
        }

        $this->getUser()->addSeries($serie);
        $entityManager->persist($this->getUser()); // save changes locally
        $entityManager->flush();    // Update the changes made in the databse
        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * @Route("/unfollow-serie/{id}", name="unfollow")
     */
    public function unfollow(Series $serie, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Verify user connection
        if (!$this->getUser()) {
            return $this->redirectToRoute('user_login');
        }
        if ($this->getUser()->getAdmin()) {
            return $this->redirectToRoute('home');
        }

        $this->getUser()->removeSeries($serie);
        $entityManager->persist($this->getUser()); // save changes locally
        $entityManager->flush();    // Update the changes made in the databse

        if (strpos($request->headers->get('referer'), 'my-series') !== false) {
            return $this->redirectToRoute('user_series');
        }
        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * @Route("/mark-as-seen/episode{id}", name="mark_as_seen")
     */
    public function mark_as_seen(Episode $episode, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Verify user connection
        if (!$this->getUser()) {
            return $this->redirectToRoute('user_login');
        }
        if ($this->getUser()->getAdmin()) {
            return $this->redirectToRoute('home');
        }

        $this->getUser()->addEpisode($episode);
        $entityManager->persist($this->getUser()); // save changes locally
        $entityManager->flush();    // Update the changes made in the databse
        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * @Route("/mark-as-not-seen/episode{id}", name="mark_as_not_seen")
     */
    public function mark_as_not_seen(Episode $episode, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Verify user connection
        if (!$this->getUser()) {
            return $this->redirectToRoute('user_login');
        }
        if ($this->getUser()->getAdmin()) {
            return $this->redirectToRoute('home');
        }

        $this->getUser()->removeEpisode($episode);
        $entityManager->persist($this->getUser()); // save changes locally
        $entityManager->flush();    // Update the changes made in the databse
        return $this->redirect($request->headers->get('referer'));
    }

}