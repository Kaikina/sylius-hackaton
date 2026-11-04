<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Repository;

use Doctrine\ORM\QueryBuilder;
use Evolutive\SyliusWishlistPlugin\Entity\WishlistInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;

/**
 * @template T of WishlistInterface
 * @implements WishlistRepositoryInterface<T>
 */
class WishlistRepository extends EntityRepository implements WishlistRepositoryInterface
{
    public function findByOwner(ChannelInterface $channel, ?CustomerInterface $customer, ?string $token): array
    {
        $queryBuilder = $this->createOwnerQueryBuilder($channel, $customer, $token);
        if (null === $queryBuilder) {
            return [];
        }

        /** @var list<WishlistInterface> $result */
        $result = $queryBuilder
            ->addSelect('i')
            ->leftJoin('w.items', 'i')
            ->addOrderBy('w.createdAt', 'ASC')
            ->addOrderBy('w.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return $result;
    }

    public function findOneByIdAndOwner(mixed $id, ChannelInterface $channel, ?CustomerInterface $customer, ?string $token): ?WishlistInterface
    {
        if (!is_numeric($id)) {
            return null;
        }

        /** @var WishlistInterface|null $result */
        $result = $this->createOwnerQueryBuilder($channel, $customer, $token)
            ?->andWhere('w.id = :id')
            ->setParameter('id', (int) $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $result;
    }

    public function findGuestWishlistsByToken(string $token): array
    {
        /** @var list<WishlistInterface> $result */
        $result = $this->createQueryBuilder('w')
            ->andWhere('w.token = :token')
            ->andWhere('w.customer IS NULL')
            ->setParameter('token', $token)
            ->addOrderBy('w.id', 'ASC')
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

    /** Bulk DQL delete; items go with their wishlist through the ON DELETE CASCADE foreign key. */
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

        $deleted = $this->createQueryBuilder('w')
            ->delete()
            ->andWhere('w.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->execute()
        ;

        return is_numeric($deleted) ? (int) $deleted : 0;
    }

    private function createOwnerQueryBuilder(ChannelInterface $channel, ?CustomerInterface $customer, ?string $token): ?QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('w')
            ->andWhere('w.channel = :channel')
            ->setParameter('channel', $channel)
        ;

        if (null !== $customer) {
            return $queryBuilder
                ->andWhere('w.customer = :customer')
                ->setParameter('customer', $customer)
            ;
        }

        if (null === $token || '' === $token) {
            return null;
        }

        return $queryBuilder
            ->andWhere('w.token = :token')
            ->andWhere('w.customer IS NULL')
            ->setParameter('token', $token)
        ;
    }
}
