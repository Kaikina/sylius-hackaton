<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Form\Model;

use Evolutive\SyliusWishlistPlugin\Entity\WishlistInterface;
use Evolutive\SyliusWishlistPlugin\Entity\WishlistItemInterface;

/** What the shopper ticked on a wishlist page: items, and destination wishlists for a move. */
final class WishlistItemsSelection
{
    /** @var list<WishlistItemInterface> */
    public array $items = [];

    /** @var list<WishlistInterface> */
    public array $targets = [];
}
