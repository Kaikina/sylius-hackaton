<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Sylius\Bundle\CoreBundle\Doctrine\Migrations\AbstractMigration;

final class Version20261104154009 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Makes the wishlist name required; unnamed Stage 1 wishlists become "My Favorites".';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE evolutive_wishlist SET name = 'My Favorites' WHERE name IS NULL OR TRIM(name) = ''");
        $this->addSql('ALTER TABLE evolutive_wishlist CHANGE name name VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE evolutive_wishlist CHANGE name name VARCHAR(255) DEFAULT NULL');
    }
}
