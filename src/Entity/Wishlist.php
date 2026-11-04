<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Evolutive\SyliusWishlistPlugin\Repository\WishlistRepository;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

/**
 * Owned either by a Customer or by a guest token, never both.
 * The model allows several wishlists per owner; V1 only ever uses one per owner and channel.
 */
#[ORM\Entity(repositoryClass: WishlistRepository::class)]
#[ORM\Table(name: 'evolutive_wishlist')]
#[ORM\Index(name: 'evolutive_wishlist_token_idx', columns: ['token'])]
#[ORM\Index(name: 'evolutive_wishlist_updated_at_idx', columns: ['updated_at'])]
class Wishlist
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\ManyToOne(targetEntity: ChannelInterface::class)]
    #[ORM\JoinColumn(name: 'channel_id', nullable: false, onDelete: 'CASCADE')]
    private ChannelInterface $channel;

    #[ORM\ManyToOne(targetEntity: CustomerInterface::class)]
    #[ORM\JoinColumn(name: 'customer_id', nullable: true, onDelete: 'CASCADE')]
    private ?CustomerInterface $customer = null;

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private ?string $token = null;

    /** @var Collection<int, WishlistItem> */
    #[ORM\OneToMany(targetEntity: WishlistItem::class, mappedBy: 'wishlist', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC', 'id' => 'DESC'])]
    private Collection $items;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    private function __construct(ChannelInterface $channel, \DateTimeImmutable $now)
    {
        $this->channel = $channel;
        $this->items = new ArrayCollection();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public static function forCustomer(CustomerInterface $customer, ChannelInterface $channel, \DateTimeImmutable $now): self
    {
        $wishlist = new self($channel, $now);
        $wishlist->customer = $customer;

        return $wishlist;
    }

    public static function forGuest(string $token, ChannelInterface $channel, \DateTimeImmutable $now): self
    {
        $wishlist = new self($channel, $now);
        $wishlist->token = $token;

        return $wishlist;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getChannel(): ChannelInterface
    {
        return $this->channel;
    }

    public function getCustomer(): ?CustomerInterface
    {
        return $this->customer;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function isGuest(): bool
    {
        return null === $this->customer;
    }

    /** Wishlist Adoption: the guest wishlist becomes the customer's. */
    public function assignToCustomer(CustomerInterface $customer, \DateTimeImmutable $now): void
    {
        $this->customer = $customer;
        $this->token = null;
        $this->updatedAt = $now;
    }

    /** @return Collection<int, WishlistItem> */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function countItems(): int
    {
        return $this->items->count();
    }

    public function isEmpty(): bool
    {
        return $this->items->isEmpty();
    }

    public function getItemForVariant(ProductVariantInterface $variant): ?WishlistItem
    {
        foreach ($this->items as $item) {
            if ($item->getVariant() === $variant) {
                return $item;
            }
        }

        return null;
    }

    public function hasVariant(ProductVariantInterface $variant): bool
    {
        return null !== $this->getItemForVariant($variant);
    }

    /** Returns false when the variant was already in the wishlist. */
    public function addVariant(ProductVariantInterface $variant, \DateTimeImmutable $now): bool
    {
        if ($this->hasVariant($variant)) {
            return false;
        }

        $this->items->add(new WishlistItem($this, $variant, $now));
        $this->updatedAt = $now;

        return true;
    }

    public function removeVariant(ProductVariantInterface $variant, \DateTimeImmutable $now): bool
    {
        $item = $this->getItemForVariant($variant);
        if (null === $item) {
            return false;
        }

        $this->items->removeElement($item);
        $this->updatedAt = $now;

        return true;
    }

    public function clear(\DateTimeImmutable $now): void
    {
        $this->items->clear();
        $this->updatedAt = $now;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
