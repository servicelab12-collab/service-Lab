<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Entity\Offer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class OfferType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('premierMoisGratuit', CheckboxType::class, [
                'label' => '1er mois gratuit pour le client (code TZANET)',
                'help' => 'Le client ne paie pas le premier mois de location.',
                'required' => false,
            ])
            ->add('commissionEqualsMonthlyRent', CheckboxType::class, [
                'label' => 'Commission signature = 1 mois de loyer (ex. 180 $)',
                'help' => 'Si coché, ignore la commission fixe et prend le loyer du contrat.',
                'required' => false,
                'attr' => [
                    'data-offer-commission-target' => 'equalsRent',
                    'data-action' => 'change->offer-commission#sync',
                ],
            ])
            ->add('commissionSignature', NumberType::class, [
                'label' => 'Commission fixe ($)',
                'help' => 'Utilisée seulement si « = 1 mois de loyer » est décoché.',
                'scale' => 2,
                'html5' => true,
                'required' => false,
                'attr' => [
                    'min' => 0,
                    'step' => '0.01',
                    'placeholder' => '100.00',
                    'data-offer-commission-target' => 'fixedAmount',
                ],
            ])
            ->add('retourAnnuel', NumberType::class, [
                'label' => 'Retour annuel sur achats (%)',
                'help' => 'Ex. 2 % des achats annuels du client (équipement + produits).',
                'scale' => 2,
                'html5' => true,
                'attr' => ['min' => 0, 'max' => 100, 'step' => '0.01', 'placeholder' => '2.00'],
            ])
            ->add('dateDebut', DateType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('dateFin', DateType::class, [
                'label' => 'Date de fin (optionnel)',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => false,
            ])
            ->add('active', CheckboxType::class, [
                'label' => 'Activer cette offre immédiatement',
                'help' => 'Si cochée, toutes les autres offres seront désactivées automatiquement.',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Offer::class,
        ]);
    }
}
