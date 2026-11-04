<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Resolver;

use Evolutive\SyliusWishlistPlugin\Context\WishlistContextInterface;
use Evolutive\SyliusWishlistPlugin\Entity\WishlistInterface;
use Evolutive\SyliusWishlistPlugin\Form\Model\WishlistItemsSelection;
use Evolutive\SyliusWishlistPlugin\Form\Type\WishlistItemsType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

/** Builds the wishlist page form and turns a submission into a validated selection of the visitor's own items. */
final class WishlistItemsSelectionResolver
{
    /** Query parameter set by per-row buttons to act on that single item. */
    public const SINGLE_ITEM_PARAMETER = 'item';

    public function __construct(
        private readonly WishlistContextInterface $wishlistContext,
        private readonly FormFactoryInterface $formFactory,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /** Only the visitor's own wishlists are found; anything else is a 404. */
    public function getWishlist(mixed $id): WishlistInterface
    {
        return $this->wishlistContext->getWishlist($id) ?? throw new NotFoundHttpException('Wishlist not found.');
    }

    /**
     * @param array<string, mixed>|callable $targetChoiceAttr
     */
    public function createForm(WishlistInterface $wishlist, ?WishlistItemsSelection $data = null, array|callable $targetChoiceAttr = []): FormInterface
    {
        return $this->formFactory->create(WishlistItemsType::class, $data ?? new WishlistItemsSelection(), [
            'wishlist' => $wishlist,
            'targets' => $this->getTargets($wishlist),
            'target_choice_attr' => $targetChoiceAttr,
        ]);
    }

    /**
     * Null (with a flash explaining why) when the submission is invalid or selects nothing.
     */
    public function resolve(Request $request, WishlistInterface $wishlist, bool $requireItems = true): ?WishlistItemsSelection
    {
        $form = $this->createForm($wishlist);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->flash($request, 'error', 'invalid_form');

            return null;
        }

        $selection = $form->getData();
        \assert($selection instanceof WishlistItemsSelection);

        if ($request->query->has(self::SINGLE_ITEM_PARAMETER)) {
            $itemId = (string) $request->query->get(self::SINGLE_ITEM_PARAMETER);
            $selection->items = array_values(array_filter(
                $wishlist->getItems()->getValues(),
                static fn ($item): bool => (string) $item->getId() === $itemId,
            ));
        }

        if ($requireItems && [] === $selection->items) {
            $this->flash($request, 'info', 'nothing_selected');

            return null;
        }

        return $selection;
    }

    /** @return list<WishlistInterface> the owner's wishlists a move from $wishlist may target */
    public function getTargets(WishlistInterface $wishlist): array
    {
        return array_values(array_filter(
            $this->wishlistContext->getWishlists(),
            static fn (WishlistInterface $target): bool => $target->getId() !== $wishlist->getId(),
        ));
    }

    public function flash(Request $request, string $type, string $key): void
    {
        $session = $request->getSession();
        if ($session instanceof FlashBagAwareSessionInterface) {
            $session->getFlashBag()->add($type, $this->translator->trans('evolutive_sylius_wishlist.flash.' . $key));
        }
    }
}
