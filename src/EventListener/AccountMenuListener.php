<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\EventListener;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class AccountMenuListener
{
    public function addWishlistItem(MenuBuilderEvent $event): void
    {
        $event->getMenu()
            ->addChild('wishlist', ['route' => 'evolutive_sylius_wishlist_shop_wishlist_show'])
            ->setLabel('evolutive_sylius_wishlist.ui.my_wishlist')
            ->setLabelAttribute('icon', 'evolutive-wishlist:heart')
        ;
    }
}
