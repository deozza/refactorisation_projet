<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\UserInput;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;

class PatchUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'constraints' => [
                    new Length(['min'=> 1, 'max' => 255])
                ]
            ])
            ->add('age', NumberType::class, [
                'constraints' => [
                    new GreaterThanOrEqual(User::MIN_AGE_AUTHORIZED)
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserInput::class,
        ]);
    }
}
