<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Form\Type;

use Evolutive\SyliusWishlistPlugin\Entity\WishlistInterface;
use Evolutive\SyliusWishlistPlugin\Entity\WishlistItemInterface;
use Evolutive\SyliusWishlistPlugin\Form\Model\WishlistItemsSelection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Bulk actions on the items of one wishlist. Choices are limited to that wishlist's items
 * and to the owner's other wishlists, so nothing else can be targeted.
 */
final class WishlistItemsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var WishlistInterface $wishlist */
        $wishlist = $options['wishlist'];

        $builder
            ->add('items', ChoiceType::class, [
                'choices' => $wishlist->getItems()->getValues(),
                'choice_value' => static fn (?WishlistItemInterface $item): string => (string) $item?->getId(),
                'choice_label' => static fn (WishlistItemInterface $item): string => (string) $item->getVariant()?->getDescriptor(),
                'choice_translation_domain' => false,
                'multiple' => true,
                'expanded' => true,
                'label' => false,
            ])
            ->add('targets', ChoiceType::class, [
                'choices' => $options['targets'],
                'choice_value' => static fn (?WishlistInterface $target): string => (string) $target?->getId(),
                'choice_label' => static fn (WishlistInterface $target): string => (string) $target->getName(),
                'choice_attr' => $options['target_choice_attr'],
                'choice_translation_domain' => false,
                'multiple' => true,
                'expanded' => true,
                'label' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('wishlist')
            ->setAllowedTypes('wishlist', WishlistInterface::class)
            ->setDefaults([
                'data_class' => WishlistItemsSelection::class,
                'targets' => [],
                'target_choice_attr' => [],
                'csrf_token_id' => 'evolutive_sylius_wishlist_items',
            ])
            ->setAllowedTypes('targets', 'array')
            ->setAllowedTypes('target_choice_attr', ['array', 'callable'])
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'evolutive_sylius_wishlist_items';
    }
}
