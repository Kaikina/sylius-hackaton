<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Twig\Component;

use Evolutive\SyliusWishlistPlugin\Checker\VariantAvailabilityChecker;
use Evolutive\SyliusWishlistPlugin\Context\WishlistContextInterface;
use Evolutive\SyliusWishlistPlugin\Creator\WishlistCreator;
use Evolutive\SyliusWishlistPlugin\Entity\WishlistInterface;
use Evolutive\SyliusWishlistPlugin\Modifier\WishlistModifierInterface;
use Sylius\Bundle\ShopBundle\Twig\Component\Product\AddToCartFormComponent;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Sylius\Component\Product\Resolver\ProductVariantResolverInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\PostMount;

/** Destination chooser: one checkbox per wishlist of the visitor, ticking adds or removes the variant there. */
#[AsLiveComponent(name: 'evolutive_wishlist:wishlist_picker', template: '@EvolutiveSyliusWishlistPlugin/shop/components/wishlist_picker.html.twig')]
final class WishlistPickerComponent
{
    use ComponentToolsTrait;
    use DefaultActionTrait;
    use ProductVariantsHydrationTrait;
    use WishlistCreationTrait;

    #[LiveProp(hydrateWith: 'hydrateProduct', dehydrateWith: 'dehydrateProduct')]
    public ?ProductInterface $product = null;

    #[LiveProp(hydrateWith: 'hydrateVariant', dehydrateWith: 'dehydrateVariant')]
    public ?ProductVariantInterface $variant = null;

    /** On the product page, follow the variant selected in the add-to-cart form. */
    #[LiveProp]
    public bool $followSelectedVariant = false;

    /**
     * @param ProductRepositoryInterface<ProductInterface> $productRepository
     * @param ProductVariantRepositoryInterface<ProductVariantInterface> $productVariantRepository
     */
    public function __construct(
        private readonly WishlistContextInterface $wishlistContext,
        private readonly WishlistModifierInterface $wishlistModifier,
        private readonly WishlistCreator $wishlistCreator,
        private readonly VariantAvailabilityChecker $availabilityChecker,
        private readonly ProductVariantResolverInterface $productVariantResolver,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ProductVariantRepositoryInterface $productVariantRepository,
    ) {
    }

    /** On the product page, start from the variant the add-to-cart form preselects. */
    #[PostMount]
    public function postMount(): void
    {
        if (null === $this->variant && null !== $this->product && $this->followSelectedVariant) {
            $variant = $this->productVariantResolver->getVariant($this->product);
            $this->variant = $variant instanceof ProductVariantInterface ? $variant : null;
        }
    }

    /** @return list<array{wishlist: WishlistInterface, contains: bool}> */
    public function getRows(): array
    {
        $rows = [];
        foreach ($this->wishlistContext->getWishlists() as $wishlist) {
            $rows[] = [
                'wishlist' => $wishlist,
                'contains' => null !== $this->variant && $wishlist->hasVariant($this->variant),
            ];
        }

        return $rows;
    }

    public function isWishlistable(): bool
    {
        return null !== $this->variant &&
            $this->availabilityChecker->isWishlistable($this->variant, $this->wishlistContext->getChannel());
    }

    #[LiveAction]
    public function toggle(#[LiveArg] int $wishlistId): void
    {
        $wishlist = $this->wishlistContext->getWishlist($wishlistId);
        if (null === $wishlist || null === $this->variant || !$this->isWishlistable()) {
            return;
        }

        if ($wishlist->hasVariant($this->variant)) {
            $this->wishlistModifier->removeVariant($wishlist, $this->variant);
        } else {
            $this->wishlistModifier->addVariant($wishlist, $this->variant);
        }

        $this->emit('evolutive_wishlist:changed');
        $this->emitUp(CardToggleComponent::PRODUCT_CHANGED_EVENT);
    }

    /** A shopper creating their very first wishlist from here obviously wants the product in it. */
    protected function onWishlistCreated(WishlistInterface $wishlist, bool $isFirst): void
    {
        if (!$isFirst || null === $this->variant || !$this->isWishlistable()) {
            return;
        }

        if ($this->wishlistModifier->addVariant($wishlist, $this->variant)) {
            $this->emit('evolutive_wishlist:changed');
            $this->emitUp(CardToggleComponent::PRODUCT_CHANGED_EVENT);
        }
    }

    #[LiveListener(AddToCartFormComponent::SYLIUS_SHOP_VARIANT_CHANGED)]
    public function onVariantChanged(#[LiveArg] mixed $variantId = null): void
    {
        if (!$this->followSelectedVariant) {
            return;
        }

        $this->variant = null === $this->product || !is_numeric($variantId)
            ? null
            : $this->findProductVariant($this->product, (int) $variantId);
    }

    #[LiveListener(self::LIST_CHANGED_EVENT)]
    public function refresh(): void
    {
    }
}
