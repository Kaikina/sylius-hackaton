<?php

declare(strict_types=1);

namespace Tests\Evolutive\SyliusWishlistPlugin\Unit\Command;

use Evolutive\SyliusWishlistPlugin\Command\PurgeGuestWishlistsCommand;
use Evolutive\SyliusWishlistPlugin\Repository\WishlistRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Console\Tester\CommandTester;

final class PurgeGuestWishlistsCommandTest extends TestCase
{
    public function test_it_deletes_in_batches_wishlists_not_updated_within_the_ttl(): void
    {
        $repository = $this->createMock(WishlistRepositoryInterface::class);
        $repository->expects(self::exactly(3))
            ->method('deleteGuestWishlistsNotUpdatedSince')
            ->with(new \DateTimeImmutable('2026-10-05 12:00:00'), self::anything())
            ->willReturnOnConsecutiveCalls(500, 12, 0)
        ;

        $tester = new CommandTester(new PurgeGuestWishlistsCommand($repository, new MockClock('2026-11-04 12:00:00'), 30));

        self::assertSame(0, $tester->execute([]));
        self::assertStringContainsString('Deleted 512 guest wishlist(s)', $tester->getDisplay());
    }

    public function test_dry_run_only_counts_and_ttl_can_be_overridden(): void
    {
        $repository = $this->createMock(WishlistRepositoryInterface::class);
        $repository->expects(self::never())->method('deleteGuestWishlistsNotUpdatedSince');
        $repository->method('countGuestWishlistsNotUpdatedSince')
            ->with(new \DateTimeImmutable('2026-11-03 12:00:00'))
            ->willReturn(7)
        ;

        $tester = new CommandTester(new PurgeGuestWishlistsCommand($repository, new MockClock('2026-11-04 12:00:00'), 30));

        self::assertSame(0, $tester->execute(['--dry-run' => true, '--ttl' => '1']));
        self::assertStringContainsString('7 guest wishlist(s)', $tester->getDisplay());
    }
}
