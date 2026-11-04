<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Context;

use Doctrine\ORM\EntityManagerInterface;
use Evolutive\SyliusWishlistPlugin\Entity\Wishlist;
use Evolutive\SyliusWishlistPlugin\Repository\WishlistRepository;
use Psr\Clock\ClockInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Customer\Context\CustomerContextInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/** Resolves the Current Wishlist of the visitor in the current channel. */
class WishlistContext
{
    public const SESSION_TOKEN_KEY = '_evolutive_wishlist.guest_token';

    public function __construct(
        private readonly WishlistRepository $wishlistRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ChannelContextInterface $channelContext,
        private readonly CustomerContextInterface $customerContext,
        private readonly RequestStack $requestStack,
        private readonly ClockInterface $clock,
    ) {
    }

    /** Never creates anything: viewing must not persist empty wishlists. */
    public function getWishlist(): ?Wishlist
    {
        $channel = $this->getChannel();
        $customer = $this->getCustomer();

        if (null !== $customer) {
            return $this->wishlistRepository->findOneByCustomerAndChannel($customer, $channel);
        }

        $token = $this->getSession()?->get(self::SESSION_TOKEN_KEY);
        if (!is_string($token) || '' === $token) {
            return null;
        }

        return $this->wishlistRepository->findOneByTokenAndChannel($token, $channel);
    }

    public function getOrCreateWishlist(): Wishlist
    {
        $wishlist = $this->getWishlist();
        if (null !== $wishlist) {
            return $wishlist;
        }

        $channel = $this->getChannel();
        $now = $this->clock->now();
        $customer = $this->getCustomer();

        $wishlist = null !== $customer
            ? Wishlist::forCustomer($customer, $channel, $now)
            : Wishlist::forGuest($this->getOrCreateGuestToken(), $channel, $now);

        $this->entityManager->persist($wishlist);

        return $wishlist;
    }

    private function getOrCreateGuestToken(): string
    {
        $session = $this->getSession();
        if (null === $session) {
            throw new \LogicException('A session is required to create a guest wishlist.');
        }

        $token = $session->get(self::SESSION_TOKEN_KEY);
        if (!is_string($token) || '' === $token) {
            $token = bin2hex(random_bytes(32));
            $session->set(self::SESSION_TOKEN_KEY, $token);
        }

        return $token;
    }

    private function getChannel(): ChannelInterface
    {
        /** @var ChannelInterface $channel */
        $channel = $this->channelContext->getChannel();

        return $channel;
    }

    private function getCustomer(): ?CustomerInterface
    {
        $customer = $this->customerContext->getCustomer();

        return $customer instanceof CustomerInterface ? $customer : null;
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
