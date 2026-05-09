<?php

namespace App\Form;

use App\Entity\Organs;
use App\Entity\UltrasoundType;
use Symfony\Component\Form\AbstractType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class OrgansType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Denumire organ',
                'attr' => [
                    'maxlength' => 255,
                ],
            ])
            ->add('ultrasoundType', EntityType::class, [
                'class' => UltrasoundType::class,
                'choice_label' => 'name',
                'label' => 'Tip UZI',
                'required' => false,
                'placeholder' => 'Alegeți tipul UZI',
                'query_builder' => static fn (\App\Repository\UltrasoundTypeRepository $repository) => $repository
                    ->createQueryBuilder('ut')
                    ->orderBy('ut.sort_order', 'ASC')
                    ->addOrderBy('ut.name', 'ASC')
                    ->addOrderBy('ut.id', 'ASC'),
            ])
            ->add('paried', CheckboxType::class, [
                'label' => 'Organ pereche',
                'required' => false,
            ])
            ->add('sortOrder', IntegerType::class, [
                'label' => 'Nr. ord',
                'attr' => [
                    'min' => 0,
                ],
                'help' => 'Organele cu valoare mai mică vor fi afișate mai sus.',
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Imagine organ',
                'mapped' => false,
                'required' => false,
                'help' => 'Încărcați o imagine PNG, JPG sau WebP.',
                'attr' => [
                    'accept' => '.png,.jpg,.jpeg,.webp',
                ],
                'constraints' => [
                    new File(
                        maxSize: '5M',
                        maxSizeMessage: 'Imaginea nu poate depăși 5 MB.',
                    ),
                ],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Salvează organul',
                'attr' => [
                    'class' => 'btn btn-primary',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Organs::class,
        ]);
    }
}
