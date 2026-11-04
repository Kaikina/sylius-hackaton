<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Modifier;

use Doctrine\ORM\EntityManagerInterface;
use Evolutive\SyliusWishlistPlugin\Checker\VariantAvailabilityChecker;
use Evolutive\SyliusWishlistPlugin\Context\WishlistContext;
use Psr\Clock\ClockInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

class WishlistModifier
{
    public function __construct(
        private readonly WishlistContext $wishlistContext,
        private readonly VariantAvailabilityChecker $availabilityChecker,
        private readonly ChannelContextInterface $channelContext,
        private readonly EntityManagerInterface $entityManager,
        private readonly ClockInterface $clock,
    ) {
    }

    public function isInWishlist(ProductVariantInterface $variant): bool
    {
        return $this->wishlistContext->getWishlist()?->hasVariant($variant) ?? false;
    }

    /** @return bool whether the variant is in the wishlist afterwards */
    public function toggle(ProductVariantInterface $variant): bool
    {
        if ($this->isInWishlist($variant)) {
            $this->remove([$variant]);

            return false;
        }

        $this->add($variant);

        return true;
    }

    /** @return bool false when the variant was already there */
    public function add(ProductVariantInterface $variant): bool
    {
        /** @var ChannelInterface $channel */
        $channel = $this->channelContext->getChannel();
        if (!$this->availabilityChecker->isWishlistable($variant, $channel)) {
            throw new \InvalidArgumentException(sprintf('Variant "%s" cannot be added to the wishlist.', (string) $variant->getCode()));
        }

        $wishlist = $this->wishlistContext->getOrCreateWishlist();
        $added = $wishlist->addVariant($variant, $this->clock->now());
        $this->entityManager->flush();

        return $added;
    }

    /**
     * @param iterable<ProductVariantInterface> $variants
     *
     * @return int number of removed items
     */
    public function remove(iterable $variants): int
    {
        $wishlist = $this->wishlistContext->getWishlist();
        if (null === $wishlist) {
            return 0;
        }

        $now = $this->clock->now();
        $removed = 0;
        foreach ($variants as $variant) {
            if ($wishlist->removeVariant($variant, $now)) {
                ++$removed;
            }
        }

        $this->entityManager->flush();

        return $removed;
    }

    public function clear(): void
    {
        $wishlist = $this->wishlistContext->getWishlist();
        if (null === $wishlist) {
            return;
        }

        $wishlist->clear($this->clock->now());
        $this->entityManager->flush();
    }
}
