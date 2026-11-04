<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Adopter;

use Evolutive\SyliusWishlistPlugin\Context\WishlistContext;
use Evolutive\SyliusWishlistPlugin\Entity\WishlistInterface;
use Evolutive\SyliusWishlistPlugin\Modifier\WishlistModifierInterface;
use Evolutive\SyliusWishlistPlugin\Repository\WishlistRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * At login, each guest wishlist is merged into the customer's wishlist of the same name in that channel
 * (variants already there are skipped), or becomes the customer's when there is none. The guest token is forgotten.
 */
class WishlistAdopter
{
    /** @param WishlistRepositoryInterface<WishlistInterface> $wishlistRepository */
    public function __construct(
        private readonly WishlistRepositoryInterface $wishlistRepository,
        private readonly WishlistModifierInterface $wishlistModifier,
        private readonly RequestStack $requestStack,
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

        foreach ($this->wishlistRepository->findGuestWishlistsByToken($token) as $guestWishlist) {
            $channel = $guestWishlist->getChannel();
            $namesake = null === $channel ? null : $this->findNamesake($customer, $channel, (string) $guestWishlist->getName());

            if (null === $namesake) {
                $guestWishlist->assignToCustomer($customer);
                $this->wishlistRepository->add($guestWishlist);

                continue;
            }

            foreach ($guestWishlist->getItems() as $item) {
                $variant = $item->getVariant();
                if (null !== $variant) {
                    $this->wishlistModifier->addVariant($namesake, $variant);
                }
            }

            $this->wishlistRepository->remove($guestWishlist);
        }
    }

    private function findNamesake(CustomerInterface $customer, ChannelInterface $channel, string $name): ?WishlistInterface
    {
        foreach ($this->wishlistRepository->findByOwner($channel, $customer, null) as $wishlist) {
            if (0 === strcasecmp((string) $wishlist->getName(), $name)) {
                return $wishlist;
            }
        }

        return null;
    }
}
