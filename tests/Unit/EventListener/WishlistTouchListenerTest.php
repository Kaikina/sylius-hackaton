<?php

declare(strict_types=1);

namespace Tests\Evolutive\SyliusWishlistPlugin\Unit\EventListener;

use Evolutive\SyliusWishlistPlugin\Entity\Wishlist;
use Evolutive\SyliusWishlistPlugin\Entity\WishlistItem;
use Evolutive\SyliusWishlistPlugin\EventListener\WishlistTouchListener;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Symfony\Component\Clock\MockClock;

final class WishlistTouchListenerTest extends TestCase
{
    public function test_item_changes_and_renames_touch_the_wishlist_unless_stopped(): void
    {
        $listener = new WishlistTouchListener(new MockClock('2030-01-01 10:00:00'));
        $wishlist = new Wishlist();
        $item = new WishlistItem();
        $item->setWishlist($wishlist);

        $stopped = new ResourceControllerEvent($item);
        $stopped->stop('no');
        $listener($stopped);
        self::assertNotEquals(new \DateTimeImmutable('2030-01-01 10:00:00'), $wishlist->getUpdatedAt());

        $listener(new ResourceControllerEvent($item));
        self::assertEquals(new \DateTimeImmutable('2030-01-01 10:00:00'), $wishlist->getUpdatedAt());

        $renamed = new Wishlist();
        $listener(new ResourceControllerEvent($renamed));
        self::assertEquals(new \DateTimeImmutable('2030-01-01 10:00:00'), $renamed->getUpdatedAt());
    }
}
