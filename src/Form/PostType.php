<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Post;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class,
            ['label' => 'Name ',
             'attr' => ['class' => 'form-control']
            ])
            ->add('description', TextType::class,
            ['label' => 'Description ',
             'attr' => ['class' => 'form-control']
            ])
            ->add('category',EntityType::class,
            [ 'class' => Category::class,
                'attr'=>['class' => 'form-control']])
            ->add('cat', TextType::class,
            ['label' => 'cat ',
             'attr' => ['class' => 'form-control']
            ])
            ->add('price', TextType::class,
            ['label' => 'Price ',
             'attr' => ['class' => 'form-control']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
        ]);
    }
}
