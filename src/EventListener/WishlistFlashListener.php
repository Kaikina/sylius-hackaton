<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\EventListener;

use Evolutive\SyliusWishlistPlugin\Entity\WishlistInterface;
use Evolutive\SyliusWishlistPlugin\Event\WishlistClearedEvent;
use Evolutive\SyliusWishlistPlugin\Event\WishlistItemsAddedToCartEvent;
use Evolutive\SyliusWishlistPlugin\Event\WishlistItemsMovedEvent;
use Evolutive\SyliusWishlistPlugin\Event\WishlistItemsRemovedEvent;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/** Tells the shopper the outcome of a bulk action on the wishlist page. */
final class WishlistFlashListener
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[AsEventListener]
    public function onItemsRemoved(WishlistItemsRemovedEvent $event): void
    {
        $this->add('success', 'removed', ['%count%' => count($event->variants)]);
    }

    #[AsEventListener]
    public function onCleared(WishlistClearedEvent $event): void
    {
        $this->add('success', 'cleared', ['%wishlist%' => (string) $event->wishlist->getName()]);
    }

    #[AsEventListener]
    public function onItemsMoved(WishlistItemsMovedEvent $event): void
    {
        $this->add('success', 'moved', [
            '%count%' => count($event->variants),
            '%wishlists%' => implode(', ', array_map(
                static fn (WishlistInterface $wishlist): string => (string) $wishlist->getName(),
                $event->targets,
            )),
        ]);
    }

    #[AsEventListener]
    public function onItemsAddedToCart(WishlistItemsAddedToCartEvent $event): void
    {
        $result = $event->result;

        if ([] !== $result->added) {
            $this->add('success', 'added_to_cart', ['%count%' => count($result->added)]);
        }

        if ([] !== $result->rejected) {
            $this->add('warning', 'not_added_to_cart', [
                '%count%' => count($result->rejected),
                '%products%' => implode(', ', array_map(self::describe(...), $result->rejected)),
            ]);
        }
    }

    /** @param array<string, mixed> $parameters */
    private function add(string $type, string $key, array $parameters): void
    {
        $session = $this->requestStack->getMainRequest()?->getSession();
        if (!$session instanceof FlashBagAwareSessionInterface) {
            return;
        }

        $session->getFlashBag()->add($type, $this->translator->trans('evolutive_sylius_wishlist.flash.' . $key, $parameters));
    }

    private static function describe(ProductVariantInterface $variant): string
    {
        $productName = (string) $variant->getProduct()?->getName();
        $variantName = $variant->getName();

        return null !== $variantName && '' !== $variantName && $variantName !== $productName
            ? sprintf('%s (%s)', $productName, $variantName)
            : $productName;
    }
}
