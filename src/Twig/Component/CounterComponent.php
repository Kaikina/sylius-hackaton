<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Twig\Component;

use Evolutive\SyliusWishlistPlugin\Context\WishlistContext;
use Sylius\TwigHooks\LiveComponent\HookableLiveComponentTrait;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/** Header link to the wishlist page with its item count. */
#[AsLiveComponent(name: 'evolutive_wishlist:counter', template: '@EvolutiveSyliusWishlistPlugin/shop/components/counter.html.twig')]
final class CounterComponent
{
    use DefaultActionTrait;
    use HookableLiveComponentTrait;

    public function __construct(private readonly WishlistContext $wishlistContext)
    {
    }

    public function getCount(): int
    {
        return $this->wishlistContext->getWishlist()?->countItems() ?? 0;
    }

    #[LiveListener('evolutive_wishlist:changed')]
    public function refresh(): void
    {
    }
}
