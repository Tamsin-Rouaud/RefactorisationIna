<?php

namespace App\Form;

use App\Entity\Album;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AlbumType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', TextType::class, [
            'label' => 'Nom',
        ]);

        if ($options['is_admin']) {
            $builder->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'name',
                'label' => 'Destinataire',
                'placeholder' => 'Choisissez un utilisateur',
            ]);
        }
    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Album::class,
            'is_admin' => false,
        ]);
    }
}
