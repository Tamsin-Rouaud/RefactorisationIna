<?php

namespace App\Controller\Admin;

use App\Entity\Media;
use App\Form\MediaType;
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
        $criteria = [];

        if (!$this->isGranted('ROLE_ADMIN')) {
            $criteria['user'] = $this->getUser();
        }

        $mediaRepo = $doctrine->getRepository(Media::class);

        $medias = $mediaRepo->findBy(
            $criteria,
            ['id' => 'ASC'],
            25,
            25 * ($page - 1)
        );

        $total = count($mediaRepo->findBy($criteria));

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
            'is_admin' => $this->isGranted('ROLE_ADMIN')
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $media->getFile();
            if ($file) {
                $filename = md5(uniqid()) . '.' . $file->guessExtension();

                $uploadDir = $this->params->get('upload_dir');
                if (!is_string($uploadDir)) {
                    throw new \RuntimeException('Le paramètre "upload_dir" doit être une chaîne.');
                }

                $media->setPath('uploads/' . $filename);
                $file->move($uploadDir, $filename);
            } else {
                $media->setPath('uploads/default.jpg');
            }

            $em = $doctrine->getManager();
            $em->persist($media);
            $em->flush();

            return $this->redirectToRoute('admin_media_index');
        }

        return $this->render('admin/media/add.html.twig', [
            'form' => $form->createView()
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
        if (!is_string($uploadDir)) {
            throw new \RuntimeException('Le paramètre "upload_dir" doit être une chaîne.');
        }

        $fullPath = $uploadDir . '/' . basename($path);
        if (file_exists($fullPath) && is_file($fullPath)) {
            unlink($fullPath);
        }

        return $this->redirectToRoute('admin_media_index');
    }
}
