<?php

namespace App\Form;

use App\Entity\Album;
use App\Entity\Media;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;

class MediaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('file', FileType::class, [
                'label' => 'Image',
                'required' => false,
                
            ])
            ->add('title', TextType::class, [
                'label' => 'Titre',
            ]);

        if ($options['is_admin']) {
            $builder->add('user', EntityType::class, [
                'label' => 'Utilisateur',
                'required' => false,
                'class' => User::class,
                'choice_label' => 'name',
            ]);
        }

        // ✅ Affiché pour tous
        $builder->add('album', EntityType::class, [
            'label' => 'Album',
            'required' => true,
            'class' => Album::class,
            'choice_label' => 'name',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Media::class,
            'is_admin' => false,
        ]);
    }
}
