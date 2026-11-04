<?php

declare(strict_types=1);

namespace Tests\Evolutive\SyliusWishlistPlugin\Unit\Adopter;

use Doctrine\ORM\EntityManagerInterface;
use Evolutive\SyliusWishlistPlugin\Adopter\WishlistAdopter;
use Evolutive\SyliusWishlistPlugin\Context\WishlistContext;
use Evolutive\SyliusWishlistPlugin\Entity\Wishlist;
use Evolutive\SyliusWishlistPlugin\Repository\WishlistRepository;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class WishlistAdopterTest extends TestCase
{
    public function test_guest_wishlist_is_adopted_when_the_customer_has_none_in_that_channel(): void
    {
        $customer = $this->createMock(CustomerInterface::class);
        $guestWishlist = Wishlist::forGuest('abc', $this->createMock(ChannelInterface::class), new \DateTimeImmutable());

        $repository = $this->createMock(WishlistRepository::class);
        $repository->method('findGuestWishlistsByToken')->with('abc')->willReturn([$guestWishlist]);
        $repository->method('findOneByCustomerAndChannel')->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('remove');
        $entityManager->expects(self::once())->method('flush');

        $session = $this->sessionWithToken('abc');
        (new WishlistAdopter($repository, $entityManager, $this->requestStack($session), new MockClock()))->adoptGuestWishlists($customer);

        self::assertSame($customer, $guestWishlist->getCustomer());
        self::assertNull($guestWishlist->getToken());
        self::assertFalse($session->has(WishlistContext::SESSION_TOKEN_KEY));
    }

    public function test_guest_wishlist_is_discarded_when_the_customer_already_has_one(): void
    {
        $customer = $this->createMock(CustomerInterface::class);
        $channel = $this->createMock(ChannelInterface::class);
        $guestWishlist = Wishlist::forGuest('abc', $channel, new \DateTimeImmutable());
        $customerWishlist = Wishlist::forCustomer($customer, $channel, new \DateTimeImmutable());

        $repository = $this->createMock(WishlistRepository::class);
        $repository->method('findGuestWishlistsByToken')->willReturn([$guestWishlist]);
        $repository->method('findOneByCustomerAndChannel')->willReturn($customerWishlist);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('remove')->with($guestWishlist);

        $session = $this->sessionWithToken('abc');
        (new WishlistAdopter($repository, $entityManager, $this->requestStack($session), new MockClock()))->adoptGuestWishlists($customer);

        self::assertTrue($guestWishlist->isGuest());
        self::assertFalse($session->has(WishlistContext::SESSION_TOKEN_KEY));
    }

    public function test_nothing_happens_without_guest_token(): void
    {
        $repository = $this->createMock(WishlistRepository::class);
        $repository->expects(self::never())->method('findGuestWishlistsByToken');

        $adopter = new WishlistAdopter($repository, $this->createMock(EntityManagerInterface::class), $this->requestStack(new Session(new MockArraySessionStorage())), new MockClock());
        $adopter->adoptGuestWishlists($this->createMock(CustomerInterface::class));
    }

    private function sessionWithToken(string $token): Session
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set(WishlistContext::SESSION_TOKEN_KEY, $token);

        return $session;
    }

    private function requestStack(Session $session): RequestStack
    {
        $request = new Request();
        $request->setSession($session);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        return $requestStack;
    }
}
