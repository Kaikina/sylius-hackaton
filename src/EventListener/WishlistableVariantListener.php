<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\EventListener;

use Evolutive\SyliusWishlistPlugin\Checker\VariantAvailabilityChecker;
use Evolutive\SyliusWishlistPlugin\Entity\WishlistItemInterface;
use Evolutive\SyliusWishlistPlugin\Event\WishlistEvents;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/** Refuses items whose variant is not enabled, sold and priced in the wishlist's channel. */
#[AsEventListener(event: WishlistEvents::ITEM_PRE_CREATE)]
final class WishlistableVariantListener
{
    public function __construct(private readonly VariantAvailabilityChecker $availabilityChecker)
    {
    }

    public function __invoke(ResourceControllerEvent $event): void
    {
        $item = $event->getSubject();
        if (!$item instanceof WishlistItemInterface) {
            return;
        }

        $variant = $item->getVariant();
        $channel = $item->getWishlist()?->getChannel();

        if (null === $variant || null === $channel || !$this->availabilityChecker->isWishlistable($variant, $channel)) {
            $event->stop('evolutive_sylius_wishlist.flash.not_wishlistable');
        }
    }
}
