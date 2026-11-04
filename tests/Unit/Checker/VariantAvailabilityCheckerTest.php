<?php

declare(strict_types=1);

namespace Tests\Evolutive\SyliusWishlistPlugin\Unit\Checker;

use Evolutive\SyliusWishlistPlugin\Checker\VariantAvailabilityChecker;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Inventory\Checker\AvailabilityCheckerInterface;

final class VariantAvailabilityCheckerTest extends TestCase
{
    /** @return iterable<string, array{bool, bool, bool, ?int, bool, bool, bool}> */
    public static function cases(): iterable
    {
        // productEnabled, variantEnabled, inChannel, price, inStock => wishlistable, availableForPurchase
        yield 'everything fine' => [true, true, true, 1000, true, true, true];
        yield 'out of stock can still be wishlisted' => [true, true, true, 1000, false, true, false];
        yield 'disabled product' => [false, true, true, 1000, true, false, false];
        yield 'disabled variant' => [true, false, true, 1000, true, false, false];
        yield 'not sold in channel' => [true, true, false, 1000, true, false, false];
        yield 'no price in channel' => [true, true, true, null, true, false, false];
    }

    #[DataProvider('cases')]
    public function test_availability(
        bool $productEnabled,
        bool $variantEnabled,
        bool $inChannel,
        ?int $price,
        bool $inStock,
        bool $expectedWishlistable,
        bool $expectedAvailable,
    ): void {
        $channel = $this->createMock(ChannelInterface::class);

        $product = $this->createMock(ProductInterface::class);
        $product->method('isEnabled')->willReturn($productEnabled);
        $product->method('hasChannel')->with($channel)->willReturn($inChannel);

        $pricing = null;
        if (null !== $price) {
            $pricing = $this->createMock(ChannelPricingInterface::class);
            $pricing->method('getPrice')->willReturn($price);
        }

        $variant = $this->createMock(ProductVariantInterface::class);
        $variant->method('getProduct')->willReturn($product);
        $variant->method('isEnabled')->willReturn($variantEnabled);
        $variant->method('getChannelPricingForChannel')->willReturn($pricing);

        $stockChecker = $this->createMock(AvailabilityCheckerInterface::class);
        $stockChecker->method('isStockAvailable')->willReturn($inStock);

        $checker = new VariantAvailabilityChecker($stockChecker);

        self::assertSame($expectedWishlistable, $checker->isWishlistable($variant, $channel));
        self::assertSame($expectedAvailable, $checker->isAvailableForPurchase($variant, $channel));
    }
}
