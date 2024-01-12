<?php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class CreateUserType extends AbstractType
    {
        public function buildForm(FormBuilderInterface $builder, array $options): void
        {
            $builder
                ->add('nom', TextType::class, [
                    'constraints'=>[
                        new NotBlank(),
                        new Length(['min'=>1, 'max'=>255])
                    ]
                ])
                ->add('age', NumberType::class, [
                    'constraints'=>[
                        new NotBlank()
                    ]
                ])
               ;
       }

   }