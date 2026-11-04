# Wishlists are Sylius resources, and every change goes through resource events

Stage 2 lets an owner keep several named wishlists. `Wishlist` and `WishlistItem` are registered as Sylius resources (`evolutive_sylius_wishlist.wishlist`, `evolutive_sylius_wishlist.wishlist_item`), and the shop CRUD (index, show, create, rename, delete) is served by Sylius' `ResourceController` through route configuration, not by a hand-written controller. Repository methods take the owner (channel, customer, guest token) as arguments, resolved by route expressions from `evolutive_sylius_wishlist.context.wishlist`. Another visitor's wishlist id is therefore never found and answers 404.

Side effects live in event listeners instead of in controllers or services:

- `ResourceController` dispatches `evolutive_sylius_wishlist.wishlist.pre_create|post_create|pre_update|post_delete|…` itself.
- `WishlistModifier` dispatches the same `ResourceControllerEvent` names for items (`evolutive_sylius_wishlist.wishlist_item.pre_create|post_create|pre_delete|post_delete`), whatever the entry point: product page and listing pickers, move, clear, login adoption. A listener can stop a `pre_*` event to refuse the change.
- Bulk actions on the wishlist page dispatch domain events (`WishlistItemsMovedEvent`, `WishlistItemsRemovedEvent`, `WishlistClearedEvent`, `WishlistItemsAddedToCartEvent`) that the flash listener reports.

| Listener | Event | Effect |
| --- | --- | --- |
| `WishlistOwnerListener` | wishlist `pre_create` | assigns channel and owner (customer, or guest token) |
| `WishlistOwnerListener` | wishlist `post_create` / `post_delete` | resets the per-request wishlist cache |
| `WishlistableVariantListener` | item `pre_create` | refuses variants not enabled, sold and priced in the channel |
| `WishlistTouchListener` | item `pre_create` / `pre_delete`, wishlist `pre_update` | refreshes `updatedAt` (used by the guest purge) |
| `WishlistFlashListener` | domain events above | flash messages on the wishlist page |

Item bulk actions (add to cart, remove, move, clear) are not CRUD, so they are small `final` invokable controllers sharing one Symfony form (`WishlistItemsType`), whose choices are limited to the source wishlist's items and the owner's other wishlists.

## Consequences

- An application can react to any wishlist change, or veto it, by listening to the resource event names, without decorating plugin services.
- The "Move to…" modal renders the `targets` field of the page form from a live component, so wishlists created inside the modal show up without leaving the page, and the page form still submits them.
- Adding from a product page or listing always asks for a Destination; there is no implicit default wishlist anymore.
