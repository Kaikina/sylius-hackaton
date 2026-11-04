<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Cart;

use Sylius\Component\Core\Model\ProductVariantInterface;

final class CartAdditionResult
{
    /** @var list<ProductVariantInterface> */
    public array $added = [];

    /** @var list<ProductVariantInterface> */
    public array $rejected = [];
}
