<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;


class GuestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
      $builder
    ->add('name', null, [
        'constraints' => [
            new Assert\NotBlank(['message' => 'Le nom est obligatoire.']),
        ],
    ])
    ->add('email', EmailType::class, [
        'constraints' => [
            new Assert\NotBlank(['message' => 'L\'email est obligatoire.']),
            new Assert\Email(['message' => 'Adresse email invalide.']),
        ],
    ])
    ->add('password', PasswordType::class, [
        'constraints' => [
            new Assert\NotBlank(['message' => 'Le mot de passe est requis.']),
            new Assert\Length(['min' => 6, 'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères.']),
        ],
    ])
    ->add('description', TextareaType::class, [
        'required' => false,
    ]);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
