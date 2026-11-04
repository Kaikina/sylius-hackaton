<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Checker;

use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Inventory\Checker\AvailabilityCheckerInterface;

class VariantAvailabilityChecker
{
    public function __construct(private readonly AvailabilityCheckerInterface $stockAvailabilityChecker)
    {
    }

    /** Enabled, sold and priced in the channel. Stock is not considered: out-of-stock variants can be wishlisted. */
    public function isWishlistable(ProductVariantInterface $variant, ChannelInterface $channel): bool
    {
        $product = $variant->getProduct();
        if (!$product instanceof ProductInterface) {
            return false;
        }

        return $product->isEnabled() &&
            $variant->isEnabled() &&
            $product->hasChannel($channel) &&
            null !== $variant->getChannelPricingForChannel($channel)?->getPrice();
    }

    public function isAvailableForPurchase(ProductVariantInterface $variant, ChannelInterface $channel): bool
    {
        return $this->isWishlistable($variant, $channel) &&
            $this->stockAvailabilityChecker->isStockAvailable($variant);
    }

    /** @return list<ProductVariantInterface> */
    public function getWishlistableVariants(ProductInterface $product, ChannelInterface $channel): array
    {
        $variants = [];
        foreach ($product->getEnabledVariants() as $variant) {
            if ($variant instanceof ProductVariantInterface && $this->isWishlistable($variant, $channel)) {
                $variants[] = $variant;
            }
        }

        return $variants;
    }
}
