<?php

namespace App\Form;

use App\Entity\Cities;
use App\Entity\Patients;
use App\Repository\CitiesRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PatientsType extends AbstractType
{
    public function __construct(private readonly CitiesRepository $citiesRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prenume',
                'attr' => [
                    'maxlength' => 100,
                    'autocomplete' => 'given-name',
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nume',
                'attr' => [
                    'maxlength' => 100,
                    'autocomplete' => 'family-name',
                ],
            ])
            ->add('gender', ChoiceType::class, [
                'label' => 'Sex',
                'placeholder' => 'Alegeți sexul',
                'choices' => [
                    'Feminin' => 'female',
                    'Masculin' => 'male',
                ],
            ])
            ->add('birthYear', IntegerType::class, [
                'label' => 'Anul nașterii',
                'required' => false,
                'empty_data' => null,
                'attr' => [
                    'min' => 1900,
                    'max' => 2026,
                    'placeholder' => 'ex. 1985',
                ],
            ])
            ->add('phone', TelType::class, [
                'label' => 'Telefon',
                'required' => false,
                'empty_data' => null,
                'attr' => [
                    'maxlength' => 40,
                    'autocomplete' => 'tel',
                    'placeholder' => '+373 69 000 000',
                ],
            ])
            ->add('city', HiddenType::class, [
                'required' => false,
                'invalid_message' => 'Alegeți o localitate validă.',
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresă',
                'required' => false,
                'empty_data' => null,
                'attr' => [
                    'maxlength' => 255,
                    'autocomplete' => 'street-address',
                ],
            ])
            ->add('idnp', TextType::class, [
                'label' => 'IDNP',
                'required' => false,
                'empty_data' => null,
                'attr' => [
                    'maxlength' => 13,
                    'inputmode' => 'numeric',
                    'placeholder' => '13 cifre',
                ],
            ])
            ->add('seria', TextType::class, [
                'label' => 'Seria documentului',
                'required' => false,
                'empty_data' => null,
                'attr' => [
                    'maxlength' => 255,
                ],
            ])
            ->add('beneficiary', CheckboxType::class, [
                'label' => 'Pacient cu inlesniri',
                'required' => false,
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Salvează pacientul',
                'attr' => [
                    'class' => 'btn btn-primary',
                ],
            ])
        ;

        $builder->get('city')->addModelTransformer(new CallbackTransformer(
            static fn (?Cities $city): string => $city?->getId() === null ? '' : (string) $city->getId(),
            fn (?string $cityId): ?Cities => $cityId === null || $cityId === ''
                ? null
                : $this->citiesRepository->find((int) $cityId),
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Patients::class,
        ]);
    }
}
