# Wishlist Items reference ProductVariant, not Product

A Wishlist Item points to a ProductVariant, because the cart only accepts variants and "Available for Purchase" (price, stock) is defined per variant. So adding to the cart from the wishlist never needs to ask which variant, and duplicates are checked per variant (sizes S and M of the same shirt are two items).

## Consequences

- Product listing tiles have no variant selector. For a product with several variants, the heart opens a modal where each variant is toggled on its own; a product with one variant is toggled directly.
- The listing heart is filled when *any* variant of the product is in the Wishlist.
- On the product page, the heart follows the variant currently selected in the add-to-cart form.
