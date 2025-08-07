<?php

namespace App\Controller;

use App\Repository\AlbumRepository;
use App\Repository\MediaRepository;
use App\Repository\UserRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function home(): Response
    {
        return $this->render('front/home.html.twig');
    }


    #[Route('/guests', name: 'guests')]
    public function guests(UserRepository $userRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $query = $userRepository->getActiveGuestsQuery(); 

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1), // page actuelle
            12 // nombre d'invités par page
        );

        return $this->render('front/guests.html.twig', [
            'pagination' => $pagination
        ]);
    }

   
    #[Route('/guest/{id}', name: 'guest')]
    public function guest(UserRepository $userRepository, PaginatorInterface $paginator, Request $request, int $id): Response
    {
        $guest = $userRepository->findWithMedias($id);

        if (!$guest || $guest->isBlocked() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException('Cet invité n’est pas accessible.');
        }

        $pagination = $paginator->paginate(
            $guest->getMedias(),
            $request->query->getInt('page', 1),
            9 // par page
        );

        return $this->render('front/guest.html.twig', [
            'guest' => $guest,
            'pagination' => $pagination
        ]);
    }

    #[Route('/portfolio/{id}', name: 'portfolio')]
    public function portfolio(AlbumRepository $albumRepo, UserRepository $userRepo, MediaRepository $mediaRepo, PaginatorInterface $paginator, Request $request, ?int $id = null): Response 
    {
        $albums = $albumRepo->findAllVisible();
        $album = $id ? $albumRepo->find($id) : null;

        // Si un album est sélectionné, on vérifie si son propriétaire est bloqué
        if ($album !== null) {
            $owner = $album->getUser();
            if (!$owner || $owner->isBlocked()) {
                throw $this->createNotFoundException('Album inaccessible.');
            }

            $query = $mediaRepo->findByAlbumQuery($album);
        } else {
            // Page d’accueil du portfolio : médias de l’admin uniquement si non bloqué
            $user = $userRepo->findOneBy(['admin' => true]);
            

           $user = $this->getUser();
            assert($user instanceof \App\Entity\User);
            $query = $mediaRepo->findByUserQuery($user);

        }

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            9
        );

        return $this->render('front/portfolio.html.twig', [
            'albums' => $albums,
            'album' => $album,
            'pagination' => $pagination,
        ]);
    }


    #[Route('/about', name: 'about')]
    public function about(): Response
    {
        return $this->render('front/about.html.twig');
    }
}
