<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Repository;

use Evolutive\SyliusWishlistPlugin\Entity\WishlistInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;

/**
 * The owner is a Customer or, when null, a Guest Token. With neither, nothing matches.
 *
 * @template T of WishlistInterface
 * @extends RepositoryInterface<T>
 */
interface WishlistRepositoryInterface extends RepositoryInterface
{
    /** @return list<WishlistInterface> oldest first */
    public function findByOwner(ChannelInterface $channel, ?CustomerInterface $customer, ?string $token): array;

    public function findOneByIdAndOwner(mixed $id, ChannelInterface $channel, ?CustomerInterface $customer, ?string $token): ?WishlistInterface;

    /** @return list<WishlistInterface> */
    public function findGuestWishlistsByToken(string $token): array;

    public function countGuestWishlistsNotUpdatedSince(\DateTimeImmutable $threshold): int;

    /** @return int number of deleted wishlists */
    public function deleteGuestWishlistsNotUpdatedSince(\DateTimeImmutable $threshold, int $batchSize): int;
}
