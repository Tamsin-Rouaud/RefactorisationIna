<?php

namespace App\Controller;

use App\Entity\Album;
use App\Entity\Media;
use App\Entity\User;
use App\Repository\AlbumRepository;
use App\Repository\MediaRepository;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
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
    $query = $userRepository->getActiveGuestsQuery(); // on va créer cette méthode juste après

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
public function guest(UserRepository $userRepository, PaginatorInterface $paginator, Request $request, int $id): Response{
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
public function portfolio(
    AlbumRepository $albumRepo,
    UserRepository $userRepo,
    MediaRepository $mediaRepo,
    PaginatorInterface $paginator,
    Request $request,
    ?int $id = null
): Response {
    $albums = $albumRepo->findAll();
    $album = $id ? $albumRepo->find($id) : null;

    if ($album === null) {
        $user = $userRepo->findOneBy(['admin' => true]);
        if (!$user) {
            throw $this->createNotFoundException("Administrateur introuvable pour afficher les médias.");
        }

        $query = $mediaRepo->findByUserQuery($user);
    } else {
        $query = $mediaRepo->findByAlbumQuery($album);
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
