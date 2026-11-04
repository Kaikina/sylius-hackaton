<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Twig\Component;

use Evolutive\SyliusWishlistPlugin\Checker\VariantAvailabilityChecker;
use Evolutive\SyliusWishlistPlugin\Context\WishlistContextInterface;
use Sylius\Bundle\ShopBundle\Twig\Component\Product\AddToCartFormComponent;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Sylius\Component\Product\Resolver\ProductVariantResolverInterface;
use Sylius\TwigHooks\LiveComponent\HookableLiveComponentTrait;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\PostMount;

/** "Add to wishlist" on the product page: opens the wishlist picker for the variant selected in the add-to-cart form. */
#[AsLiveComponent(name: 'evolutive_wishlist:product_toggle', template: '@EvolutiveSyliusWishlistPlugin/shop/components/product_toggle.html.twig')]
final class ProductToggleComponent
{
    use DefaultActionTrait;
    use HookableLiveComponentTrait;
    use ProductVariantsHydrationTrait;

    #[LiveProp(hydrateWith: 'hydrateProduct', dehydrateWith: 'dehydrateProduct')]
    public ?ProductInterface $product = null;

    #[LiveProp(hydrateWith: 'hydrateVariant', dehydrateWith: 'dehydrateVariant')]
    public ?ProductVariantInterface $variant = null;

    /**
     * @param ProductRepositoryInterface<ProductInterface> $productRepository
     * @param ProductVariantRepositoryInterface<ProductVariantInterface> $productVariantRepository
     */
    public function __construct(
        private readonly WishlistContextInterface $wishlistContext,
        private readonly VariantAvailabilityChecker $availabilityChecker,
        private readonly ProductVariantResolverInterface $productVariantResolver,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ProductVariantRepositoryInterface $productVariantRepository,
    ) {
    }

    #[PostMount]
    public function postMount(): void
    {
        if (null === $this->variant && null !== $this->product) {
            /** @var ProductVariantInterface|null $variant */
            $variant = $this->productVariantResolver->getVariant($this->product);
            $this->variant = $variant;
        }
    }

    #[LiveListener(AddToCartFormComponent::SYLIUS_SHOP_VARIANT_CHANGED)]
    public function onVariantChanged(#[LiveArg] mixed $variantId = null): void
    {
        $this->variant = null === $this->product || !is_numeric($variantId)
            ? null
            : $this->findProductVariant($this->product, (int) $variantId);
    }

    #[LiveListener('evolutive_wishlist:changed')]
    public function refresh(): void
    {
    }

    public function isWishlistable(): bool
    {
        return null !== $this->variant &&
            $this->availabilityChecker->isWishlistable($this->variant, $this->wishlistContext->getChannel());
    }

    public function isInWishlist(): bool
    {
        return null !== $this->variant && [] !== $this->wishlistContext->getWishlistsContaining($this->variant);
    }
}
