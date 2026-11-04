<?php

declare(strict_types=1);

namespace Tests\Evolutive\SyliusWishlistPlugin\Unit\Cart;

use Evolutive\SyliusWishlistPlugin\Cart\WishlistCartAdder;
use Evolutive\SyliusWishlistPlugin\Checker\VariantAvailabilityChecker;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\OrderBundle\Adder\CartItemAdderInterface;
use Sylius\Bundle\OrderBundle\Controller\AddToCartCommand;
use Sylius\Bundle\OrderBundle\Factory\AddToCartCommandFactoryInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Factory\CartItemFactoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItem;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Modifier\OrderItemQuantityModifierInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class WishlistCartAdderTest extends TestCase
{
    public function test_available_variants_are_added_and_the_others_reported(): void
    {
        $available = $this->createMock(ProductVariantInterface::class);
        $outOfStock = $this->createMock(ProductVariantInterface::class);
        $rejectedByCartRules = $this->createMock(ProductVariantInterface::class);

        $availabilityChecker = $this->createMock(VariantAvailabilityChecker::class);
        $availabilityChecker->method('isAvailableForPurchase')->willReturnCallback(
            static fn (ProductVariantInterface $variant): bool => $variant !== $outOfStock,
        );

        $cart = $this->createMock(OrderInterface::class);
        $cartContext = $this->createMock(CartContextInterface::class);
        $cartContext->method('getCart')->willReturn($cart);

        $cartItemFactory = $this->createMock(CartItemFactoryInterface::class);
        $cartItemFactory->method('createNew')->willReturnCallback(static fn (): OrderItemInterface => new OrderItem());

        $quantityModifier = $this->createMock(OrderItemQuantityModifierInterface::class);
        $quantityModifier->expects(self::exactly(2))->method('modify')->with(self::anything(), 1);

        $commandFactory = $this->createMock(AddToCartCommandFactoryInterface::class);
        $commandFactory->method('createWithCartAndCartItem')->willReturnCallback(
            static fn (OrderInterface $cart, OrderItemInterface $item): AddToCartCommand => new AddToCartCommand($cart, $item),
        );

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturnCallback(
            static fn (AddToCartCommand $command): ConstraintViolationList => $command->getCartItem()->getVariant() === $rejectedByCartRules
                ? new ConstraintViolationList([new ConstraintViolation('Not enough stock', null, [], null, '', null)])
                : new ConstraintViolationList(),
        );

        $cartItemAdder = $this->createMock(CartItemAdderInterface::class);
        $cartItemAdder->expects(self::once())->method('add');

        $channelContext = $this->createMock(ChannelContextInterface::class);
        $channelContext->method('getChannel')->willReturn($this->createMock(ChannelInterface::class));

        $adder = new WishlistCartAdder($cartContext, $cartItemFactory, $quantityModifier, $commandFactory, $cartItemAdder, $validator, $availabilityChecker, $channelContext);
        $result = $adder->add([$available, $outOfStock, $rejectedByCartRules]);

        self::assertSame([$available], $result->added);
        self::assertSame([$outOfStock, $rejectedByCartRules], $result->rejected);
    }
}
