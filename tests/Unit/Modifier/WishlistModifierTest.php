<?php

declare(strict_types=1);

namespace Tests\Evolutive\SyliusWishlistPlugin\Unit\Modifier;

use Evolutive\SyliusWishlistPlugin\Entity\Wishlist;
use Evolutive\SyliusWishlistPlugin\Entity\WishlistItem;
use Evolutive\SyliusWishlistPlugin\Event\WishlistClearedEvent;
use Evolutive\SyliusWishlistPlugin\Event\WishlistEvents;
use Evolutive\SyliusWishlistPlugin\Event\WishlistItemsMovedEvent;
use Evolutive\SyliusWishlistPlugin\Modifier\WishlistModifier;
use Evolutive\SyliusWishlistPlugin\Repository\WishlistRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Resource\Factory\Factory;
use Symfony\Component\EventDispatcher\EventDispatcher;

final class WishlistModifierTest extends TestCase
{
    private EventDispatcher $dispatcher;

    /** @var list<string> */
    private array $dispatched = [];

    protected function setUp(): void
    {
        $this->dispatcher = new EventDispatcher();
        foreach ([WishlistEvents::WISHLIST_PRE_CREATE, WishlistEvents::WISHLIST_POST_CREATE, WishlistEvents::ITEM_PRE_CREATE, WishlistEvents::ITEM_POST_CREATE, WishlistEvents::ITEM_PRE_DELETE, WishlistEvents::ITEM_POST_DELETE, WishlistItemsMovedEvent::class, WishlistClearedEvent::class] as $name) {
            $this->dispatcher->addListener($name, function () use ($name): void {
                $this->dispatched[] = $name;
            });
        }
    }

    public function test_creating_dispatches_resource_events_around_persistence(): void
    {
        $repository = $this->createMock(WishlistRepositoryInterface::class);
        $repository->expects(self::once())->method('add');

        $wishlist = $this->createModifier($repository)->create(' Birthday ');

        self::assertSame('Birthday', $wishlist?->getName());
        self::assertSame([WishlistEvents::WISHLIST_PRE_CREATE, WishlistEvents::WISHLIST_POST_CREATE], $this->dispatched);
    }

    public function test_a_stopped_pre_create_prevents_the_creation(): void
    {
        $this->dispatcher->addListener(WishlistEvents::WISHLIST_PRE_CREATE, static fn (ResourceControllerEvent $event) => $event->stop('no'));
        $repository = $this->createMock(WishlistRepositoryInterface::class);
        $repository->expects(self::never())->method('add');

        self::assertNull($this->createModifier($repository)->create('Birthday'));
    }

    public function test_adding_a_variant_twice_keeps_a_single_item(): void
    {
        $wishlist = new Wishlist();
        $variant = $this->createMock(ProductVariantInterface::class);
        $modifier = $this->createModifier();

        self::assertTrue($modifier->addVariant($wishlist, $variant));
        self::assertFalse($modifier->addVariant($wishlist, $variant));
        self::assertSame(1, $wishlist->countItems());
        self::assertSame([WishlistEvents::ITEM_PRE_CREATE, WishlistEvents::ITEM_POST_CREATE], $this->dispatched);
    }

    public function test_a_listener_can_refuse_an_item(): void
    {
        $this->dispatcher->addListener(WishlistEvents::ITEM_PRE_CREATE, static fn (ResourceControllerEvent $event) => $event->stop('no'));
        $wishlist = new Wishlist();

        self::assertFalse($this->createModifier()->addVariant($wishlist, $this->createMock(ProductVariantInterface::class)));
        self::assertTrue($wishlist->isEmpty());
    }

    public function test_removing_from_one_wishlist_leaves_the_others_untouched(): void
    {
        $shirt = $this->createMock(ProductVariantInterface::class);
        $birthday = self::wishlistWith($shirt);
        $christmas = self::wishlistWith($shirt);

        self::assertSame(1, $this->createModifier()->removeItems($birthday, $birthday->getItems()->toArray()));

        self::assertFalse($birthday->hasVariant($shirt));
        self::assertTrue($christmas->hasVariant($shirt));
    }

    public function test_items_of_another_wishlist_cannot_be_removed(): void
    {
        $shirt = $this->createMock(ProductVariantInterface::class);
        $birthday = self::wishlistWith($shirt);
        $christmas = self::wishlistWith($shirt);

        self::assertSame(0, $this->createModifier()->removeItems($birthday, $christmas->getItems()->toArray()));
        self::assertTrue($christmas->hasVariant($shirt));
    }

    public function test_moving_skips_variants_already_in_a_target_and_empties_the_source(): void
    {
        $glasses = $this->createMock(ProductVariantInterface::class);
        $shirt = $this->createMock(ProductVariantInterface::class);
        $birthday = self::wishlistWith($glasses, $shirt);
        $christmas = self::wishlistWith($shirt);
        $favorites = new Wishlist();

        $this->createModifier()->moveItems($birthday, $birthday->getItems()->toArray(), [$christmas, $favorites]);

        self::assertTrue($birthday->isEmpty());
        self::assertSame(2, $christmas->countItems());
        self::assertTrue($christmas->hasVariant($glasses));
        self::assertSame(2, $favorites->countItems());
        self::assertContains(WishlistItemsMovedEvent::class, $this->dispatched);
    }

    public function test_moving_without_any_other_target_keeps_the_items(): void
    {
        $shirt = $this->createMock(ProductVariantInterface::class);
        $birthday = self::wishlistWith($shirt);

        $this->createModifier()->moveItems($birthday, $birthday->getItems()->toArray(), [$birthday]);

        self::assertTrue($birthday->hasVariant($shirt));
        self::assertNotContains(WishlistItemsMovedEvent::class, $this->dispatched);
    }

    public function test_clearing_empties_the_wishlist(): void
    {
        $wishlist = self::wishlistWith($this->createMock(ProductVariantInterface::class), $this->createMock(ProductVariantInterface::class));

        $this->createModifier()->clear($wishlist);

        self::assertTrue($wishlist->isEmpty());
        self::assertSame(WishlistClearedEvent::class, end($this->dispatched));
    }

    private static function wishlistWith(ProductVariantInterface ...$variants): Wishlist
    {
        $wishlist = new Wishlist();
        foreach ($variants as $variant) {
            $item = new WishlistItem();
            $item->setVariant($variant);
            $wishlist->addItem($item);
        }

        return $wishlist;
    }

    private function createModifier(?WishlistRepositoryInterface $repository = null): WishlistModifier
    {
        return new WishlistModifier(
            $repository ?? $this->createMock(WishlistRepositoryInterface::class),
            new Factory(Wishlist::class),
            new Factory(WishlistItem::class),
            $this->dispatcher,
        );
    }
}
