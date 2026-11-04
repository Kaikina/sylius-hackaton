<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Controller\Shop;

use Evolutive\SyliusWishlistPlugin\Cart\WishlistCartAdder;
use Evolutive\SyliusWishlistPlugin\Entity\WishlistItemInterface;
use Evolutive\SyliusWishlistPlugin\Event\WishlistItemsAddedToCartEvent;
use Evolutive\SyliusWishlistPlugin\Resolver\WishlistItemsSelectionResolver;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/** Items stay in the wishlist; the outcome is reported by WishlistFlashListener. */
#[AsController]
final class AddWishlistItemsToCartAction
{
    public function __construct(
        private readonly WishlistItemsSelectionResolver $selectionResolver,
        private readonly WishlistCartAdder $cartAdder,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(Request $request, int $id): RedirectResponse
    {
        $wishlist = $this->selectionResolver->getWishlist($id);
        $selection = $this->selectionResolver->resolve($request, $wishlist);

        if (null !== $selection) {
            $variants = array_values(array_filter(array_map(
                static fn (WishlistItemInterface $item): ?ProductVariantInterface => $item->getVariant(),
                $selection->items,
            )));

            $result = $this->cartAdder->add($variants);
            $this->eventDispatcher->dispatch(new WishlistItemsAddedToCartEvent($wishlist, $result));
        }

        return new RedirectResponse($this->urlGenerator->generate('evolutive_sylius_wishlist_shop_wishlist_show', ['id' => $id]));
    }
}
