<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\EventListener;

use Evolutive\SyliusWishlistPlugin\Entity\WishlistInterface;
use Evolutive\SyliusWishlistPlugin\Entity\WishlistItemInterface;
use Evolutive\SyliusWishlistPlugin\Event\WishlistEvents;
use Psr\Clock\ClockInterface;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/** Keeps updatedAt current; the guest purge relies on it. Runs after guards (negative priority). */
final class WishlistTouchListener
{
    public function __construct(private readonly ClockInterface $clock)
    {
    }

    #[AsEventListener(event: WishlistEvents::WISHLIST_PRE_UPDATE, priority: -100)]
    #[AsEventListener(event: WishlistEvents::ITEM_PRE_CREATE, priority: -100)]
    #[AsEventListener(event: WishlistEvents::ITEM_PRE_DELETE, priority: -100)]
    public function __invoke(ResourceControllerEvent $event): void
    {
        if ($event->isStopped()) {
            return;
        }

        $subject = $event->getSubject();
        $wishlist = $subject instanceof WishlistItemInterface ? $subject->getWishlist() : $subject;

        if ($wishlist instanceof WishlistInterface) {
            $wishlist->touch($this->clock->now());
        }
    }
}
