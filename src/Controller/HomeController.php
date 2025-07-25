<?php

namespace App\Controller;

use App\Entity\Album;
use App\Entity\Media;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function home()
    {
        return $this->render('front/home.html.twig');
    }



#[Route('/guests', name: 'guests')]
public function guests(UserRepository $userRepository, PaginatorInterface $paginator, Request $request)
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
public function guest(
    UserRepository $userRepository,
    PaginatorInterface $paginator,
    Request $request,
    int $id
) {
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
    ManagerRegistry $doctrine,
    PaginatorInterface $paginator,
    Request $request,
    ?int $id = null
) {
    $albumRepo = $doctrine->getRepository(Album::class);
    $userRepo = $doctrine->getRepository(User::class);
    $mediaRepo = $doctrine->getRepository(Media::class);

    $albums = $albumRepo->findAll();
    $album = $id ? $albumRepo->find($id) : null;
    $user = $userRepo->findOneBy(['admin' => true]);

$query = $album
    ? $mediaRepo->findByAlbumQuery($album)
    : $mediaRepo->findByUserQuery($user);

$pagination = $paginator->paginate(
    $query,
    $request->query->getInt('page', 1),
    9
);


    return $this->render('front/portfolio.html.twig', [
        'albums' => $albums,
        'album' => $album,
        'pagination' => $pagination
    ]);
}

    #[Route('/about', name: 'about')]
    public function about()
    {
        return $this->render('front/about.html.twig');
    }
}
