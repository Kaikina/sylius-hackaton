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
use Sylius\TwigHooks\LiveComponent\HookableLiveComponentTrait;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\PostMount;

/**
 * Heart on a product tile. Single-variant products toggle directly;
 * multi-variant products open a modal holding a VariantPickerComponent.
 */
#[AsLiveComponent(name: 'evolutive_wishlist:card_toggle', template: '@EvolutiveSyliusWishlistPlugin/shop/components/card_toggle.html.twig')]
final class CardToggleComponent
{
    use ComponentToolsTrait;
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
        private readonly WishlistModifier $wishlistModifier,
        private readonly VariantAvailabilityChecker $availabilityChecker,
        private readonly ChannelContextInterface $channelContext,
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
        if (null === $this->variants) {
            /** @var ChannelInterface $channel */
            $channel = $this->channelContext->getChannel();
            $this->variants = null === $this->product ? [] : $this->availabilityChecker->getWishlistableVariants($this->product, $channel);
        }

        return $this->variants;
    }

    public function isSingleVariant(): bool
    {
        return 1 === count($this->getVariants());
    }

    public function isInWishlist(): bool
    {
        foreach ($this->getVariants() as $variant) {
            if ($this->wishlistModifier->isInWishlist($variant)) {
                return true;
            }
        }

        return false;
    }

    #[LiveAction]
    public function toggle(): void
    {
        if (!$this->isSingleVariant()) {
            return;
        }

        $this->wishlistModifier->toggle($this->getVariants()[0]);
        $this->emit('evolutive_wishlist:changed');
    }

    /** Emitted up by the variant picker of this tile. */
    #[LiveListener(self::PRODUCT_CHANGED_EVENT)]
    public function refresh(#[LiveArg] mixed $productId = null): void
    {
    }
}
