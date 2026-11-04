<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Adopter;

use Doctrine\ORM\EntityManagerInterface;
use Evolutive\SyliusWishlistPlugin\Context\WishlistContext;
use Evolutive\SyliusWishlistPlugin\Repository\WishlistRepository;
use Psr\Clock\ClockInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * V1 rule: a guest wishlist becomes the customer's only if the customer has none in that channel,
 * otherwise it is discarded. The guest token is always forgotten afterwards.
 */
class WishlistAdopter
{
    public function __construct(
        private readonly WishlistRepository $wishlistRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
        private readonly ClockInterface $clock,
    ) {
    }

    public function adoptGuestWishlists(CustomerInterface $customer): void
    {
        $request = $this->requestStack->getMainRequest();
        if (null === $request || !$request->hasSession()) {
            return;
        }

        $session = $request->getSession();
        $token = $session->get(WishlistContext::SESSION_TOKEN_KEY);
        $session->remove(WishlistContext::SESSION_TOKEN_KEY);

        if (!is_string($token) || '' === $token) {
            return;
        }

        $now = $this->clock->now();
        foreach ($this->wishlistRepository->findGuestWishlistsByToken($token) as $guestWishlist) {
            $existing = $this->wishlistRepository->findOneByCustomerAndChannel($customer, $guestWishlist->getChannel());

            if (null === $existing) {
                $guestWishlist->assignToCustomer($customer, $now);

                continue;
            }

            $this->entityManager->remove($guestWishlist);
        }

        $this->entityManager->flush();
    }
}
