<?php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class UpdateUserType extends AbstractType
    {
        public function buildForm(FormBuilderInterface $builder, array $options): void
        {
            $builder
                ->add('nom', TextType::class, array(
                    'required'=>false
                ))
                ->add('age', NumberType::class, [
                    'required' => false
                ])
            ;
       }
   }