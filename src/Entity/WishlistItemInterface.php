<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Entity;

use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Resource\Model\ResourceInterface;

interface WishlistItemInterface extends ResourceInterface
{
    public function getWishlist(): ?WishlistInterface;

    public function setWishlist(?WishlistInterface $wishlist): void;

    public function getVariant(): ?ProductVariantInterface;

    public function setVariant(ProductVariantInterface $variant): void;

    public function getProduct(): ?ProductInterface;

    public function getCreatedAt(): \DateTimeImmutable;
}
