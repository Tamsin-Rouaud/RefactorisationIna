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
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaController extends AbstractController
{
    // Injection du service ParameterBagInterface pour accéder au paramètre 'upload_dir'
    public function __construct(private ParameterBagInterface $params) {}

    // Route permettant d'afficher les médias en back-office (admin ou utilisateur connecté)
    #[Route('/admin/media', name: 'admin_media_index')]
    public function index(Request $request, ManagerRegistry $doctrine): Response
    {
        // Récupération du numéro de page depuis les paramètres GET, défaut : 1
        $page = $request->query->getInt('page', 1);

        // Récupération du repository Media
        /** @var \App\Repository\MediaRepository $mediaRepo */
        $mediaRepo = $doctrine->getRepository(Media::class);

        // Construction de la requête : médias dont les utilisateurs ne sont pas bloqués
        $qb = $mediaRepo->createQueryBuilder('m')
            ->join('m.user', 'u')
            ->where('u.isBlocked = false')
            ->orderBy('m.id', 'ASC')
            ->setMaxResults(25)
            ->setFirstResult(25 * ($page - 1));

        // Si l'utilisateur n'est pas admin, on restreint aux médias de l'utilisateur connecté
        if (!$this->isGranted('ROLE_ADMIN')) {
            $qb->andWhere('m.user = :user')
               ->setParameter('user', $this->getUser());
        }

        // Exécution de la requête
        $medias = $qb->getQuery()->getResult();

        // Compte total des médias visibles (utile pour la pagination)
        $count = $mediaRepo->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->join('m.user', 'u')
            ->where('u.isBlocked = false');

        if (!$this->isGranted('ROLE_ADMIN')) {
            $count->andWhere('m.user = :user')
                  ->setParameter('user', $this->getUser());
        }

        $total = $count->getQuery()->getSingleScalarResult();

        // Affichage du template avec les données paginées
        return $this->render('admin/media/index.html.twig', [
            'medias' => $medias,
            'total' => $total,
            'page' => $page
        ]);
    }

    // Route d'ajout d'un média
    #[Route('/admin/media/add', name: 'admin_media_add')]
    public function add(Request $request, ManagerRegistry $doctrine): Response
    {
        $media = new Media();

        // Si un ID utilisateur est transmis via GET, on le récupère, sinon on prend l'utilisateur connecté
        $userId = $request->query->get('user');
        $user = $userId
            ? $doctrine->getRepository(User::class)->find($userId)
            : $this->getUser();

        // Création du formulaire avec passage de paramètres personnalisés
        $form = $this->createForm(MediaType::class, $media, [
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
            'user' => $user,
            'album_repository' => $doctrine->getRepository(Album::class),
            'user_repository' => $doctrine->getRepository(User::class),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $media = $form->getData();

            // Vérification côté admin : un user doit être défini et ne doit pas être bloqué
            if ($this->isGranted('ROLE_ADMIN')) {
                assert($media instanceof \App\Entity\Media);

        $selectedUser = $media->getUser();

        if (!$selectedUser instanceof User) {
            $form->addError(new \Symfony\Component\Form\FormError('Veuillez sélectionner un utilisateur.'));
        } elseif ($selectedUser->isBlocked()) {
            $form->addError(new \Symfony\Component\Form\FormError('Impossible d’ajouter un média pour un utilisateur bloqué.'));
        }
    }
    
            if ($form->isValid()) {
                // Récupération du fichier envoyé
                $file = $form->get('file')->getData();
    assert($media instanceof \App\Entity\Media);
                if (!$file instanceof UploadedFile) {
                    $form->addError(new \Symfony\Component\Form\FormError('Une image est requise.'));
                } else {
                    // Génération du nom de fichier unique
                    $filename = md5(uniqid()) . '.' . $file->guessExtension();

                    $uploadDir = $this->params->get('upload_dir');
    assert(is_string($uploadDir));


                    // Déplacement du fichier
                    $file->move($uploadDir, $filename);
                    $media->setPath('uploads/' . $filename);
                    


                    // Sauvegarde en base
                    $em = $doctrine->getManager();
                    $em->persist($media);
                    $em->flush();

                    return $this->redirectToRoute('admin_media_index');
                }
            }
        }

        // Affichage du formulaire
        return $this->render('admin/media/add.html.twig', [
            'form' => $form->createView(),
        ]);
        
    }

    // Route de suppression d'un média
    #[Route('/admin/media/delete/{id}', name: 'admin_media_delete')]
    public function delete(int $id, ManagerRegistry $doctrine): Response
    {
        $media = $doctrine->getRepository(Media::class)->find($id);

        if (!$media) {
            throw $this->createNotFoundException('Média introuvable');
        }

        // Vérifie que seul l'admin ou le propriétaire peut supprimer
        if (!$this->isGranted('ROLE_ADMIN') && $media->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        // Suppression de l'entité en base
        $em = $doctrine->getManager();
        $em->remove($media);
        $em->flush();

        // Suppression physique du fichier (si existant)
        $path = $media->getPath();
        $uploadDir = $this->params->get('upload_dir');
        assert(is_string($uploadDir));

       

        $fullPath = $uploadDir . '/' . basename((string) $path);

        if (file_exists($fullPath) && is_file($fullPath)) {
            unlink($fullPath);
        }

        return $this->redirectToRoute('admin_media_index');
    }
}



