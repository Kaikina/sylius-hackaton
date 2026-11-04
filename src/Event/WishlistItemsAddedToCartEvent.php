<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Event;

use Evolutive\SyliusWishlistPlugin\Cart\CartAdditionResult;
use Evolutive\SyliusWishlistPlugin\Entity\WishlistInterface;
use Symfony\Contracts\EventDispatcher\Event;

/** Items stay in the wishlist after being added to the cart. */
final class WishlistItemsAddedToCartEvent extends Event
{
    public function __construct(
        public readonly WishlistInterface $wishlist,
        public readonly CartAdditionResult $result,
    ) {
    }
}
