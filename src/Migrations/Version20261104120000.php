<?php

declare(strict_types=1);

namespace Evolutive\SyliusWishlistPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Sylius\Bundle\CoreBundle\Doctrine\Migrations\AbstractMigration;

final class Version20261104120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates wishlist and wishlist item tables.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE evolutive_wishlist (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) DEFAULT NULL, token VARCHAR(64) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', channel_id INT NOT NULL, customer_id INT DEFAULT NULL, INDEX IDX_C3C1A8E372F5A1AA (channel_id), INDEX IDX_C3C1A8E39395C3F3 (customer_id), INDEX evolutive_wishlist_token_idx (token), INDEX evolutive_wishlist_updated_at_idx (updated_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE evolutive_wishlist_item (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', wishlist_id INT NOT NULL, variant_id INT NOT NULL, INDEX IDX_D52FA11AFB8E54CD (wishlist_id), INDEX IDX_D52FA11A3B69A9AF (variant_id), UNIQUE INDEX evolutive_wishlist_item_variant_unique (wishlist_id, variant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE evolutive_wishlist ADD CONSTRAINT FK_C3C1A8E372F5A1AA FOREIGN KEY (channel_id) REFERENCES sylius_channel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE evolutive_wishlist ADD CONSTRAINT FK_C3C1A8E39395C3F3 FOREIGN KEY (customer_id) REFERENCES sylius_customer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE evolutive_wishlist_item ADD CONSTRAINT FK_D52FA11AFB8E54CD FOREIGN KEY (wishlist_id) REFERENCES evolutive_wishlist (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE evolutive_wishlist_item ADD CONSTRAINT FK_D52FA11A3B69A9AF FOREIGN KEY (variant_id) REFERENCES sylius_product_variant (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE evolutive_wishlist_item DROP FOREIGN KEY FK_D52FA11AFB8E54CD');
        $this->addSql('ALTER TABLE evolutive_wishlist_item DROP FOREIGN KEY FK_D52FA11A3B69A9AF');
        $this->addSql('ALTER TABLE evolutive_wishlist DROP FOREIGN KEY FK_C3C1A8E372F5A1AA');
        $this->addSql('ALTER TABLE evolutive_wishlist DROP FOREIGN KEY FK_C3C1A8E39395C3F3');
        $this->addSql('DROP TABLE evolutive_wishlist_item');
        $this->addSql('DROP TABLE evolutive_wishlist');
    }
}
