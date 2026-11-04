<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Entity;

use Doctrine\Common\Collections\Collection;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Resource\Model\ResourceInterface;

/** Owned either by a Customer or by a Guest Token, never both. */
interface WishlistInterface extends ResourceInterface
{
    public function getName(): ?string;

    public function setName(?string $name): void;

    public function getChannel(): ?ChannelInterface;

    public function setChannel(ChannelInterface $channel): void;

    public function getCustomer(): ?CustomerInterface;

    public function getToken(): ?string;

    public function isGuest(): bool;

    public function assignToCustomer(CustomerInterface $customer): void;

    public function assignToGuest(string $token): void;

    /** @return Collection<int, WishlistItemInterface> */
    public function getItems(): Collection;

    public function countItems(): int;

    public function isEmpty(): bool;

    public function getItemForVariant(ProductVariantInterface $variant): ?WishlistItemInterface;

    public function hasVariant(ProductVariantInterface $variant): bool;

    public function hasItem(WishlistItemInterface $item): bool;

    /** Silently ignores an item whose variant is already in the wishlist. */
    public function addItem(WishlistItemInterface $item): void;

    public function removeItem(WishlistItemInterface $item): void;

    public function getCreatedAt(): \DateTimeImmutable;

    public function getUpdatedAt(): \DateTimeImmutable;

    public function touch(\DateTimeImmutable $now): void;
}
