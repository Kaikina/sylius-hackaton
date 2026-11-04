<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Context;

use Evolutive\SyliusWishlistPlugin\Entity\WishlistInterface;
use Evolutive\SyliusWishlistPlugin\Repository\WishlistRepositoryInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Customer\Context\CustomerContextInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Service\ResetInterface;

final class WishlistContext implements WishlistContextInterface, ResetInterface
{
    public const SESSION_TOKEN_KEY = '_evolutive_wishlist.guest_token';

    /** @var list<WishlistInterface>|null */
    private ?array $wishlists = null;

    /** @param WishlistRepositoryInterface<WishlistInterface> $wishlistRepository */
    public function __construct(
        private readonly WishlistRepositoryInterface $wishlistRepository,
        private readonly ChannelContextInterface $channelContext,
        private readonly CustomerContextInterface $customerContext,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getChannel(): ChannelInterface
    {
        /** @var ChannelInterface $channel */
        $channel = $this->channelContext->getChannel();

        return $channel;
    }

    public function getCustomer(): ?CustomerInterface
    {
        $customer = $this->customerContext->getCustomer();

        return $customer instanceof CustomerInterface ? $customer : null;
    }

    public function getGuestToken(): ?string
    {
        $token = $this->getSession()?->get(self::SESSION_TOKEN_KEY);

        return is_string($token) && '' !== $token ? $token : null;
    }

    public function getOrCreateGuestToken(): string
    {
        $token = $this->getGuestToken();
        if (null !== $token) {
            return $token;
        }

        $session = $this->getSession();
        if (null === $session) {
            throw new \LogicException('A session is required to create a guest wishlist.');
        }

        $token = bin2hex(random_bytes(32));
        $session->set(self::SESSION_TOKEN_KEY, $token);

        return $token;
    }

    public function getWishlists(): array
    {
        return $this->wishlists ??= $this->wishlistRepository->findByOwner(
            $this->getChannel(),
            $this->getCustomer(),
            $this->getGuestToken(),
        );
    }

    public function getWishlist(mixed $id): ?WishlistInterface
    {
        if (!is_scalar($id)) {
            return null;
        }

        foreach ($this->getWishlists() as $wishlist) {
            $wishlistId = $wishlist->getId();
            if (is_scalar($wishlistId) && (string) $wishlistId === (string) $id) {
                return $wishlist;
            }
        }

        return null;
    }

    public function getWishlistsContaining(ProductVariantInterface $variant): array
    {
        return array_values(array_filter(
            $this->getWishlists(),
            static fn (WishlistInterface $wishlist): bool => $wishlist->hasVariant($variant),
        ));
    }

    public function countVariants(): int
    {
        $variantIds = [];
        foreach ($this->getWishlists() as $wishlist) {
            foreach ($wishlist->getItems() as $item) {
                $variantIds[(string) $item->getVariant()?->getId()] = true;
            }
        }

        return count($variantIds);
    }

    public function reset(): void
    {
        $this->wishlists = null;
    }

    private function getSession(): ?SessionInterface
    {
        $request = $this->requestStack->getMainRequest();
        if (null === $request || !$request->hasSession()) {
            return null;
        }

        return $request->getSession();
    }
}
