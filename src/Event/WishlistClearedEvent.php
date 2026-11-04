<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Event;

use Evolutive\SyliusWishlistPlugin\Entity\WishlistInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class WishlistClearedEvent extends Event
{
    /** @param list<ProductVariantInterface> $variants the variants the wishlist held */
    public function __construct(
        public readonly WishlistInterface $wishlist,
        public readonly array $variants,
    ) {
    }
}
