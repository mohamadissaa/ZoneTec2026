{{--
    Two-tier desktop header:

      1. main bar     - logo, search, wishlist/cart/account
      2. category nav - top-level categories with hover mega-menu

    Account controls (sign in/up for guests, profile+logout dropdown for
    signed-in customers) live inline in the main bar next to the cart.
--}}
<div class="flex w-full flex-col max-lg:hidden">
    <x-shop::layouts.header.desktop.bottom />

    <x-shop::layouts.header.desktop.nav />
</div>
