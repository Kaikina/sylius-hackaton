<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Event;

/**
 * Resource lifecycle event names, the same ones Sylius' ResourceController dispatches.
 * WishlistModifier dispatches them too, so listeners react whatever the entry point
 * (shop pages, live components, login adoption).
 * Subjects are wrapped in Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent; pre_* events can be stopped.
 */
final class WishlistEvents
{
    public const WISHLIST_PRE_CREATE = 'evolutive_sylius_wishlist.wishlist.pre_create';

    public const WISHLIST_POST_CREATE = 'evolutive_sylius_wishlist.wishlist.post_create';

    public const WISHLIST_PRE_UPDATE = 'evolutive_sylius_wishlist.wishlist.pre_update';

    public const WISHLIST_POST_DELETE = 'evolutive_sylius_wishlist.wishlist.post_delete';

    public const ITEM_PRE_CREATE = 'evolutive_sylius_wishlist.wishlist_item.pre_create';

    public const ITEM_POST_CREATE = 'evolutive_sylius_wishlist.wishlist_item.post_create';

    public const ITEM_PRE_DELETE = 'evolutive_sylius_wishlist.wishlist_item.pre_delete';

    public const ITEM_POST_DELETE = 'evolutive_sylius_wishlist.wishlist_item.post_delete';

    private function __construct()
    {
    }
}
