<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Modifier;

use Evolutive\SyliusWishlistPlugin\Entity\WishlistInterface;
use Evolutive\SyliusWishlistPlugin\Entity\WishlistItemInterface;
use Evolutive\SyliusWishlistPlugin\Event\WishlistClearedEvent;
use Evolutive\SyliusWishlistPlugin\Event\WishlistEvents;
use Evolutive\SyliusWishlistPlugin\Event\WishlistItemsMovedEvent;
use Evolutive\SyliusWishlistPlugin\Event\WishlistItemsRemovedEvent;
use Evolutive\SyliusWishlistPlugin\Repository\WishlistRepositoryInterface;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class WishlistModifier implements WishlistModifierInterface
{
    /**
     * @param WishlistRepositoryInterface<WishlistInterface> $wishlistRepository
     * @param FactoryInterface<WishlistInterface> $wishlistFactory
     * @param FactoryInterface<WishlistItemInterface> $wishlistItemFactory
     */
    public function __construct(
        private readonly WishlistRepositoryInterface $wishlistRepository,
        #[Autowire(service: 'evolutive_sylius_wishlist.factory.wishlist')]
        private readonly FactoryInterface $wishlistFactory,
        #[Autowire(service: 'evolutive_sylius_wishlist.factory.wishlist_item')]
        private readonly FactoryInterface $wishlistItemFactory,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function create(string $name): ?WishlistInterface
    {
        $wishlist = $this->wishlistFactory->createNew();
        $wishlist->setName($name);

        if ($this->dispatch(WishlistEvents::WISHLIST_PRE_CREATE, $wishlist)->isStopped()) {
            return null;
        }

        $this->wishlistRepository->add($wishlist);
        $this->dispatch(WishlistEvents::WISHLIST_POST_CREATE, $wishlist);

        return $wishlist;
    }

    public function addVariant(WishlistInterface $wishlist, ProductVariantInterface $variant): bool
    {
        if ($this->stageVariant($wishlist, $variant) instanceof WishlistItemInterface) {
            $this->wishlistRepository->add($wishlist);
            $this->dispatchPostCreate($wishlist, [$variant]);

            return true;
        }

        return false;
    }

    public function removeVariant(WishlistInterface $wishlist, ProductVariantInterface $variant): bool
    {
        $item = $wishlist->getItemForVariant($variant);

        return null !== $item && 1 === $this->doRemoveItems($wishlist, [$item]);
    }

    public function removeItems(WishlistInterface $wishlist, iterable $items): int
    {
        $items = is_array($items) ? $items : iterator_to_array($items, false);
        $variants = $this->variantsOf($wishlist, $items);
        $removed = $this->doRemoveItems($wishlist, $items);
        $this->eventDispatcher->dispatch(new WishlistItemsRemovedEvent($wishlist, $variants));

        return $removed;
    }

    public function clear(WishlistInterface $wishlist): void
    {
        $items = $wishlist->getItems()->toArray();
        $variants = $this->variantsOf($wishlist, $items);
        $this->doRemoveItems($wishlist, $items);
        $this->eventDispatcher->dispatch(new WishlistClearedEvent($wishlist, $variants));
    }

    public function moveItems(WishlistInterface $source, iterable $items, iterable $targets): void
    {
        $items = is_array($items) ? $items : iterator_to_array($items, false);
        $variants = $this->variantsOf($source, $items);

        $movedTargets = [];
        foreach ($targets as $target) {
            if ($target === $source) {
                continue;
            }

            $added = [];
            foreach ($variants as $variant) {
                if ($this->stageVariant($target, $variant) instanceof WishlistItemInterface) {
                    $added[] = $variant;
                }
            }

            $this->wishlistRepository->add($target);
            $this->dispatchPostCreate($target, $added);
            $movedTargets[] = $target;
        }

        if ([] === $movedTargets) {
            return;
        }

        $this->doRemoveItems($source, $items);
        $this->eventDispatcher->dispatch(new WishlistItemsMovedEvent($source, $variants, $movedTargets));
    }

    /** Adds a new item to the wishlist without flushing; null when already there or refused by a listener. */
    private function stageVariant(WishlistInterface $wishlist, ProductVariantInterface $variant): ?WishlistItemInterface
    {
        if ($wishlist->hasVariant($variant)) {
            return null;
        }

        $item = $this->wishlistItemFactory->createNew();
        $item->setVariant($variant);
        $item->setWishlist($wishlist);

        if ($this->dispatch(WishlistEvents::ITEM_PRE_CREATE, $item)->isStopped()) {
            $item->setWishlist(null);

            return null;
        }

        $wishlist->addItem($item);

        return $item;
    }

    /** @param list<ProductVariantInterface> $variants */
    private function dispatchPostCreate(WishlistInterface $wishlist, array $variants): void
    {
        foreach ($variants as $variant) {
            $item = $wishlist->getItemForVariant($variant);
            if (null !== $item) {
                $this->dispatch(WishlistEvents::ITEM_POST_CREATE, $item);
            }
        }
    }

    /** @param iterable<WishlistItemInterface> $items */
    private function doRemoveItems(WishlistInterface $wishlist, iterable $items): int
    {
        $removed = [];
        foreach ($items as $item) {
            if (!$wishlist->hasItem($item) || $this->dispatch(WishlistEvents::ITEM_PRE_DELETE, $item)->isStopped()) {
                continue;
            }

            $wishlist->removeItem($item);
            $removed[] = $item;
        }

        if ([] === $removed) {
            return 0;
        }

        $this->wishlistRepository->add($wishlist);
        foreach ($removed as $item) {
            $this->dispatch(WishlistEvents::ITEM_POST_DELETE, $item);
        }

        return count($removed);
    }

    /**
     * @param iterable<WishlistItemInterface> $items
     *
     * @return list<ProductVariantInterface>
     */
    private function variantsOf(WishlistInterface $wishlist, iterable $items): array
    {
        $variants = [];
        foreach ($items as $item) {
            $variant = $item->getVariant();
            if (null !== $variant && $wishlist->hasItem($item)) {
                $variants[] = $variant;
            }
        }

        return $variants;
    }

    private function dispatch(string $eventName, object $subject): ResourceControllerEvent
    {
        $event = new ResourceControllerEvent($subject);
        $this->eventDispatcher->dispatch($event, $eventName);

        return $event;
    }
}
