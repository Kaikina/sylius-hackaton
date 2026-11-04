<?php

declare(strict_types=1);

namespace Tests\Evolutive\SyliusWishlistPlugin\Unit\EventListener;

use Evolutive\SyliusWishlistPlugin\Context\WishlistContextInterface;
use Evolutive\SyliusWishlistPlugin\Entity\Wishlist;
use Evolutive\SyliusWishlistPlugin\EventListener\WishlistOwnerListener;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;

final class WishlistOwnerListenerTest extends TestCase
{
    public function test_a_new_wishlist_belongs_to_the_logged_in_customer_in_the_current_channel(): void
    {
        $channel = $this->createMock(ChannelInterface::class);
        $customer = $this->createMock(CustomerInterface::class);
        $context = $this->createMock(WishlistContextInterface::class);
        $context->method('getChannel')->willReturn($channel);
        $context->method('getCustomer')->willReturn($customer);
        $context->expects(self::never())->method('getOrCreateGuestToken');

        $wishlist = new Wishlist();
        (new WishlistOwnerListener($context))->assignOwner(new ResourceControllerEvent($wishlist));

        self::assertSame($channel, $wishlist->getChannel());
        self::assertSame($customer, $wishlist->getCustomer());
    }

    public function test_a_guest_wishlist_gets_the_session_token(): void
    {
        $context = $this->createMock(WishlistContextInterface::class);
        $context->method('getChannel')->willReturn($this->createMock(ChannelInterface::class));
        $context->method('getCustomer')->willReturn(null);
        $context->method('getOrCreateGuestToken')->willReturn('abc');

        $wishlist = new Wishlist();
        (new WishlistOwnerListener($context))->assignOwner(new ResourceControllerEvent($wishlist));

        self::assertTrue($wishlist->isGuest());
        self::assertSame('abc', $wishlist->getToken());
    }
}
