# Wishlist

Lets shoppers, guests or logged-in customers, save purchasable products to come back to later, separately from the cart.

## Language

### Wishlist

**Wishlist**:
A named, saved list of product variants that belongs to one Wishlist Owner within one Channel. An owner can have several (e.g. "Birthday", "Christmas"); names are required and never blank.
_Avoid_: Favorites, saved items, list

**Wishlist Item**:
One product variant inside a Wishlist. A variant appears at most once per Wishlist, but may be in several Wishlists.
_Avoid_: Entry, line, wishlisted product

**Destination**:
A Wishlist the shopper picks when adding a product from a product page or listing. There is no implicit default Wishlist.
_Avoid_: Current wishlist, default wishlist

**Move**:
Copying the selected variants into one or more Destinations, skipping those already there, then removing them from the source Wishlist.
_Avoid_: Transfer

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
At login, each Guest Wishlist becomes one of the Customer's Wishlists. If the Customer already has one with the same name (case-insensitive) in that Channel, the guest's variants are added to it instead and the Guest Wishlist is deleted.
_Avoid_: Conversion

### Lifecycle

**Guest TTL**:
How long a Guest Wishlist may go without changes before it expires.

**Purge**:
Deleting Guest Wishlists not modified within the Guest TTL, which mainly removes Orphaned Guest Wishlists.

### Availability

**Available for Purchase**:
A variant that can go into the cart right now in the current Channel: product and variant are enabled, the product is in the Channel, the variant has a price there, and it is in stock if stock is tracked.
_Avoid_: In stock, orderable, active
