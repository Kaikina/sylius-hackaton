<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Command;

use Evolutive\SyliusWishlistPlugin\Repository\WishlistRepository;
use Psr\Clock\ClockInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'sylius:wishlist:purge-guest',
    description: 'Deletes guest wishlists not modified within the guest TTL (mostly orphaned ones whose session is gone).',
)]
final class PurgeGuestWishlistsCommand extends Command
{
    private const BATCH_SIZE = 500;

    public function __construct(
        private readonly WishlistRepository $wishlistRepository,
        private readonly ClockInterface $clock,
        private readonly int $guestTtlDays,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('ttl', null, InputOption::VALUE_REQUIRED, 'Overrides the configured guest TTL, in days')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Only count the wishlists that would be deleted')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $ttl = $input->getOption('ttl');
        if (null !== $ttl && (!is_string($ttl) || !ctype_digit($ttl))) {
            $io->error('The TTL must be zero or a positive number of days.');

            return Command::INVALID;
        }

        $ttlDays = null === $ttl ? $this->guestTtlDays : (int) $ttl;
        if ($ttlDays < 0) {
            $io->error('The TTL must be zero or a positive number of days.');

            return Command::INVALID;
        }

        $threshold = $this->clock->now()->modify(sprintf('-%d days', $ttlDays));

        if ((bool) $input->getOption('dry-run')) {
            $count = $this->wishlistRepository->countGuestWishlistsNotUpdatedSince($threshold);
            $io->note(sprintf('%d guest wishlist(s) not modified since %s would be deleted.', $count, $threshold->format(\DATE_ATOM)));

            return Command::SUCCESS;
        }

        $total = 0;
        do {
            $deleted = $this->wishlistRepository->deleteGuestWishlistsNotUpdatedSince($threshold, self::BATCH_SIZE);
            $total += $deleted;
        } while ($deleted > 0);

        $io->success(sprintf('Deleted %d guest wishlist(s) not modified since %s.', $total, $threshold->format(\DATE_ATOM)));

        return Command::SUCCESS;
    }
}
