<?php

namespace App\Controller\Admin;

use App\Entity\Album;
use App\Form\AlbumType;
use App\Repository\AlbumRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AlbumController extends AbstractController
{
    #[Route('/admin/album', name: 'admin_album_index')]
    public function index(AlbumRepository $repo): Response
    {
        // Ne pas afficher les albums d’utilisateurs bloqués
        $albums = $repo->createQueryBuilder('a')
            ->join('a.user', 'u')
            ->where('u.isBlocked = false')
            ->getQuery()
            ->getResult();

        return $this->render('admin/album/index.html.twig', ['albums' => $albums]);
    }


   #[Route('/admin/album/add', name: 'admin_album_add')]
    public function add(Request $request, ManagerRegistry $doctrine): Response
    {
        $album = new Album();

        // On précise que le champ `user` sera affiché dans le formulaire uniquement si admin
        $form = $this->createForm(AlbumType::class, $album, [
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Si ce n’est pas un admin, on force l’attribution à l’utilisateur connecté
            if (!$this->isGranted('ROLE_ADMIN')) {
                $user = $this->getUser();
                if (!$user instanceof \App\Entity\User) {
                    throw new \LogicException('Utilisateur connecté invalide.');
                }
                $album->setUser($user);
            }

            $em = $doctrine->getManager();
            $em->persist($album);
            $em->flush();

            return $this->redirectToRoute('admin_album_index');
        }

        return $this->render('admin/album/add.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/admin/albums/by-user/{id}', name: 'admin_albums_by_user')]
    public function getAlbumsByUser(int $id, AlbumRepository $repo): JsonResponse
    {
        $albums = $repo->createQueryBuilder('a')
            ->join('a.user', 'u')
            ->where('u.id = :id')
            ->andWhere('u.isBlocked = false')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult();

        $data = [];
        foreach ($albums as $album) {
            $data[] = [
                'id' => $album->getId(),
                'name' => $album->getName()
            ];
        }

        return new JsonResponse($data);
    }



    #[Route('/admin/album/update/{id}', name: 'admin_album_update')]
    public function update(Request $request, int $id, ManagerRegistry $doctrine): Response
    {
        $album = $doctrine->getRepository(Album::class)->find($id);

        if (!$album) {
            throw $this->createNotFoundException('Album introuvable.');
        }

        $form = $this->createForm(AlbumType::class, $album);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // L’album est déjà lié à son user => pas besoin de setUser()
            $doctrine->getManager()->flush();

            return $this->redirectToRoute('admin_album_index');
        }

        return $this->render('admin/album/update.html.twig', [
            'form' => $form->createView(),
        ]);
    }


   #[Route('/admin/album/delete/{id}', name: 'admin_album_delete')]
    public function delete(int $id, ManagerRegistry $doctrine): Response
    {
        $album = $doctrine->getRepository(Album::class)->find($id);

        if (!$album) {
            throw $this->createNotFoundException('Album introuvable.');
        }

        $em = $doctrine->getManager();
        $em->remove($album);
        $em->flush();

        return $this->redirectToRoute('admin_album_index');
    }

}
