<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Controller\Shop;

use Evolutive\SyliusWishlistPlugin\Modifier\WishlistModifierInterface;
use Evolutive\SyliusWishlistPlugin\Resolver\WishlistItemsSelectionResolver;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/** Variants already in a destination are silently skipped there, so no duplicate is ever created. */
#[AsController]
final class MoveWishlistItemsAction
{
    public function __construct(
        private readonly WishlistItemsSelectionResolver $selectionResolver,
        private readonly WishlistModifierInterface $wishlistModifier,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(Request $request, int $id): RedirectResponse
    {
        $wishlist = $this->selectionResolver->getWishlist($id);
        $selection = $this->selectionResolver->resolve($request, $wishlist);

        if (null !== $selection) {
            if ([] === $selection->targets) {
                $this->selectionResolver->flash($request, 'info', 'no_destination_selected');
            } else {
                $this->wishlistModifier->moveItems($wishlist, $selection->items, $selection->targets);
            }
        }

        return new RedirectResponse($this->urlGenerator->generate('evolutive_sylius_wishlist_shop_wishlist_show', ['id' => $id]));
    }
}
