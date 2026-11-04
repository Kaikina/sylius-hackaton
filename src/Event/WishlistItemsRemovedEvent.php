<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Event;

use Evolutive\SyliusWishlistPlugin\Entity\WishlistInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Symfony\Contracts\EventDispatcher\Event;

/** Selected items were removed from a wishlist by the shopper. */
final class WishlistItemsRemovedEvent extends Event
{
    /** @param list<ProductVariantInterface> $variants */
    public function __construct(
        public readonly WishlistInterface $wishlist,
        public readonly array $variants,
    ) {
    }
}
