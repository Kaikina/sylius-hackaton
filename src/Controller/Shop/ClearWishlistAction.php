<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Controller\Shop;

use Evolutive\SyliusWishlistPlugin\Modifier\WishlistModifierInterface;
use Evolutive\SyliusWishlistPlugin\Resolver\WishlistItemsSelectionResolver;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/** Empties the wishlist but keeps it. */
#[AsController]
final class ClearWishlistAction
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

        if (null !== $this->selectionResolver->resolve($request, $wishlist, requireItems: false)) {
            $this->wishlistModifier->clear($wishlist);
        }

        return new RedirectResponse($this->urlGenerator->generate('evolutive_sylius_wishlist_shop_wishlist_show', ['id' => $id]));
    }
}
