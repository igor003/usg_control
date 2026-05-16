<?php

namespace App\Form;

use App\Entity\UltrasoundType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UltrasoundTypeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Denumire tip USG',
                'attr' => [
                    'maxlength' => 255,
                ],
            ])
            ->add('sortOrder', IntegerType::class, [
                'label' => 'Nr. ord',
                'attr' => [
                    'min' => 0,
                ],
                'help' => 'Tipurile cu valoare mai mică vor fi afișate mai sus.',
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Salvează tipul USG',
                'attr' => [
                    'class' => 'btn btn-primary',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UltrasoundType::class,
        ]);
    }
}
