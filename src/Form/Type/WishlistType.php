<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Form\Type;

use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class WishlistType extends AbstractResourceType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder->add('name', TextType::class, [
            'label' => 'evolutive_sylius_wishlist.form.wishlist.name',
            'attr' => ['maxlength' => 255, 'autocomplete' => 'off'],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'evolutive_sylius_wishlist_wishlist';
    }
}
