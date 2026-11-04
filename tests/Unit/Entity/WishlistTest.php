<?php

declare(strict_types=1);

namespace Tests\Evolutive\SyliusWishlistPlugin\Unit\Entity;

use Evolutive\SyliusWishlistPlugin\Entity\Wishlist;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

final class WishlistTest extends TestCase
{
    public function test_a_variant_is_added_only_once_and_touches_the_wishlist(): void
    {
        $wishlist = Wishlist::forGuest('token', $this->createMock(ChannelInterface::class), new \DateTimeImmutable('2026-01-01'));
        $variant = $this->createMock(ProductVariantInterface::class);

        self::assertTrue($wishlist->addVariant($variant, new \DateTimeImmutable('2026-01-02')));
        self::assertFalse($wishlist->addVariant($variant, new \DateTimeImmutable('2026-01-03')));

        self::assertSame(1, $wishlist->countItems());
        self::assertEquals(new \DateTimeImmutable('2026-01-02'), $wishlist->getUpdatedAt());
        self::assertEquals(new \DateTimeImmutable('2026-01-01'), $wishlist->getCreatedAt());
    }

    public function test_removing_and_clearing_touch_the_wishlist(): void
    {
        $wishlist = Wishlist::forGuest('token', $this->createMock(ChannelInterface::class), new \DateTimeImmutable('2026-01-01'));
        $first = $this->createMock(ProductVariantInterface::class);
        $second = $this->createMock(ProductVariantInterface::class);
        $wishlist->addVariant($first, new \DateTimeImmutable('2026-01-01'));
        $wishlist->addVariant($second, new \DateTimeImmutable('2026-01-01'));

        self::assertTrue($wishlist->removeVariant($first, new \DateTimeImmutable('2026-01-05')));
        self::assertFalse($wishlist->removeVariant($first, new \DateTimeImmutable('2026-01-06')));
        self::assertFalse($wishlist->hasVariant($first));
        self::assertTrue($wishlist->hasVariant($second));
        self::assertEquals(new \DateTimeImmutable('2026-01-05'), $wishlist->getUpdatedAt());

        $wishlist->clear(new \DateTimeImmutable('2026-01-07'));
        self::assertTrue($wishlist->isEmpty());
        self::assertEquals(new \DateTimeImmutable('2026-01-07'), $wishlist->getUpdatedAt());
    }

    public function test_adoption_turns_a_guest_wishlist_into_a_customer_one(): void
    {
        $wishlist = Wishlist::forGuest('token', $this->createMock(ChannelInterface::class), new \DateTimeImmutable('2026-01-01'));
        $customer = $this->createMock(CustomerInterface::class);

        $wishlist->assignToCustomer($customer, new \DateTimeImmutable('2026-01-02'));

        self::assertFalse($wishlist->isGuest());
        self::assertSame($customer, $wishlist->getCustomer());
        self::assertNull($wishlist->getToken());
    }
}
