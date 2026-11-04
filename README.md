# Sylius Wishlist Plugin

Lets shoppers, guests or logged-in customers, save products to come back to later, separately from the cart.

- **Heart toggle on product cards.** A product with several variants opens a modal where each variant is toggled on its own.
- **Heart toggle on the product page.** It follows the variant currently selected in the add-to-cart form.
- **Header counter** linking to the wishlist page, refreshed live when the wishlist changes.
- **Wishlist page** (`/{_locale}/wishlist`), also linked from the customer account menu. From there, shoppers can add selected items to the cart, remove them, or clear the list. Items that are no longer available for purchase are flagged, and adding them to the cart is refused with an explanation.
- **Guest wishlists.** They are tied to the session, like the guest cart. At login, a guest's wishlist is adopted by the customer if they have none yet in that channel.
- **One wishlist per owner and channel.**
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
    ```

5. Run the migrations. The plugin registers its own migrations, which create the `evolutive_wishlist` and `evolutive_wishlist_item` tables:

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

### Quality checks

```bash
vendor/bin/phpunit
vendor/bin/behat --strict --tags="~@javascript&&~@mink:chromedriver"
vendor/bin/phpstan analyse -c phpstan.neon -l max src/
vendor/bin/ecs check
```

## License

MIT, see [LICENSE](LICENSE).
