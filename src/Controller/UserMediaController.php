<?php

// Déclaration du namespace, indispensable pour l’autoloading PSR-4
namespace App\Controller;

// Importation des classes nécessaires
use App\Entity\Media; // L'entité Media qu'on va créer et persister
use App\Form\MediaType; // Le formulaire Symfony lié à Media
use App\Entity\Album; // On aura besoin de récupérer les albums disponibles
use App\Entity\User; // On vérifie et réutilise l’utilisateur connecté
use Doctrine\Persistence\ManagerRegistry; // Pour accéder à la BDD via Doctrine
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; // Contrôleur de base fourni par Symfony
use Symfony\Component\HttpFoundation\Request; // Représente la requête HTTP (GET ou POST)
use Symfony\Component\HttpFoundation\Response; // Représente la réponse HTTP retournée au navigateur
use Symfony\Component\Routing\Attribute\Route; // Pour déclarer les routes via attributs
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface; // Pour accéder aux paramètres configurés (comme l’upload_dir)
use Symfony\Component\HttpFoundation\File\UploadedFile; // Classe représentant un fichier uploadé

// Préfixe commun à toutes les routes du contrôleur
#[Route('/media')]
class UserMediaController extends AbstractController
{
    // Injection du service ParameterBagInterface dans le constructeur
    // Cela permet de récupérer les paramètres de configuration (comme 'upload_dir')
    public function __construct(private ParameterBagInterface $params) {}

    // Route : /media/add – Permet à un utilisateur d'ajouter un média dans un de ses albums
    #[Route('/add', name: 'user_media_add')]
    public function add(Request $request, ManagerRegistry $doctrine): Response
    {
        // Bloque l’accès à cette route si l’utilisateur n’a pas le rôle 'ROLE_USER'
        $this->denyAccessUnlessGranted('ROLE_USER');

        // Récupère l’utilisateur connecté
        $user = $this->getUser();
        

        // Création d'une nouvelle instance de Media
        $media = new Media();

        // On associe d’emblée ce média à l’utilisateur connecté (par sécurité)
        assert($user instanceof \App\Entity\User);
        $media->setUser($user);

        // Création du formulaire à partir du type MediaType
        // On passe plusieurs options au formulaire pour adapter dynamiquement son comportement
        $form = $this->createForm(MediaType::class, $media, [
            'is_admin' => false, // On précise que l’utilisateur n’est pas admin
            'user' => $user, // Pour préfiltrer les albums de l’utilisateur
            'album_repository' => $doctrine->getRepository(Album::class),
            'user_repository' => $doctrine->getRepository(User::class),
        ]);

        // Gère la soumission du formulaire (hydrate $media si soumis)
        $form->handleRequest($request);

        // Si le formulaire est soumis et valide (toutes les contraintes respectées)
        if ($form->isSubmitted() && $form->isValid()) {
            // On récupère le fichier uploadé depuis le champ 'file' du formulaire
            $file = $form->get('file')->getData();

            // On récupère la valeur du paramètre 'upload_dir' (défini dans services.yaml)
            $uploadDir = $this->params->get('upload_dir');
            assert(is_string($uploadDir)); // ✅ PHPStan est satisfait

            

            // Si un fichier est bien présent ET qu’il a été uploadé sans erreur
            if ($file instanceof UploadedFile && $file->getError() === UPLOAD_ERR_OK) {
                // Génère un nom de fichier unique pour éviter les collisions
                $filename = md5(uniqid()) . '.' . $file->guessExtension();

                // Déplace physiquement le fichier dans le dossier défini (ex: /public/uploads)
                $file->move($uploadDir, $filename);

                // Stocke le chemin du fichier dans l'entité Media
                $media->setPath('uploads/' . $filename);
            } else {
                // Si aucun fichier fourni ou erreur à l’upload, on utilise une image par défaut
                // ⚠️ À corriger : ici tu as 'public/uploads' → incohérent avec le reste du code
                $media->setPath('uploads/default.jpg');
            }

            // Sécurité supplémentaire : si l’utilisateur n’est pas admin (toujours vrai ici), on réassigne le bon user
            if (!$this->isGranted('ROLE_ADMIN')) {

                $user = $this->getUser();
            assert($user instanceof \App\Entity\User);
            $media->setUser($user);

            }

            // Sauvegarde en base de données
            $em = $doctrine->getManager(); // Récupère le gestionnaire d'entités
            $em->persist($media); // Marque l’objet pour insertion
            $em->flush(); // Exécute la requête SQL

            // Redirige vers la page d’accueil une fois l’ajout terminé
            return $this->redirectToRoute('home');
        }

        // Si le formulaire n’a pas encore été soumis ou s’il est invalide, on l’affiche
        return $this->render('front/addMediaUser.html.twig', [
            'form' => $form->createView(), // Convertit le formulaire en objet exploitable par Twig
        ]);
    }
}
