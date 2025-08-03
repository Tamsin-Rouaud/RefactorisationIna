<?php

namespace App\Controller;

use App\Entity\Media;
use App\Form\MediaType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[Route('/media')]
class UserMediaController extends AbstractController
{
    public function __construct(private ParameterBagInterface $params) {}

    #[Route('/add', name: 'user_media_add')]
    public function add(Request $request, ManagerRegistry $doctrine): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();



        if (!$user instanceof \App\Entity\User) {
            throw new \LogicException('Utilisateur connecté invalide.');
        }

        $media = new Media();
        $media->setUser($user); // Assure que le média appartient bien à l'utilisateur connecté

        $form = $this->createForm(MediaType::class, $media, [
            'is_admin' => false,
            'user' => $user,
            'album_repository' => $doctrine->getRepository(\App\Entity\Album::class),
            'user_repository' => $doctrine->getRepository(\App\Entity\User::class),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) 
        {
            $file = $media->getFile();
            if ($file) {
                $filename = md5(uniqid()) . '.' . $file->guessExtension();
                $uploadDir = $this->params->get('upload_dir');
if (!is_string($uploadDir)) {
    throw new \RuntimeException('Le paramètre "upload_dir" doit être une chaîne de caractères.');
}


                $file->move($uploadDir, $filename);
                $media->setPath('uploads/' . $filename);
            } else {
                $media->setPath('uploads/default.jpg');
            }

            // On relie automatiquement le média à l’utilisateur connecté
            if (!$this->isGranted('ROLE_ADMIN')) {
                $media->setUser($user);

            }

            $em = $doctrine->getManager();
            $em->persist($media);
            $em->flush();

            return $this->redirectToRoute('home');
        }

        return $this->render('front/addMediaUser.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
