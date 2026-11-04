<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\EventListener;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: 'sylius.menu.shop.account')]
final class AccountMenuListener
{
    public function __invoke(MenuBuilderEvent $event): void
    {
        $event->getMenu()
            ->addChild('wishlists', ['route' => 'evolutive_sylius_wishlist_shop_wishlist_index'])
            ->setLabel('evolutive_sylius_wishlist.ui.my_wishlists')
            ->setLabelAttribute('icon', 'evolutive-wishlist:heart')
        ;
    }
}
