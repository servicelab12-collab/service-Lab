<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

final class PublicLeadType extends AbstractType
{
    public const MACHINES = [
        'Lave-vaisselle vertical basse température (120 V - 20 Amp)' => 'Lave-vaisselle vertical basse température (120 V - 20 Amp)',
        'Lave-vaisselle vertical haute température (240 V - 40 Amp)' => 'Lave-vaisselle vertical haute température (240 V - 40 Amp)',
        'Lave-vaisselle sous comptoir basse température (120 V - 20 Amp)' => 'Lave-vaisselle sous comptoir basse température (120 V - 20 Amp)',
        'Lave-vaisselle sous comptoir haute température (240 V - 40 Amp)' => 'Lave-vaisselle sous comptoir haute température (240 V - 40 Amp)',
        'Lave-verre rotatif (240 V - 40 Amp)' => 'Lave-verre rotatif (240 V - 40 Amp)',
        'Machine convoyeur (240 V)' => 'Machine convoyeur (240 V)',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom complet',
                'attr' => [
                    'placeholder' => 'Ex. Josée Bouchard',
                    'autocomplete' => 'name',
                ],
                'constraints' => [
                    new NotBlank(message: 'Veuillez indiquer votre nom.'),
                    new Length(max: 100),
                ],
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone',
                'attr' => [
                    'placeholder' => 'Ex. 514 555-0123',
                    'autocomplete' => 'tel',
                ],
                'constraints' => [
                    new NotBlank(message: 'Veuillez indiquer votre téléphone.'),
                    new Length(max: 30),
                    new Regex(
                        pattern: '/^[\d\s\-+().]{7,30}$/',
                        message: 'Numéro de téléphone invalide.',
                    ),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse e-mail',
                'attr' => [
                    'placeholder' => 'Ex. josee@resto.ca',
                    'autocomplete' => 'email',
                ],
                'constraints' => [
                    new NotBlank(message: 'Veuillez indiquer votre e-mail.'),
                    new Email(message: 'Adresse e-mail invalide.'),
                    new Length(max: 150),
                ],
            ])
            ->add('machineRecherchee', ChoiceType::class, [
                'label' => 'Machine recherchée',
                'placeholder' => 'Sélectionner...',
                'choices' => self::MACHINES,
                'constraints' => [
                    new NotBlank(message: 'Veuillez sélectionner une machine.'),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
        ]);
    }
}
