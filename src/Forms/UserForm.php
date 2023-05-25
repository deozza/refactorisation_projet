<?php

namespace App\Forms;

use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class UserForm {

    /**
     * @param FormBuilderInterface $builder
     * @return FormBuilderInterface
     */
    public static function buildUserForm(FormBuilderInterface $builder): FormBuilderInterface
    {
        return $builder
            ->add('nom', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['min' => 1, 'max' => 255])
                ]
            ])
            ->add('age', NumberType::class, [
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ]);
    }
}
