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
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class MediaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Champs communs
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

        /** @var AlbumRepository $albumRepo */
        $albumRepo = $options['album_repository'];

        /** @var UserRepository $userRepo */
        $userRepo = $options['user_repository'];

        /** @var User|null $user */
        $user = $options['user'];

        if ($options['is_admin']) {
            // Champ utilisateur visible uniquement côté admin
            $builder->add('user', EntityType::class, [
                'label' => 'Utilisateur',
                'class' => User::class,
                'choice_label' => 'name',
                'placeholder' => 'Sélectionnez un utilisateur',
            ]);

            // Champ album vide au départ (sera rempli dynamiquement en JS ou pré-submit)
            $builder->add('album', EntityType::class, [
                'label' => 'Album',
                'class' => Album::class,
                'choice_label' => 'name',
                'choices' => [], // Vide au chargement
                'placeholder' => 'Sélectionnez un utilisateur d’abord',
            ]);

            // Écouteur qui recharge dynamiquement les albums de l'utilisateur sélectionné
            $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($userRepo) {
                $form = $event->getForm();
                $data = (array) $event->getData();

                if (!isset($data['user'])) {

                    return;
                }

                $user = $userRepo->find($data['user']);
                if (!$user) {
                    return;
                }

                $form->add('album', EntityType::class, [
                    'label' => 'Album',
                    'class' => Album::class,
                    'choice_label' => 'name',
                    'query_builder' => function (AlbumRepository $repo) use ($user) {
                        return $repo->createQueryBuilder('a')
                            ->where('a.user = :user')
                            ->setParameter('user', $user);
                    },
                ]);
            });

            // Pré-remplissage du champ album si un user est déjà défini (ex: GET avec ?user=X)
            if ($user instanceof User) {

                $builder->add('album', EntityType::class, [
                    'label' => 'Album',
                    'class' => Album::class,
                    'choice_label' => 'name',
                    'query_builder' => function (AlbumRepository $repo) use ($user) {
                        return $repo->createQueryBuilder('a')
                            ->where('a.user = :user')
                            ->setParameter('user', $user);
                    },
                ]);
            }
        } else {
            // Pour un utilisateur non admin : on affiche directement ses albums
            if (!$user instanceof User) {
                throw new \LogicException('L’utilisateur doit être fourni dans les options.');
            }

            $builder->add('album', EntityType::class, [
                'label' => 'Album',
                'class' => Album::class,
                'choice_label' => 'name',
                'query_builder' => function (AlbumRepository $repo) use ($user) {
                    return $repo->createQueryBuilder('a')
                        ->where('a.user = :user')
                        ->setParameter('user', $user);
                },
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

        $resolver->setRequired(['user', 'album_repository', 'user_repository']);
    }
}
