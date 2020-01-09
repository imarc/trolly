<?php

namespace Trolly;

/**
 * A pricer is an object which can be registered with a cart to determine the price of items
 * added in the cart.
 *
 * When the cart needs to determine the price of an item, it will loop through the registered
 * pricers and call the `match()` method.  If the `match()` method returns `TRUE`, the `price()`
 * method will be used to determine the price of the item.
 */
interface Pricer
{
	/**
	 * Determine whether or not this pricer is capable of pricing a Item item.
	 *
	 * @return bool TRUE if this pricer can price the item, FALSE otherwise
	 */
	public function match(Item $item): bool;


	/**
	 * Determine the price of an item.
	 *
	 * @return float The price of the item
	 */
	public function price(Item $item, Cart $cart, int $flags = 0): float;
}
