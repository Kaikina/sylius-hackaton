<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

#[ORM\Entity]
#[ORM\Table(name: 'evolutive_wishlist_item')]
#[ORM\UniqueConstraint(name: 'evolutive_wishlist_item_variant_unique', columns: ['wishlist_id', 'variant_id'])]
class WishlistItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Wishlist::class, inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'wishlist_id', nullable: false, onDelete: 'CASCADE')]
    private Wishlist $wishlist;

    #[ORM\ManyToOne(targetEntity: ProductVariantInterface::class)]
    #[ORM\JoinColumn(name: 'variant_id', nullable: false, onDelete: 'CASCADE')]
    private ProductVariantInterface $variant;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(Wishlist $wishlist, ProductVariantInterface $variant, \DateTimeImmutable $createdAt)
    {
        $this->wishlist = $wishlist;
        $this->variant = $variant;
        $this->createdAt = $createdAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWishlist(): Wishlist
    {
        return $this->wishlist;
    }

    public function getVariant(): ProductVariantInterface
    {
        return $this->variant;
    }

    public function getProduct(): ProductInterface
    {
        /** @var ProductInterface $product */
        $product = $this->variant->getProduct();

        return $product;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
