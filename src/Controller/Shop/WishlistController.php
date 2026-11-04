<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Controller\Shop;

use Evolutive\SyliusWishlistPlugin\Cart\WishlistCartAdder;
use Evolutive\SyliusWishlistPlugin\Checker\VariantAvailabilityChecker;
use Evolutive\SyliusWishlistPlugin\Context\WishlistContext;
use Evolutive\SyliusWishlistPlugin\Entity\Wishlist;
use Evolutive\SyliusWishlistPlugin\Modifier\WishlistModifier;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

final class WishlistController extends AbstractController
{
    public const CSRF_TOKEN_ID = 'evolutive_wishlist_update';

    public function __construct(
        private readonly WishlistContext $wishlistContext,
        private readonly WishlistModifier $wishlistModifier,
        private readonly WishlistCartAdder $cartAdder,
        private readonly VariantAvailabilityChecker $availabilityChecker,
        private readonly ChannelContextInterface $channelContext,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route('/wishlist', name: 'evolutive_sylius_wishlist_shop_wishlist_show', methods: ['GET'])]
    public function show(): Response
    {
        /** @var ChannelInterface $channel */
        $channel = $this->channelContext->getChannel();
        $wishlist = $this->wishlistContext->getWishlist();

        $rows = [];
        foreach ($wishlist?->getItems() ?? [] as $item) {
            $rows[] = [
                'item' => $item,
                'variant' => $item->getVariant(),
                'product' => $item->getProduct(),
                'available' => $this->availabilityChecker->isAvailableForPurchase($item->getVariant(), $channel),
            ];
        }

        return $this->render('@EvolutiveSyliusWishlistPlugin/shop/wishlist/show.html.twig', [
            'wishlist' => $wishlist,
            'rows' => $rows,
            'csrf_token_id' => self::CSRF_TOKEN_ID,
        ]);
    }

    /**
     * One form drives every action; "action" is one of add_selected, remove_selected, clear, add:<variantId>, remove:<variantId>.
     */
    #[Route('/wishlist', name: 'evolutive_sylius_wishlist_shop_wishlist_update', methods: ['POST'])]
    public function update(Request $request): RedirectResponse
    {
        $redirect = $this->redirectToRoute('evolutive_sylius_wishlist_shop_wishlist_show');

        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_ID, (string) $request->request->get('_token'))) {
            $this->addFlash('error', $this->trans('invalid_csrf'));

            return $redirect;
        }

        $wishlist = $this->wishlistContext->getWishlist();
        if (null === $wishlist) {
            return $redirect;
        }

        $action = (string) $request->request->get('action');
        [$verb, $variantId] = array_pad(explode(':', $action, 2), 2, null);

        $variantIds = null !== $variantId
            ? [(int) $variantId]
            : array_map(static fn (mixed $id): int => is_numeric($id) ? (int) $id : 0, $request->request->all('variants'));

        match ($verb) {
            'clear' => $this->clear(),
            'add', 'add_selected' => $this->addToCart($this->resolveVariants($wishlist, $variantIds)),
            'remove', 'remove_selected' => $this->remove($this->resolveVariants($wishlist, $variantIds)),
            default => null,
        };

        return $redirect;
    }

    private function clear(): void
    {
        $this->wishlistModifier->clear();
        $this->addFlash('success', $this->trans('cleared'));
    }

    /** @param list<ProductVariantInterface> $variants */
    private function addToCart(array $variants): void
    {
        if ([] === $variants) {
            $this->addFlash('info', $this->trans('nothing_selected'));

            return;
        }

        $result = $this->cartAdder->add($variants);

        if ([] !== $result->added) {
            $this->addFlash('success', $this->trans('added_to_cart', ['%count%' => count($result->added)]));
        }

        if ([] !== $result->rejected) {
            $this->addFlash('warning', $this->trans('not_added_to_cart', [
                '%count%' => count($result->rejected),
                '%products%' => implode(', ', array_map(self::describe(...), $result->rejected)),
            ]));
        }
    }

    /** @param list<ProductVariantInterface> $variants */
    private function remove(array $variants): void
    {
        if ([] === $variants) {
            $this->addFlash('info', $this->trans('nothing_selected'));

            return;
        }

        $removed = $this->wishlistModifier->remove($variants);
        $this->addFlash('success', $this->trans('removed', ['%count%' => $removed]));
    }

    /**
     * Only variants already in the visitor's wishlist can be acted upon.
     *
     * @param list<int> $variantIds
     *
     * @return list<ProductVariantInterface>
     */
    private function resolveVariants(Wishlist $wishlist, array $variantIds): array
    {
        $variants = [];
        foreach ($wishlist->getItems() as $item) {
            if (in_array($item->getVariant()->getId(), $variantIds, true)) {
                $variants[] = $item->getVariant();
            }
        }

        return $variants;
    }

    private static function describe(ProductVariantInterface $variant): string
    {
        $productName = (string) $variant->getProduct()?->getName();
        $variantName = $variant->getName();

        return null !== $variantName && '' !== $variantName && $variantName !== $productName
            ? sprintf('%s (%s)', $productName, $variantName)
            : $productName;
    }

    /** @param array<string, mixed> $parameters */
    private function trans(string $key, array $parameters = []): string
    {
        return $this->translator->trans('evolutive_sylius_wishlist.flash.' . $key, $parameters);
    }
}
