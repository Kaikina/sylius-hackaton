<?php

declare(strict_types=1);

namespace Tests\Evolutive\SyliusWishlistPlugin\Unit\EventListener;

use Evolutive\SyliusWishlistPlugin\Checker\VariantAvailabilityChecker;
use Evolutive\SyliusWishlistPlugin\Entity\Wishlist;
use Evolutive\SyliusWishlistPlugin\Entity\WishlistItem;
use Evolutive\SyliusWishlistPlugin\EventListener\WishlistableVariantListener;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

final class WishlistableVariantListenerTest extends TestCase
{
    public function test_it_stops_items_whose_variant_is_not_wishlistable_in_the_wishlist_channel(): void
    {
        self::assertTrue($this->dispatch(false)->isStopped());
        self::assertFalse($this->dispatch(true)->isStopped());
    }

    private function dispatch(bool $wishlistable): ResourceControllerEvent
    {
        $channel = $this->createMock(ChannelInterface::class);
        $variant = $this->createMock(ProductVariantInterface::class);
        $checker = $this->createMock(VariantAvailabilityChecker::class);
        $checker->method('isWishlistable')->with($variant, $channel)->willReturn($wishlistable);

        $wishlist = new Wishlist();
        $wishlist->setChannel($channel);
        $item = new WishlistItem();
        $item->setVariant($variant);
        $item->setWishlist($wishlist);

        $event = new ResourceControllerEvent($item);
        (new WishlistableVariantListener($checker))($event);

        return $event;
    }
}
