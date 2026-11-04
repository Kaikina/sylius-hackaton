<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Creator;

use Evolutive\SyliusWishlistPlugin\Entity\WishlistInterface;
use Evolutive\SyliusWishlistPlugin\Modifier\WishlistModifierInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/** Creates a wishlist outside the ResourceController (live components), with the same name validation as the form. */
final class WishlistCreator
{
    /** @param class-string<WishlistInterface> $wishlistClass */
    public function __construct(
        private readonly WishlistModifierInterface $wishlistModifier,
        private readonly ValidatorInterface $validator,
        #[Autowire(param: 'evolutive_sylius_wishlist.model.wishlist.class')]
        private readonly string $wishlistClass,
        #[Autowire(param: 'evolutive_sylius_wishlist.form.type.wishlist.validation_groups')]
        private readonly array $validationGroups,
    ) {
    }

    /** @return WishlistInterface|string the new wishlist, or the (translated) reason it was not created */
    public function create(string $name): WishlistInterface|string
    {
        $violations = $this->validator->validatePropertyValue($this->wishlistClass, 'name', $name, $this->validationGroups);
        if (count($violations) > 0) {
            return (string) $violations->get(0)->getMessage();
        }

        return $this->wishlistModifier->create($name) ?? 'evolutive_sylius_wishlist.flash.not_created';
    }
}
