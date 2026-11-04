<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\EventListener;

use Evolutive\SyliusWishlistPlugin\Adopter\WishlistAdopter;
use Sylius\Bundle\CoreBundle\SectionResolver\SectionProviderInterface;
use Sylius\Bundle\ShopBundle\SectionResolver\ShopSection;
use Sylius\Bundle\UserBundle\Event\UserEvent;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

final class WishlistAdoptionListener
{
    public function __construct(
        private readonly WishlistAdopter $wishlistAdopter,
        private readonly SectionProviderInterface $sectionProvider,
    ) {
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $this->adopt($event->getAuthenticationToken()->getUser());
    }

    public function onImplicitLogin(UserEvent $event): void
    {
        $this->adopt($event->getUser());
    }

    private function adopt(mixed $user): void
    {
        if (!$this->sectionProvider->getSection() instanceof ShopSection || !$user instanceof ShopUserInterface) {
            return;
        }

        $customer = $user->getCustomer();
        if ($customer instanceof CustomerInterface) {
            $this->wishlistAdopter->adoptGuestWishlists($customer);
        }
    }
}
