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
class ItemPricer
{
	/**
	 * Determine whether or not this pricer is capable of pricing an Item.
	 *
	 * @return bool TRUE if this pricer can price the item, FALSE otherwise
	 */
	public function match(Item $item): bool
	{
		return $item instanceof Item\Priced;
	}


	/**
	 * Determine the price of an Item.
	 *
	 * @return float The price of the item
	 */
	public function price(Item $item, Cart $cart): float
	{
		return $item->getPrice($cart);
	}
}
