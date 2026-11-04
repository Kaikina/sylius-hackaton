<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\EventListener;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: 'sylius.menu.admin.main')]
final class AdminMenuListener
{
    public function __invoke(MenuBuilderEvent $event): void
    {
        $customers = $event->getMenu()->getChild('customers');
        if (null === $customers) {
            return;
        }

        $customers
            ->addChild('wishlists', ['route' => 'evolutive_sylius_wishlist_admin_wishlist_index'])
            ->setLabel('evolutive_sylius_wishlist.ui.wishlists')
        ;
    }
}
