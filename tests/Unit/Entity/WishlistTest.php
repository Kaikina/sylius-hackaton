<?php

declare(strict_types=1);

namespace Tests\Evolutive\SyliusWishlistPlugin\Unit\Entity;

use Evolutive\SyliusWishlistPlugin\Entity\Wishlist;
use Evolutive\SyliusWishlistPlugin\Entity\WishlistItem;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

final class WishlistTest extends TestCase
{
    public function test_a_variant_is_added_only_once(): void
    {
        $wishlist = new Wishlist();
        $variant = $this->createMock(ProductVariantInterface::class);

        $first = self::item($variant);
        $wishlist->addItem($first);
        $wishlist->addItem(self::item($variant));

        self::assertSame(1, $wishlist->countItems());
        self::assertSame($wishlist, $first->getWishlist());
        self::assertSame($first, $wishlist->getItemForVariant($variant));
    }

    public function test_removing_an_item_detaches_it(): void
    {
        $wishlist = new Wishlist();
        $variant = $this->createMock(ProductVariantInterface::class);
        $item = self::item($variant);
        $wishlist->addItem($item);

        $wishlist->removeItem($item);

        self::assertTrue($wishlist->isEmpty());
        self::assertFalse($wishlist->hasVariant($variant));
        self::assertNull($item->getWishlist());
    }

    public function test_the_name_is_trimmed(): void
    {
        $wishlist = new Wishlist();
        $wishlist->setName('  Birthday ');

        self::assertSame('Birthday', $wishlist->getName());
    }

    public function test_an_owner_is_either_a_customer_or_a_guest_token(): void
    {
        $wishlist = new Wishlist();
        $wishlist->assignToGuest('abc');
        self::assertTrue($wishlist->isGuest());
        self::assertSame('abc', $wishlist->getToken());

        $customer = $this->createMock(CustomerInterface::class);
        $wishlist->assignToCustomer($customer);
        self::assertFalse($wishlist->isGuest());
        self::assertSame($customer, $wishlist->getCustomer());
        self::assertNull($wishlist->getToken());
    }

    public function test_touch_moves_updated_at(): void
    {
        $wishlist = new Wishlist();
        $now = new \DateTimeImmutable('2030-01-01 10:00:00');

        $wishlist->touch($now);

        self::assertSame($now, $wishlist->getUpdatedAt());
    }

    private static function item(ProductVariantInterface $variant): WishlistItem
    {
        $item = new WishlistItem();
        $item->setVariant($variant);

        return $item;
    }
}
