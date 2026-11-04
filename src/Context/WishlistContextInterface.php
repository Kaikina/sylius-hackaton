<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Context;

use Evolutive\SyliusWishlistPlugin\Entity\WishlistInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

/** The current Wishlist Owner (Customer or Guest Token) in the current Channel, and their wishlists. */
interface WishlistContextInterface
{
    public function getChannel(): ChannelInterface;

    public function getCustomer(): ?CustomerInterface;

    /** Null for a visitor who never created a wishlist; never creates one. */
    public function getGuestToken(): ?string;

    public function getOrCreateGuestToken(): string;

    /** @return list<WishlistInterface> */
    public function getWishlists(): array;

    public function getWishlist(mixed $id): ?WishlistInterface;

    /** @return list<WishlistInterface> */
    public function getWishlistsContaining(ProductVariantInterface $variant): array;

    /** Distinct variants across all wishlists. */
    public function countVariants(): int;

    public function reset(): void;
}
