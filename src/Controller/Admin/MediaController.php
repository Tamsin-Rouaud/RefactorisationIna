<?php

namespace App\Controller\Admin;

use App\Entity\Media;
use App\Form\MediaType;
use App\Entity\Album;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class MediaController extends AbstractController
{
    public function __construct(private ParameterBagInterface $params) {}

    #[Route('/admin/media', name: 'admin_media_index')]
    public function index(Request $request, ManagerRegistry $doctrine): Response
    {
        $page = $request->query->getInt('page', 1);

        /** @var \App\Repository\MediaRepository $mediaRepo */
        $mediaRepo = $doctrine->getRepository(Media::class);


        // Filtrer les médias dont l’utilisateur est bloqué
        $qb = $mediaRepo->createQueryBuilder('m')
            ->join('m.user', 'u')
            ->where('u.isBlocked = false')
            ->orderBy('m.id', 'ASC')
            ->setMaxResults(25)
            ->setFirstResult(25 * ($page - 1));

        if (!$this->isGranted('ROLE_ADMIN')) {
            $qb->andWhere('m.user = :user')
               ->setParameter('user', $this->getUser());
        }

        $medias = $qb->getQuery()->getResult();

        // Compter aussi les visibles pour pagination
        /** @var \App\Repository\MediaRepository $mediaRepo */
        $count = $mediaRepo->createQueryBuilder('m')

            ->select('COUNT(m.id)')
            ->join('m.user', 'u')
            ->where('u.isBlocked = false');

        if (!$this->isGranted('ROLE_ADMIN')) {
            $count->andWhere('m.user = :user')
                  ->setParameter('user', $this->getUser());
        }

        $total = $count->getQuery()->getSingleScalarResult();

        return $this->render('admin/media/index.html.twig', [
            'medias' => $medias,
            'total' => $total,
            'page' => $page
        ]);
    }

    #[Route('/admin/media/add', name: 'admin_media_add')]
    public function add(Request $request, ManagerRegistry $doctrine): Response
    {
        $media = new Media();

        $form = $this->createForm(MediaType::class, $media, [
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
            'user' => $this->getUser(),
            'album_repository' => $doctrine->getRepository(Album::class),
            'user_repository' => $doctrine->getRepository(User::class),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $media->getFile();
            if ($file) {
                $filename = md5(uniqid()) . '.' . $file->guessExtension();
                $uploadDir = $this->params->get('upload_dir');
                $file->move($uploadDir, $filename);
                $media->setPath('uploads/' . $filename);
            } else {
                $media->setPath('uploads/default.jpg');
            }

            if ($this->isGranted('ROLE_ADMIN')) {
                if (!$media->getUser()) {
                    throw new \RuntimeException('Veuillez sélectionner un utilisateur.');
                }

                // Bloquer si l’admin choisit un utilisateur bloqué
                if ($media->getUser()->isBlocked()) {
                    throw new \RuntimeException('Impossible d’ajouter un média pour un utilisateur bloqué.');
                }

            } else {
                $media->setUser($this->getUser());
            }

            $em = $doctrine->getManager();
            $em->persist($media);
            $em->flush();

            return $this->redirectToRoute('admin_media_index');
        }

        return $this->render('admin/media/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/media/delete/{id}', name: 'admin_media_delete')]
    public function delete(int $id, ManagerRegistry $doctrine): Response
    {
        $media = $doctrine->getRepository(Media::class)->find($id);
        if (!$media) {
            throw $this->createNotFoundException('Média introuvable');
        }

        if (!$this->isGranted('ROLE_ADMIN') && $media->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $em = $doctrine->getManager();
        $em->remove($media);
        $em->flush();

        $path = $media->getPath();
        $uploadDir = $this->params->get('upload_dir');
        $fullPath = $uploadDir . '/' . basename($path);

        if (file_exists($fullPath) && is_file($fullPath)) {
            unlink($fullPath);
        }

        return $this->redirectToRoute('admin_media_index');
    }
}
