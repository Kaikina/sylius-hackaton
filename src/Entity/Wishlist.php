<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'evolutive_wishlist')]
#[ORM\Index(name: 'evolutive_wishlist_token_idx', columns: ['token'])]
#[ORM\Index(name: 'evolutive_wishlist_updated_at_idx', columns: ['updated_at'])]
class Wishlist implements WishlistInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'evolutive_sylius_wishlist.wishlist.name.not_blank', normalizer: 'trim', groups: ['evolutive_sylius_wishlist'])]
    #[Assert\Length(max: 255, maxMessage: 'evolutive_sylius_wishlist.wishlist.name.max_length', groups: ['evolutive_sylius_wishlist'])]
    private ?string $name = null;

    #[ORM\ManyToOne(targetEntity: ChannelInterface::class)]
    #[ORM\JoinColumn(name: 'channel_id', nullable: false, onDelete: 'CASCADE')]
    private ?ChannelInterface $channel = null;

    #[ORM\ManyToOne(targetEntity: CustomerInterface::class)]
    #[ORM\JoinColumn(name: 'customer_id', nullable: true, onDelete: 'CASCADE')]
    private ?CustomerInterface $customer = null;

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private ?string $token = null;

    /** @var Collection<int, WishlistItemInterface> */
    #[ORM\OneToMany(targetEntity: WishlistItemInterface::class, mappedBy: 'wishlist', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC', 'id' => 'DESC'])]
    private Collection $items;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
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
        $this->name = null === $name ? null : trim($name);
    }

    public function getChannel(): ?ChannelInterface
    {
        return $this->channel;
    }

    public function setChannel(ChannelInterface $channel): void
    {
        $this->channel = $channel;
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

    public function assignToCustomer(CustomerInterface $customer): void
    {
        $this->customer = $customer;
        $this->token = null;
    }

    public function assignToGuest(string $token): void
    {
        $this->customer = null;
        $this->token = $token;
    }

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

    public function getItemForVariant(ProductVariantInterface $variant): ?WishlistItemInterface
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

    public function hasItem(WishlistItemInterface $item): bool
    {
        return $this->items->contains($item);
    }

    public function addItem(WishlistItemInterface $item): void
    {
        $variant = $item->getVariant();
        if ($this->hasItem($item) || (null !== $variant && $this->hasVariant($variant))) {
            return;
        }

        $item->setWishlist($this);
        $this->items->add($item);
    }

    public function removeItem(WishlistItemInterface $item): void
    {
        if ($this->items->removeElement($item)) {
            $item->setWishlist(null);
        }
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function touch(\DateTimeImmutable $now): void
    {
        $this->updatedAt = $now;
    }
}
