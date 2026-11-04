<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Event;

use Evolutive\SyliusWishlistPlugin\Entity\WishlistInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Symfony\Contracts\EventDispatcher\Event;

/** Variants already present in a target were left untouched there; they still leave the source. */
final class WishlistItemsMovedEvent extends Event
{
    /**
     * @param list<ProductVariantInterface> $variants
     * @param list<WishlistInterface> $targets
     */
    public function __construct(
        public readonly WishlistInterface $source,
        public readonly array $variants,
        public readonly array $targets,
    ) {
    }
}
