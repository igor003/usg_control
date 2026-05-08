<?php

namespace App\Form;

use App\Entity\Parameters;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParametersType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Denumire',
                'attr' => [
                    'maxlength' => 255,
                ],
            ])
            ->add('valueType', ChoiceType::class, [
                'label' => 'Tip valoare',
                'placeholder' => 'Alegeți tipul',
                'choices' => [
                    'Text' => 'text',
                    'Select' => 'select',
                ],
            ])
            ->add('valueContent', TextareaType::class, [
                'label' => 'Conținut valori',
                'required' => false,
                'empty_data' => '',
                'help' => 'Pentru tipul Select, introduceți valorile separate prin virgulă.',
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Ex: Mic, Mediu, Mare',
                ],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Salvează parametrul',
                'attr' => [
                    'class' => 'btn btn-primary',
                ],
            ])
        ;

        $builder->get('valueContent')->addModelTransformer(new CallbackTransformer(
            static fn (?array $values): string => implode(', ', $values ?? []),
            static function (?string $values): array {
                if ($values === null || trim($values) === '') {
                    return [];
                }

                return array_values(array_filter(
                    array_map(static fn (string $value): string => trim($value), explode(',', $values)),
                    static fn (string $value): bool => $value !== ''
                ));
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Parameters::class,
        ]);
    }
}
