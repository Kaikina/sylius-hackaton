# Guest Token lives in the session; Purge removes Orphaned Guest Wishlists

The Guest Token is stored in the Symfony session, the same way Sylius keeps the guest cart, instead of in a long-lived cookie of our own. A guest loses access to their Wishlist when their session ends (browser closed, session expired). That is deliberate: it matches how the cart behaves and needs no cookie handling of our own. The Guest TTL and the Purge command exist to delete the Guest Wishlists left orphaned in the database, not to set how long a guest can use their list.

## Considered Options

- A dedicated cookie renewed on every change, so the list survives browser restarts for up to the TTL. Rejected for V1 to stay consistent with the guest cart.
