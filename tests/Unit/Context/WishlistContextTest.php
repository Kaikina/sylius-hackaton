<?php

declare(strict_types=1);

namespace Tests\Evolutive\SyliusWishlistPlugin\Unit\Context;

use Evolutive\SyliusWishlistPlugin\Context\WishlistContext;
use Evolutive\SyliusWishlistPlugin\Entity\Wishlist;
use Evolutive\SyliusWishlistPlugin\Entity\WishlistItem;
use Evolutive\SyliusWishlistPlugin\Repository\WishlistRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Customer\Context\CustomerContextInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class WishlistContextTest extends TestCase
{
    private WishlistRepositoryInterface&MockObject $repository;

    private ChannelInterface $channel;

    private ?CustomerInterface $customer = null;

    private Session $session;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(WishlistRepositoryInterface::class);
        $this->channel = $this->createMock(ChannelInterface::class);
        $this->session = new Session(new MockArraySessionStorage());
    }

    public function test_a_guest_is_looked_up_by_session_token_and_reading_never_creates_one(): void
    {
        $this->repository->expects(self::once())->method('findByOwner')->with($this->channel, null, null)->willReturn([]);

        self::assertSame([], $this->createContext()->getWishlists());
        self::assertFalse($this->session->has(WishlistContext::SESSION_TOKEN_KEY));
    }

    public function test_a_customer_is_looked_up_by_customer_whatever_the_session_holds(): void
    {
        $this->customer = $this->createMock(CustomerInterface::class);
        $this->session->set(WishlistContext::SESSION_TOKEN_KEY, 'abc');
        $this->repository->expects(self::once())->method('findByOwner')->with($this->channel, $this->customer, 'abc')->willReturn([]);

        $this->createContext()->getWishlists();
    }

    public function test_only_the_owner_wishlists_can_be_fetched_by_id(): void
    {
        $birthday = $this->wishlist(1);
        $this->repository->method('findByOwner')->willReturn([$birthday]);
        $context = $this->createContext();

        self::assertSame($birthday, $context->getWishlist('1'));
        self::assertNull($context->getWishlist(2));
    }

    public function test_wishlists_are_cached_until_reset(): void
    {
        $this->repository->expects(self::exactly(2))->method('findByOwner')->willReturn([]);
        $context = $this->createContext();

        $context->getWishlists();
        $context->getWishlists();
        $context->reset();
        $context->getWishlists();
    }

    public function test_variants_are_counted_once_across_wishlists(): void
    {
        $shirt = $this->createConfiguredMock(ProductVariantInterface::class, ['getId' => 10]);
        $glasses = $this->createConfiguredMock(ProductVariantInterface::class, ['getId' => 11]);
        $birthday = $this->wishlist(1, $shirt, $glasses);
        $christmas = $this->wishlist(2, $shirt);
        $this->repository->method('findByOwner')->willReturn([$birthday, $christmas]);
        $context = $this->createContext();

        self::assertSame(2, $context->countVariants());
        self::assertSame([$birthday, $christmas], $context->getWishlistsContaining($shirt));
        self::assertSame([$birthday], $context->getWishlistsContaining($glasses));
    }

    public function test_the_guest_token_is_created_once_and_kept_in_session(): void
    {
        $context = $this->createContext();

        $token = $context->getOrCreateGuestToken();

        self::assertSame(64, strlen($token));
        self::assertSame($token, $this->session->get(WishlistContext::SESSION_TOKEN_KEY));
        self::assertSame($token, $context->getOrCreateGuestToken());
    }

    private function wishlist(int $id, ProductVariantInterface ...$variants): Wishlist
    {
        $wishlist = new Wishlist();
        (new \ReflectionProperty(Wishlist::class, 'id'))->setValue($wishlist, $id);
        foreach ($variants as $variant) {
            $item = new WishlistItem();
            $item->setVariant($variant);
            $wishlist->addItem($item);
        }

        return $wishlist;
    }

    private function createContext(): WishlistContext
    {
        $channelContext = $this->createMock(ChannelContextInterface::class);
        $channelContext->method('getChannel')->willReturn($this->channel);

        $customerContext = $this->createMock(CustomerContextInterface::class);
        $customerContext->method('getCustomer')->willReturnCallback(fn (): ?CustomerInterface => $this->customer);

        $request = new Request();
        $request->setSession($this->session);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        return new WishlistContext($this->repository, $channelContext, $customerContext, $requestStack);
    }
}
