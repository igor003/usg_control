<?php

namespace App\Form;

use App\Entity\Organs;
use App\Repository\OrgansRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrganControlsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('organ', EntityType::class, [
                'label' => 'Organ',
                'class' => Organs::class,
                'choice_label' => 'name',
                'placeholder' => 'Alegeți organul',
                'query_builder' => static fn (OrgansRepository $repository) => $repository
                    ->createQueryBuilder('o')
                    ->orderBy('o.sort_order', 'ASC')
                    ->addOrderBy('o.name', 'ASC'),
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Salvează legătura',
                'attr' => [
                    'class' => 'btn btn-primary',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
