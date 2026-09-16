<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Entity\Contract;
use App\Entity\Lead;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Positive;

final class ContractCreateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('lead', EntityType::class, [
                'class' => Lead::class,
                'choices' => $options['leads'],
                'choice_label' => static fn (Lead $lead): string => sprintf(
                    '%s — %s (%s)',
                    $lead->getReference(),
                    $lead->getNom(),
                    $lead->getSource()->label(),
                ),
                'label' => 'Lead',
                'placeholder' => 'Sélectionner un lead…',
                'constraints' => [new NotNull()],
            ])
            ->add('loyerMensuel', NumberType::class, [
                'label' => 'Loyer mensuel ($)',
                'help' => 'Exemple TZANET : 180 $. Commission signature = ce montant + 2 % des achats annuels.',
                'scale' => 2,
                'html5' => true,
                'attr' => ['min' => 0, 'step' => '0.01', 'placeholder' => '180.00'],
                'constraints' => [new Positive()],
            ])
            ->add('dureeMois', IntegerType::class, [
                'label' => 'Durée (mois)',
                'constraints' => [new Positive()],
            ])
            ->add('dateSignature', DateType::class, [
                'label' => 'Date de signature',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contract::class,
            'leads' => [],
        ]);
        $resolver->setAllowedTypes('leads', 'array');
    }
}
