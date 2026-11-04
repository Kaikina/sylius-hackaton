<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Twig\Component;

use Evolutive\SyliusWishlistPlugin\Checker\VariantAvailabilityChecker;
use Evolutive\SyliusWishlistPlugin\Context\WishlistContextInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Sylius\TwigHooks\LiveComponent\HookableLiveComponentTrait;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\PostMount;

/**
 * Heart on a product tile, filled when any variant is in any wishlist. It opens a modal holding
 * the wishlist picker (single variant) or the variant picker (several variants).
 */
#[AsLiveComponent(name: 'evolutive_wishlist:card_toggle', template: '@EvolutiveSyliusWishlistPlugin/shop/components/card_toggle.html.twig')]
final class CardToggleComponent
{
    use DefaultActionTrait;
    use HookableLiveComponentTrait;
    use ProductVariantsHydrationTrait;

    public const PRODUCT_CHANGED_EVENT = 'evolutive_wishlist:product_changed';

    #[LiveProp(hydrateWith: 'hydrateProduct', dehydrateWith: 'dehydrateProduct')]
    public ?ProductInterface $product = null;

    /** Stable across re-renders so the heart keeps targeting the same modal. */
    #[LiveProp]
    public string $modalId = '';

    /** @var list<ProductVariantInterface>|null */
    private ?array $variants = null;

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

    #[PostMount]
    public function postMount(): void
    {
        if ('' === $this->modalId) {
            $this->modalId = 'evolutive-wishlist-modal-' . bin2hex(random_bytes(6));
        }
    }

    /** @return list<ProductVariantInterface> */
    public function getVariants(): array
    {
        return $this->variants ??= null === $this->product
            ? []
            : $this->availabilityChecker->getWishlistableVariants($this->product, $this->wishlistContext->getChannel());
    }

    public function isSingleVariant(): bool
    {
        return 1 === count($this->getVariants());
    }

    public function isInWishlist(): bool
    {
        foreach ($this->getVariants() as $variant) {
            if ([] !== $this->wishlistContext->getWishlistsContaining($variant)) {
                return true;
            }
        }

        return false;
    }

    /** Emitted up by the pickers inside this tile's modal. */
    #[LiveListener(self::PRODUCT_CHANGED_EVENT)]
    public function refresh(): void
    {
    }
}
