<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Twig\Component;

use Evolutive\SyliusWishlistPlugin\Context\WishlistContextInterface;
use Evolutive\SyliusWishlistPlugin\Creator\WishlistCreator;
use Evolutive\SyliusWishlistPlugin\Entity\WishlistInterface;
use Evolutive\SyliusWishlistPlugin\Form\Model\WishlistItemsSelection;
use Evolutive\SyliusWishlistPlugin\Resolver\WishlistItemsSelectionResolver;
use Symfony\Component\Form\FormView;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Destination checkboxes of the "Move to…" modal. They render the "targets" field of the wishlist page form,
 * so the page form submits them; the component only keeps them fresh when a wishlist is created from the modal.
 */
#[AsLiveComponent(name: 'evolutive_wishlist:move_targets', template: '@EvolutiveSyliusWishlistPlugin/shop/components/move_targets.html.twig')]
final class MoveTargetsComponent
{
    use ComponentToolsTrait;
    use DefaultActionTrait;
    use WishlistCreationTrait;

    #[LiveProp]
    public int $sourceId = 0;

    /** @var list<string> */
    #[LiveProp(writable: true)]
    public array $checkedIds = [];

    public function __construct(
        private readonly WishlistContextInterface $wishlistContext,
        private readonly WishlistItemsSelectionResolver $selectionResolver,
        private readonly WishlistCreator $wishlistCreator,
    ) {
    }

    #[LiveListener(self::LIST_CHANGED_EVENT)]
    public function refresh(): void
    {
    }

    /** Destinations are ticked by the shopper, never automatically. */
    protected function onWishlistCreated(WishlistInterface $wishlist, bool $isFirst): void
    {
    }

    public function getTargetsField(): ?FormView
    {
        $source = $this->wishlistContext->getWishlist($this->sourceId);
        if (null === $source) {
            return null;
        }

        $selection = new WishlistItemsSelection();
        $selection->targets = array_values(array_filter(
            $this->selectionResolver->getTargets($source),
            fn (WishlistInterface $target): bool => in_array((string) $target->getId(), $this->checkedIds, true),
        ));

        $form = $this->selectionResolver->createForm(
            $source,
            $selection,
            static fn (): array => ['data-model' => 'norender|checkedIds'],
        );

        return $form->createView()->children['targets'];
    }
}
