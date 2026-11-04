<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Evolutive\SyliusWishlistPlugin\Entity\Wishlist;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;

/** @extends ServiceEntityRepository<Wishlist> */
class WishlistRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Wishlist::class);
    }

    public function findOneByCustomerAndChannel(CustomerInterface $customer, ChannelInterface $channel): ?Wishlist
    {
        /** @var Wishlist|null $result */
        $result = $this->createQueryBuilder('w')
            ->andWhere('w.customer = :customer')
            ->andWhere('w.channel = :channel')
            ->setParameter('customer', $customer)
            ->setParameter('channel', $channel)
            ->orderBy('w.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $result;
    }

    public function findOneByTokenAndChannel(string $token, ChannelInterface $channel): ?Wishlist
    {
        /** @var Wishlist|null $result */
        $result = $this->createQueryBuilder('w')
            ->andWhere('w.token = :token')
            ->andWhere('w.customer IS NULL')
            ->andWhere('w.channel = :channel')
            ->setParameter('token', $token)
            ->setParameter('channel', $channel)
            ->orderBy('w.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $result;
    }

    /** @return list<Wishlist> */
    public function findGuestWishlistsByToken(string $token): array
    {
        /** @var list<Wishlist> $result */
        $result = $this->createQueryBuilder('w')
            ->andWhere('w.token = :token')
            ->andWhere('w.customer IS NULL')
            ->setParameter('token', $token)
            ->getQuery()
            ->getResult()
        ;

        return $result;
    }

    public function countGuestWishlistsNotUpdatedSince(\DateTimeImmutable $threshold): int
    {
        $count = $this->createQueryBuilder('w')
            ->select('COUNT(w.id)')
            ->andWhere('w.customer IS NULL')
            ->andWhere('w.updatedAt < :threshold')
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return is_numeric($count) ? (int) $count : 0;
    }

    /**
     * Deletes one batch; items go with their wishlist through the ON DELETE CASCADE foreign key.
     *
     * @return int number of deleted wishlists
     */
    public function deleteGuestWishlistsNotUpdatedSince(\DateTimeImmutable $threshold, int $batchSize): int
    {
        /** @var list<int> $ids */
        $ids = $this->createQueryBuilder('w')
            ->select('w.id')
            ->andWhere('w.customer IS NULL')
            ->andWhere('w.updatedAt < :threshold')
            ->setParameter('threshold', $threshold)
            ->setMaxResults($batchSize)
            ->getQuery()
            ->getSingleColumnResult()
        ;

        if ([] === $ids) {
            return 0;
        }

        $deleted = $this->getEntityManager()->createQueryBuilder()
            ->delete(Wishlist::class, 'w')
            ->andWhere('w.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->execute()
        ;

        return is_numeric($deleted) ? (int) $deleted : 0;
    }
}
