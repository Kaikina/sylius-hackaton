<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Twig\Component;

use Evolutive\SyliusWishlistPlugin\Checker\VariantAvailabilityChecker;
use Evolutive\SyliusWishlistPlugin\Modifier\WishlistModifier;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/** Per-variant hearts shown in the modal of a multi-variant product tile. */
#[AsLiveComponent(name: 'evolutive_wishlist:variant_picker', template: '@EvolutiveSyliusWishlistPlugin/shop/components/variant_picker.html.twig')]
final class VariantPickerComponent
{
    use ComponentToolsTrait;
    use DefaultActionTrait;
    use ProductVariantsHydrationTrait;

    #[LiveProp(hydrateWith: 'hydrateProduct', dehydrateWith: 'dehydrateProduct')]
    public ?ProductInterface $product = null;

    /**
     * @param ProductRepositoryInterface<ProductInterface> $productRepository
     * @param ProductVariantRepositoryInterface<ProductVariantInterface> $productVariantRepository
     */
    public function __construct(
        private readonly WishlistModifier $wishlistModifier,
        private readonly VariantAvailabilityChecker $availabilityChecker,
        private readonly ChannelContextInterface $channelContext,
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

        /** @var ChannelInterface $channel */
        $channel = $this->channelContext->getChannel();

        $rows = [];
        foreach ($this->availabilityChecker->getWishlistableVariants($this->product, $channel) as $variant) {
            $rows[] = [
                'variant' => $variant,
                'inWishlist' => $this->wishlistModifier->isInWishlist($variant),
                'inStock' => $this->availabilityChecker->isAvailableForPurchase($variant, $channel),
            ];
        }

        return $rows;
    }

    #[LiveAction]
    public function toggle(#[LiveArg] int $variantId): void
    {
        if (null === $this->product) {
            return;
        }

        /** @var ChannelInterface $channel */
        $channel = $this->channelContext->getChannel();
        $variant = $this->findProductVariant($this->product, $variantId);
        if (null === $variant || !$this->availabilityChecker->isWishlistable($variant, $channel)) {
            return;
        }

        $this->wishlistModifier->toggle($variant);
        $this->emitUp(CardToggleComponent::PRODUCT_CHANGED_EVENT, ['productId' => $this->product->getId()]);
        $this->emit('evolutive_wishlist:changed');
    }
}
