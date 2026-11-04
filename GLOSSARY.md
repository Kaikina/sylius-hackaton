# Wishlist

Lets shoppers, guests or logged-in customers, save purchasable products to come back to later, separately from the cart.

## Language

### Wishlist

**Wishlist**:
A saved list of product variants that belongs to one Wishlist Owner within one Channel.
_Avoid_: Favorites, saved items, list

**Wishlist Item**:
One product variant inside a Wishlist. A variant appears at most once per Wishlist.
_Avoid_: Entry, line, wishlisted product

**Current Wishlist**:
The Wishlist that add/remove actions target for the current visitor in the current Channel. In V1, every owner has exactly one Wishlist per Channel.
_Avoid_: Default wishlist, active wishlist

### Ownership

**Wishlist Owner**:
Who a Wishlist belongs to: either a Customer (logged in) or a Guest Token, never both.

**Customer Wishlist**:
A Wishlist owned by a Customer. Kept indefinitely.

**Guest Wishlist**:
A Wishlist owned by a Guest Token. The guest can reach it only while their session lasts; after that it is orphaned until Purged.
_Avoid_: Anonymous wishlist, session wishlist

**Guest Token**:
An opaque identifier, kept in the guest's session (like the guest cart), that identifies their Guest Wishlist.

**Orphaned Guest Wishlist**:
A Guest Wishlist whose session is gone, so nobody can reach it any more.

**Wishlist Adoption**:
At login, a Guest Wishlist becomes the Customer's Wishlist, but only if the Customer has none in that Channel. Otherwise the Guest Wishlist is discarded.
_Avoid_: Merge, conversion

### Lifecycle

**Guest TTL**:
How long a Guest Wishlist may go without changes before it expires.

**Purge**:
Deleting Guest Wishlists not modified within the Guest TTL, which mainly removes Orphaned Guest Wishlists.

### Availability

**Available for Purchase**:
A variant that can go into the cart right now in the current Channel: product and variant are enabled, the product is in the Channel, the variant has a price there, and it is in stock if stock is tracked.
_Avoid_: In stock, orderable, active
