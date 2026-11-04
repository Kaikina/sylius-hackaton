<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Twig\Component;

use Evolutive\SyliusWishlistPlugin\Context\WishlistContextInterface;
use Evolutive\SyliusWishlistPlugin\Creator\WishlistCreator;
use Evolutive\SyliusWishlistPlugin\Entity\WishlistInterface;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * Inline "create a new wishlist" field of the pickers. The list re-renders with the new wishlist;
 * what else happens is up to the component (onWishlistCreated).
 *
 * @property WishlistCreator $wishlistCreator
 * @property WishlistContextInterface $wishlistContext
 */
trait WishlistCreationTrait
{
    /** Broadcast when a wishlist is created, so every picker on the page lists it. */
    public const LIST_CHANGED_EVENT = 'evolutive_wishlist:list_changed';

    #[LiveProp(writable: true)]
    public string $newName = '';

    #[LiveProp(writable: true)]
    public bool $creating = false;

    public ?string $creationError = null;

    #[LiveAction]
    public function startCreating(): void
    {
        $this->creating = true;
    }

    #[LiveAction]
    public function cancelCreating(): void
    {
        $this->creating = false;
        $this->newName = '';
    }

    #[LiveAction]
    public function create(): void
    {
        $isFirst = [] === $this->wishlistContext->getWishlists();
        $result = $this->wishlistCreator->create($this->newName);
        if (!$result instanceof WishlistInterface) {
            $this->creationError = $result;

            return;
        }

        $this->newName = '';
        $this->creating = false;
        $this->emit(self::LIST_CHANGED_EVENT);
        $this->onWishlistCreated($result, $isFirst);
    }

    /** @param bool $isFirst whether the owner had no wishlist before this one */
    abstract protected function onWishlistCreated(WishlistInterface $wishlist, bool $isFirst): void;
}
