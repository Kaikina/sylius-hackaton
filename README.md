# Sylius Wishlist Plugin

Lets shoppers, guests or logged-in customers, save products to come back to later, separately from the cart.

- **Several named wishlists per shopper** (e.g. "Birthday", "Christmas"), created, renamed and deleted from `/{_locale}/wishlists`, also linked from the customer account menu and the header.
- **Heart on product cards and product pages** opens a picker listing the shopper's wishlists: ticking a wishlist adds the variant to it, unticking removes it, and a new wishlist can be created inline. A shopper's very first wishlist, created from there, receives the product straight away. Products with several variants first ask which variant. The heart is filled when the product is in any wishlist.
- **Wishlist page** (`/{_locale}/wishlists/{id}`): select items to add them to the cart (they stay in the wishlist), remove them, or move them to other wishlists. The "Move to…" modal can create a wishlist on the spot; items already in a destination are skipped, so no duplicate is ever created. Items that are no longer available for purchase are flagged, and adding them to the cart is refused with an explanation.
- **Guest wishlists.** They are tied to the session, like the guest cart. At login, each guest wishlist joins the customer's wishlists, merged into the one with the same name if it exists.
- **Private.** Every lookup is scoped to the current owner and channel; another shopper's wishlist answers 404.
- **Read-only admin** under Customers › Wishlists: a grid (name, owner or "Guest", product count, searchable by customer name or email) and a details page listing each product with its image, variant and channel price. Admins cannot create, edit or delete wishlists.
- **Extensible through events**: wishlists and items are Sylius resources, and every change dispatches `evolutive_sylius_wishlist.wishlist[_item].pre_*|post_*` events (see [ADR 0003](docs/adr/0003-wishlist-as-sylius-resource-driven-by-events.md)).
- **Purge command** deleting guest wishlists left orphaned once their session is gone.
- English and French translations.

The domain vocabulary is defined in [GLOSSARY.md](GLOSSARY.md), and design decisions are recorded in [docs/adr](docs/adr).

## Requirements

- PHP 8.2+
- Sylius 2.x (shop built on Twig Hooks and Symfony UX Live Components)

## Installation

1. Require the plugin. It is not published on Packagist, so declare the repository first:

    ```bash
    composer config repositories.evolutive-wishlist vcs https://github.com/Kaikina/sylius-hackaton.git
    composer require evolutive/sylius-wishlist-plugin:dev-main
    ```

2. Register the bundle in `config/bundles.php`:

    ```php
    Evolutive\SyliusWishlistPlugin\EvolutiveSyliusWishlistPlugin::class => ['all' => true],
    ```

3. Import the configuration, for example in `config/packages/evolutive_sylius_wishlist.yaml`:

    ```yaml
    imports:
        - { resource: "@EvolutiveSyliusWishlistPlugin/config/config.yaml" }
    ```

4. Import the routes, for example in `config/routes/evolutive_sylius_wishlist.yaml`:

    ```yaml
    evolutive_sylius_wishlist_shop:
        resource: "@EvolutiveSyliusWishlistPlugin/config/routes/shop.yaml"

    evolutive_sylius_wishlist_admin:
        resource: "@EvolutiveSyliusWishlistPlugin/config/routes/admin.yaml"
    ```

5. Run the migrations. The plugin registers its own migrations, which create the `evolutive_wishlist` and `evolutive_wishlist_item` tables (Stage 1 wishlists without a name are renamed "My Favorites"):

    ```bash
    bin/console doctrine:migrations:migrate -n
    ```

6. Clear the cache. The shop hooks (header counter, product card and product page toggles) are registered automatically.

## Configuration

```yaml
evolutive_sylius_wishlist:
    # Days a guest wishlist may go without changes before the purge command deletes it.
    guest_ttl: 30 # default, minimum 1
```

## Purging guest wishlists

A guest loses access to their wishlist when their session ends. The database row stays behind, so schedule the purge, e.g. daily:

```bash
bin/console sylius:wishlist:purge-guest
```

| Option      | Description                                              |
|-------------|----------------------------------------------------------|
| `--ttl=N`   | Overrides the configured `guest_ttl`, in days            |
| `--dry-run` | Only counts the wishlists that would be deleted          |

Customer wishlists are never purged.

## Development

The repository ships a Sylius test application (`sylius/test-application`).

### Docker

```bash
make init            # install dependencies
make database-init   # create the database and run migrations
make load-fixtures   # optional
```

The shop is then available at `http://localhost`.

### Local

Configure the database credentials in `tests/TestApplication/.env` and `tests/TestApplication/.env.test`, then:

```bash
(cd vendor/sylius/test-application && yarn install && yarn build)
vendor/bin/console assets:install
vendor/bin/console doctrine:database:create
vendor/bin/console doctrine:migrations:migrate -n
vendor/bin/console sylius:fixtures:load -n   # optional

symfony server:ca:install
symfony server:start -d
```

Without the Symfony CLI, use PHP's built-in server with the bundled router, otherwise product thumbnails (`/media/cache/resolve/...`) answer 404:

```bash
php -S 127.0.0.1:8000 -t vendor/sylius/test-application/public tests/TestApplication/router.php
```

On MariaDB, set the real server version in `tests/TestApplication/.env.local`, otherwise Doctrine assumes MySQL 5.7 and migration commands fail:

```dotenv
DATABASE_URL="mysql://user:pass@127.0.0.1/db_%kernel.environment%?serverVersion=10.11.14-MariaDB&charset=utf8mb4"
```

### Quality checks

```bash
vendor/bin/phpunit
vendor/bin/behat --strict --tags="~@javascript&&~@mink:chromedriver"
vendor/bin/phpstan analyse -c phpstan.neon -l max src/
vendor/bin/ecs check
```

## License

MIT, see [LICENSE](LICENSE).
