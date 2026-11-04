<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Twig\Extension;

use Evolutive\SyliusWishlistPlugin\Checker\VariantAvailabilityChecker;
use Evolutive\SyliusWishlistPlugin\Context\WishlistContextInterface;
use Evolutive\SyliusWishlistPlugin\Entity\WishlistInterface;
use Evolutive\SyliusWishlistPlugin\Resolver\WishlistItemsSelectionResolver;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Symfony\Component\Form\FormView;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class WishlistExtension extends AbstractExtension
{
    public function __construct(
        private readonly WishlistItemsSelectionResolver $selectionResolver,
        private readonly WishlistContextInterface $wishlistContext,
        private readonly VariantAvailabilityChecker $availabilityChecker,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('evolutive_wishlist_items_form', $this->createItemsForm(...)),
            new TwigFunction('evolutive_wishlist_is_available', $this->isAvailable(...)),
            new TwigFunction('evolutive_wishlists', $this->wishlistContext->getWishlists(...)),
        ];
    }

    public function createItemsForm(WishlistInterface $wishlist): FormView
    {
        return $this->selectionResolver->createForm($wishlist)->createView();
    }

    public function isAvailable(ProductVariantInterface $variant): bool
    {
        return $this->availabilityChecker->isAvailableForPurchase($variant, $this->wishlistContext->getChannel());
    }
}
