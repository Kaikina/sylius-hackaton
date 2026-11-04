<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Twig\Component;

use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;

/**
 * @property ProductRepositoryInterface<ProductInterface> $productRepository
 * @property ProductVariantRepositoryInterface<ProductVariantInterface> $productVariantRepository
 */
trait ProductVariantsHydrationTrait
{
    public function hydrateProduct(mixed $id): ?ProductInterface
    {
        /** @var ProductInterface|null $product */
        $product = empty($id) ? null : $this->productRepository->find($id);

        return $product;
    }

    public function dehydrateProduct(?ProductInterface $product): ?int
    {
        return $product?->getId();
    }

    public function hydrateVariant(mixed $id): ?ProductVariantInterface
    {
        /** @var ProductVariantInterface|null $variant */
        $variant = empty($id) ? null : $this->productVariantRepository->find($id);

        return $variant;
    }

    public function dehydrateVariant(?ProductVariantInterface $variant): ?int
    {
        return $variant?->getId();
    }

    /** Only accepts a variant of the component's product. */
    private function findProductVariant(ProductInterface $product, int $variantId): ?ProductVariantInterface
    {
        foreach ($product->getVariants() as $variant) {
            if ($variant instanceof ProductVariantInterface && $variant->getId() === $variantId) {
                return $variant;
            }
        }

        return null;
    }
}
