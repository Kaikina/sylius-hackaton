<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Twig\Component;

use Evolutive\SyliusWishlistPlugin\Checker\VariantAvailabilityChecker;
use Evolutive\SyliusWishlistPlugin\Context\WishlistContextInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/** Modal of a multi-variant product tile: pick a variant, then its destination wishlists. */
#[AsLiveComponent(name: 'evolutive_wishlist:variant_picker', template: '@EvolutiveSyliusWishlistPlugin/shop/components/variant_picker.html.twig')]
final class VariantPickerComponent
{
    use DefaultActionTrait;
    use ProductVariantsHydrationTrait;

    #[LiveProp(hydrateWith: 'hydrateProduct', dehydrateWith: 'dehydrateProduct')]
    public ?ProductInterface $product = null;

    #[LiveProp(hydrateWith: 'hydrateVariant', dehydrateWith: 'dehydrateVariant')]
    public ?ProductVariantInterface $selectedVariant = null;

    /**
     * @param ProductRepositoryInterface<ProductInterface> $productRepository
     * @param ProductVariantRepositoryInterface<ProductVariantInterface> $productVariantRepository
     */
    public function __construct(
        private readonly WishlistContextInterface $wishlistContext,
        private readonly VariantAvailabilityChecker $availabilityChecker,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ProductVariantRepositoryInterface $productVariantRepository,
    ) {
    }

    /** @return list<array{variant: ProductVariantInterface, inWishlist: bool, inStock: bool}> */
    public function getRows(): array
    {
        if (null === $this->product) {
            return [];
        }

        $channel = $this->wishlistContext->getChannel();

        $rows = [];
        foreach ($this->availabilityChecker->getWishlistableVariants($this->product, $channel) as $variant) {
            $rows[] = [
                'variant' => $variant,
                'inWishlist' => [] !== $this->wishlistContext->getWishlistsContaining($variant),
                'inStock' => $this->availabilityChecker->isAvailableForPurchase($variant, $channel),
            ];
        }

        return $rows;
    }

    #[LiveAction]
    public function select(#[LiveArg] int $variantId): void
    {
        $variant = null === $this->product ? null : $this->findProductVariant($this->product, $variantId);
        if (null !== $variant && $this->availabilityChecker->isWishlistable($variant, $this->wishlistContext->getChannel())) {
            $this->selectedVariant = $variant;
        }
    }

    #[LiveAction]
    public function back(): void
    {
        $this->selectedVariant = null;
    }

    /** Emitted up by the wishlist picker of the selected variant. */
    #[LiveListener(CardToggleComponent::PRODUCT_CHANGED_EVENT)]
    public function refresh(): void
    {
    }
}
