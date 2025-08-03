<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\GuestType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class AdminGuestController extends AbstractController
{
    #[Route('/admin/guests', name: 'admin_guests')]
    public function index(UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Récupère uniquement les utilisateurs non-admin
        $guests = $userRepository->findBy(['admin' => false]);

        return $this->render('admin/guests/index.html.twig', [
    'guests' => $guests,
]);

    }

    #[Route('/admin/guests/{id}/toggle-block', name: 'admin_guest_toggle_block')]
    public function toggleBlock(int $id, UserRepository $userRepository, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $guest = $userRepository->find($id);

        if (!$guest || $guest->isAdmin()) {
            throw $this->createNotFoundException('Invité introuvable ou accès non autorisé.');
        }

        $guest->setIsBlocked(!$guest->isBlocked());
        $em->flush();

        return $this->redirectToRoute('admin_guests');
    }

        #[Route('/admin/guests/{id}/delete', name: 'admin_guest_delete', methods: ['POST'])]
    public function delete(int $id, UserRepository $userRepository, EntityManagerInterface $em, Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $guest = $userRepository->find($id);

        if (!$guest || $guest->isAdmin()) {
            throw $this->createNotFoundException('Invité introuvable ou protégé.');
        }

        // Protection CSRF
        $token = (string) $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete-guest-' . $guest->getId(), $token)) {
            $em->remove($guest);
            $em->flush();

            $this->addFlash('success', "L'invité a bien été supprimé.");
        }


        return $this->redirectToRoute('admin_guests');
    }



    #[Route('/admin/guests/new', name: 'admin_guest_new')]
    public function new(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $guest = new User();
        $form = $this->createForm(GuestType::class, $guest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $guest->setAdmin(false);
            $guest->setIsBlocked(false);
            $hashedPassword = $hasher->hashPassword($guest, $guest->getPassword());
            $guest->setPassword($hashedPassword);

            $em->persist($guest);
            $em->flush();

            $this->addFlash('success', 'Invité ajouté avec succès.');
            return $this->redirectToRoute('admin_guests');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
        $this->addFlash('danger', 'Le formulaire contient des erreurs.');
        }

        return $this->render('admin/guests/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

}
