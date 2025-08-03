<?php

namespace App\Form;

use App\Entity\Album;
use App\Entity\Media;
use App\Entity\User;
use App\Repository\AlbumRepository;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class MediaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('file', FileType::class, [
    'label' => 'Image',
    'required' => false,
    'mapped' => false,
    'constraints' => [
        new File([
            'maxSize' => '2M',
            'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif'],
            'mimeTypesMessage' => 'Seules les images JPEG, PNG ou GIF sont autorisées.',
            'maxSizeMessage' => 'Le fichier ne doit pas dépasser 2 Mo.',
        ])
    ]
])
            ->add('title', TextType::class, [
                'label' => 'Titre',
            ]);

        if ($options['is_admin']) {
            $builder->add('user', EntityType::class, [
                'label' => 'Utilisateur',
                'class' => User::class,
                'choice_label' => 'name',
                'placeholder' => 'Sélectionnez un utilisateur',
            ]);

            // Initialiser le champ album vide (sera rempli dynamiquement via JS ou PRE_SUBMIT)
            $builder->add('album', ChoiceType::class, [
                'label' => 'Album',
                'choices' => [],
                'placeholder' => 'Sélectionnez un utilisateur d’abord',
            ]);

            // Lors du POST : reconstruire le champ album avec les bons choix
            $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($options) {
                $form = $event->getForm();
                $data = $event->getData();
if (!is_array($data) || !isset($data['user'])) {
    return;
}


                $userId = $data['user'];
                /** @var UserRepository $userRepo */
                $userRepo = $options['user_repository'];
                /** @var AlbumRepository $albumRepo */
                $albumRepo = $options['album_repository'];

                $user = $userRepo->find($userId);
                if (!$user) {
                    return;
                }

                $albums = $albumRepo->createQueryBuilder('a')
                    ->where('a.user = :user')
                    ->setParameter('user', $user)
                    ->getQuery()
                    ->getResult();

                $form->add('album', EntityType::class, [
                    'label' => 'Album',
                    'class' => Album::class,
                    'choice_label' => 'name',
                    'choices' => $albums,
                ]);
            });
        } else {
            // Si c'est un utilisateur invité → on affiche directement ses propres albums
            $user = $options['user'];
            if (!$user instanceof User) {
                throw new \LogicException('L’utilisateur doit être fourni dans les options.');
            }

            /** @var AlbumRepository $albumRepo */
            $albumRepo = $options['album_repository'];

            $albums = $albumRepo->createQueryBuilder('a')
                ->where('a.user = :user')
                ->setParameter('user', $user)
                ->getQuery()
                ->getResult();

            $builder->add('album', EntityType::class, [
                'label' => 'Album',
                'class' => Album::class,
                'choice_label' => 'name',
                'choices' => $albums,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Media::class,
            'is_admin' => false,
            'user' => null,
            'album_repository' => null,
            'user_repository' => null,
        ]);
    }
}
