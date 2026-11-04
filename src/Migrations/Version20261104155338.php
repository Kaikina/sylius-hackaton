<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Sylius\Bundle\CoreBundle\Doctrine\Migrations\AbstractMigration;

final class Version20261104155338 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drops the DC2Type comments of wishlist date columns, which DBAL 4 no longer uses.';
    }

    public function up(Schema $schema): void
    {
        // DBAL 3 still reads the comments to map datetime_immutable columns.
        $this->skipIf(method_exists(Type::class, 'requiresSQLCommentHint'), 'DBAL 3 still needs the DC2Type comments.');

        $this->addSql('ALTER TABLE evolutive_wishlist CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE evolutive_wishlist_item CHANGE created_at created_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE evolutive_wishlist CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE evolutive_wishlist_item CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }
}
