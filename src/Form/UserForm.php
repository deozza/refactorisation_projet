<?php

namespace App\Form;

use App\Entity\User;
use PHPUnit\Framework\Constraint\GreaterThan;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThan as ConstraintsGreaterThan;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                    new Length([
                        'min' => 0,
                        'max' => 255,
                        'minMessage' => 'nom is empty',
                        'maxMessage' => 'nom is too long'
                    ]),
                    new UniqueEntity([
                        'fields' => 'nom',
                        'message' => 'Name already exists'
                    ])
                ]
            ])
            ->add('age', NumberType::class, [
                'constraints' => [
                    new NotBlank(),
                    new ConstraintsGreaterThan([
                        'value' => 21,
                        'message' => 'Wrong age'
                    ]),
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}