<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Modifier;

use Evolutive\SyliusWishlistPlugin\Entity\WishlistInterface;
use Evolutive\SyliusWishlistPlugin\Entity\WishlistItemInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

interface WishlistModifierInterface
{
    /** Owner and channel are assigned by pre_create listeners. Null when a listener stopped the creation. */
    public function create(string $name): ?WishlistInterface;

    /** @return bool false when the variant was already there or a listener refused it */
    public function addVariant(WishlistInterface $wishlist, ProductVariantInterface $variant): bool;

    public function removeVariant(WishlistInterface $wishlist, ProductVariantInterface $variant): bool;

    /**
     * @param iterable<WishlistItemInterface> $items
     *
     * @return int number of removed items
     */
    public function removeItems(WishlistInterface $wishlist, iterable $items): int;

    public function clear(WishlistInterface $wishlist): void;

    /**
     * Copies the variants into every target (skipping those already there), then removes them from the source.
     *
     * @param iterable<WishlistItemInterface> $items
     * @param iterable<WishlistInterface> $targets
     */
    public function moveItems(WishlistInterface $source, iterable $items, iterable $targets): void;
}
