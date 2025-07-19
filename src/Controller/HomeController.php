<?php

namespace App\Controller;

use App\Entity\Album;
use App\Entity\Media;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function home()
    {
        return $this->render('front/home.html.twig');
    }

    /**
     * @Route("/guests", name="guests")
     */
    public function guests(ManagerRegistry $doctrine)
    {
        $guests = $doctrine->getRepository(User::class)->findBy(['admin' => false]);
        return $this->render('front/guests.html.twig', [
            'guests' => $guests
        ]);
    }

    /**
     * @Route("/guest/{id}", name="guest")
     */
    public function guest(ManagerRegistry $doctrine, int $id)
    {
        $guest = $doctrine->getRepository(User::class)->find($id);
        return $this->render('front/guest.html.twig', [
            'guest' => $guest
        ]);
    }

    /**
     * @Route("/portfolio/{id}", name="portfolio")
     */
    public function portfolio(ManagerRegistry $doctrine, ?int $id = null)
    {
        $albumRepo = $doctrine->getRepository(Album::class);
        $userRepo = $doctrine->getRepository(User::class);
        $mediaRepo = $doctrine->getRepository(Media::class);

        $albums = $albumRepo->findAll();
        $album = $id ? $albumRepo->find($id) : null;
        $user = $userRepo->findOneByAdmin(true);

        $medias = $album
            ? $mediaRepo->findByAlbum($album)
            : $mediaRepo->findByUser($user);

        return $this->render('front/portfolio.html.twig', [
            'albums' => $albums,
            'album' => $album,
            'medias' => $medias
        ]);
    }

    /**
     * @Route("/about", name="about")
     */
    public function about()
    {
        return $this->render('front/about.html.twig');
    }
}
