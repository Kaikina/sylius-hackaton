<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Twig\Component;

use Evolutive\SyliusWishlistPlugin\Context\WishlistContextInterface;
use Sylius\TwigHooks\LiveComponent\HookableLiveComponentTrait;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/** Header link to the wishlists with the number of distinct products saved across them. */
#[AsLiveComponent(name: 'evolutive_wishlist:counter', template: '@EvolutiveSyliusWishlistPlugin/shop/components/counter.html.twig')]
final class CounterComponent
{
    use DefaultActionTrait;
    use HookableLiveComponentTrait;

    public function __construct(private readonly WishlistContextInterface $wishlistContext)
    {
    }

    public function getCount(): int
    {
        return $this->wishlistContext->countVariants();
    }

    #[LiveListener('evolutive_wishlist:changed')]
    public function refresh(): void
    {
    }
}
