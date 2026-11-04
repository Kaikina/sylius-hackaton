<?php

declare(strict_types=1);

namespace Tests\Evolutive\SyliusWishlistPlugin\Unit\Context;

use Doctrine\ORM\EntityManagerInterface;
use Evolutive\SyliusWishlistPlugin\Context\WishlistContext;
use Evolutive\SyliusWishlistPlugin\Entity\Wishlist;
use Evolutive\SyliusWishlistPlugin\Repository\WishlistRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Customer\Context\CustomerContextInterface;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class WishlistContextTest extends TestCase
{
    private WishlistRepository&MockObject $repository;

    private EntityManagerInterface&MockObject $entityManager;

    private ChannelInterface $channel;

    private ?CustomerInterface $customer = null;

    private Session $session;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(WishlistRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->channel = $this->createMock(ChannelInterface::class);
        $this->session = new Session(new MockArraySessionStorage());
    }

    public function test_a_guest_without_token_has_no_wishlist_and_nothing_is_created(): void
    {
        $this->repository->expects(self::never())->method('findOneByTokenAndChannel');
        $this->entityManager->expects(self::never())->method('persist');

        self::assertNull($this->createContext()->getWishlist());
        self::assertFalse($this->session->has(WishlistContext::SESSION_TOKEN_KEY));
    }

    public function test_a_guest_wishlist_is_found_by_its_session_token_and_channel(): void
    {
        $this->session->set(WishlistContext::SESSION_TOKEN_KEY, 'abc');
        $wishlist = Wishlist::forGuest('abc', $this->channel, new \DateTimeImmutable());
        $this->repository->method('findOneByTokenAndChannel')->with('abc', $this->channel)->willReturn($wishlist);

        self::assertSame($wishlist, $this->createContext()->getWishlist());
    }

    public function test_creating_a_guest_wishlist_stores_a_token_in_the_session(): void
    {
        $this->entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(Wishlist::class));

        $wishlist = $this->createContext()->getOrCreateWishlist();

        self::assertTrue($wishlist->isGuest());
        self::assertSame($wishlist->getToken(), $this->session->get(WishlistContext::SESSION_TOKEN_KEY));
        self::assertSame($this->channel, $wishlist->getChannel());
    }

    public function test_a_logged_in_customer_gets_their_own_wishlist_whatever_the_session_holds(): void
    {
        $this->customer = $this->createMock(CustomerInterface::class);
        $this->session->set(WishlistContext::SESSION_TOKEN_KEY, 'abc');
        $wishlist = Wishlist::forCustomer($this->customer, $this->channel, new \DateTimeImmutable());
        $this->repository->method('findOneByCustomerAndChannel')->with($this->customer, $this->channel)->willReturn($wishlist);
        $this->repository->expects(self::never())->method('findOneByTokenAndChannel');

        self::assertSame($wishlist, $this->createContext()->getWishlist());
    }

    public function test_creating_a_customer_wishlist_does_not_touch_the_session(): void
    {
        $this->customer = $this->createMock(CustomerInterface::class);

        $wishlist = $this->createContext()->getOrCreateWishlist();

        self::assertSame($this->customer, $wishlist->getCustomer());
        self::assertFalse($this->session->has(WishlistContext::SESSION_TOKEN_KEY));
    }

    private function createContext(): WishlistContext
    {
        $channelContext = $this->createMock(ChannelContextInterface::class);
        $channelContext->method('getChannel')->willReturn($this->channel);

        $customerContext = $this->createMock(CustomerContextInterface::class);
        $customerContext->method('getCustomer')->willReturn($this->customer);

        $request = new Request();
        $request->setSession($this->session);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        return new WishlistContext($this->repository, $this->entityManager, $channelContext, $customerContext, $requestStack, new MockClock());
    }
}
