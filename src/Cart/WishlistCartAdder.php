<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Cart;

use Evolutive\SyliusWishlistPlugin\Checker\VariantAvailabilityChecker;
use Sylius\Bundle\OrderBundle\Adder\CartItemAdderInterface;
use Sylius\Bundle\OrderBundle\Factory\AddToCartCommandFactoryInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Factory\CartItemFactoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Modifier\OrderItemQuantityModifierInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/** Adds one unit per variant through the regular Sylius cart flow, so cart validation still applies. */
class WishlistCartAdder
{
    /**
     * @param CartItemFactoryInterface<OrderItemInterface> $cartItemFactory
     * @param list<string> $validationGroups
     */
    public function __construct(
        private readonly CartContextInterface $cartContext,
        private readonly CartItemFactoryInterface $cartItemFactory,
        private readonly OrderItemQuantityModifierInterface $quantityModifier,
        private readonly AddToCartCommandFactoryInterface $addToCartCommandFactory,
        private readonly CartItemAdderInterface $cartItemAdder,
        private readonly ValidatorInterface $validator,
        private readonly VariantAvailabilityChecker $availabilityChecker,
        private readonly ChannelContextInterface $channelContext,
        private readonly array $validationGroups = ['sylius'],
    ) {
    }

    /** @param iterable<ProductVariantInterface> $variants */
    public function add(iterable $variants): CartAdditionResult
    {
        /** @var ChannelInterface $channel */
        $channel = $this->channelContext->getChannel();
        $result = new CartAdditionResult();

        foreach ($variants as $variant) {
            if (!$this->availabilityChecker->isAvailableForPurchase($variant, $channel)) {
                $result->rejected[] = $variant;

                continue;
            }

            /** @var OrderInterface $cart */
            $cart = $this->cartContext->getCart();

            /** @var OrderItemInterface $cartItem */
            $cartItem = $this->cartItemFactory->createNew();
            $cartItem->setVariant($variant);
            $this->quantityModifier->modify($cartItem, 1);

            $command = $this->addToCartCommandFactory->createWithCartAndCartItem($cart, $cartItem);
            if (count($this->validator->validate($command, null, $this->validationGroups)) > 0) {
                $result->rejected[] = $variant;

                continue;
            }

            $this->cartItemAdder->add($command);
            $result->added[] = $variant;
        }

        return $result;
    }
}
