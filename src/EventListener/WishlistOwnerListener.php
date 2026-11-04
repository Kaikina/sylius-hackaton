<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\EventListener;

use Evolutive\SyliusWishlistPlugin\Context\WishlistContextInterface;
use Evolutive\SyliusWishlistPlugin\Entity\WishlistInterface;
use Evolutive\SyliusWishlistPlugin\Event\WishlistEvents;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/** A new wishlist belongs to the current visitor (customer, or guest token) in the current channel. */
final class WishlistOwnerListener
{
    public function __construct(private readonly WishlistContextInterface $wishlistContext)
    {
    }

    #[AsEventListener(event: WishlistEvents::WISHLIST_PRE_CREATE)]
    public function assignOwner(ResourceControllerEvent $event): void
    {
        $wishlist = $event->getSubject();
        if (!$wishlist instanceof WishlistInterface) {
            return;
        }

        if (null === $wishlist->getChannel()) {
            $wishlist->setChannel($this->wishlistContext->getChannel());
        }

        if (null !== $wishlist->getCustomer() || null !== $wishlist->getToken()) {
            return;
        }

        $customer = $this->wishlistContext->getCustomer();
        if (null !== $customer) {
            $wishlist->assignToCustomer($customer);

            return;
        }

        $wishlist->assignToGuest($this->wishlistContext->getOrCreateGuestToken());
    }

    #[AsEventListener(event: WishlistEvents::WISHLIST_POST_CREATE)]
    #[AsEventListener(event: WishlistEvents::WISHLIST_POST_DELETE)]
    public function resetContext(): void
    {
        $this->wishlistContext->reset();
    }
}
