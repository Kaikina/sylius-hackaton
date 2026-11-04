<?php

declare(strict_types=1);

namespace Tests\Evolutive\SyliusWishlistPlugin\Unit\Adopter;

use Evolutive\SyliusWishlistPlugin\Adopter\WishlistAdopter;
use Evolutive\SyliusWishlistPlugin\Context\WishlistContext;
use Evolutive\SyliusWishlistPlugin\Entity\Wishlist;
use Evolutive\SyliusWishlistPlugin\Entity\WishlistItem;
use Evolutive\SyliusWishlistPlugin\Modifier\WishlistModifierInterface;
use Evolutive\SyliusWishlistPlugin\Repository\WishlistRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class WishlistAdopterTest extends TestCase
{
    public function test_guest_wishlists_merge_into_namesakes_or_become_the_customer_ones(): void
    {
        $channel = $this->createMock(ChannelInterface::class);
        $customer = $this->createMock(CustomerInterface::class);
        $shirt = $this->createMock(ProductVariantInterface::class);

        $guestBirthday = self::guestWishlist($channel, 'birthday', $shirt);
        $guestChristmas = self::guestWishlist($channel, 'Christmas');
        $customerBirthday = new Wishlist();
        $customerBirthday->setName('Birthday');

        $repository = $this->createMock(WishlistRepositoryInterface::class);
        $repository->method('findGuestWishlistsByToken')->with('abc')->willReturn([$guestBirthday, $guestChristmas]);
        $repository->method('findByOwner')->with($channel, $customer, null)->willReturn([$customerBirthday]);
        $repository->expects(self::once())->method('remove')->with($guestBirthday);
        $repository->expects(self::once())->method('add')->with($guestChristmas);

        $modifier = $this->createMock(WishlistModifierInterface::class);
        $modifier->expects(self::once())->method('addVariant')->with($customerBirthday, $shirt);

        $session = new Session(new MockArraySessionStorage());
        $session->set(WishlistContext::SESSION_TOKEN_KEY, 'abc');

        (new WishlistAdopter($repository, $modifier, self::requestStack($session)))->adoptGuestWishlists($customer);

        self::assertSame($customer, $guestChristmas->getCustomer());
        self::assertFalse($session->has(WishlistContext::SESSION_TOKEN_KEY));
    }

    public function test_nothing_happens_without_a_guest_token(): void
    {
        $repository = $this->createMock(WishlistRepositoryInterface::class);
        $repository->expects(self::never())->method('findGuestWishlistsByToken');

        (new WishlistAdopter($repository, $this->createMock(WishlistModifierInterface::class), self::requestStack(new Session(new MockArraySessionStorage()))))
            ->adoptGuestWishlists($this->createMock(CustomerInterface::class));
    }

    private static function guestWishlist(ChannelInterface $channel, string $name, ProductVariantInterface ...$variants): Wishlist
    {
        $wishlist = new Wishlist();
        $wishlist->setName($name);
        $wishlist->setChannel($channel);
        $wishlist->assignToGuest('abc');
        foreach ($variants as $variant) {
            $item = new WishlistItem();
            $item->setVariant($variant);
            $wishlist->addItem($item);
        }

        return $wishlist;
    }

    private static function requestStack(Session $session): RequestStack
    {
        $request = new Request();
        $request->setSession($session);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        return $requestStack;
    }
}
