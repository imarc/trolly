<?php

namespace Trolly\Item;

use Trolly;

/**
 *  This interface should be used to designate that an item can supply its own price.
 */
interface Priced extends Trolly\Item
{
	/**
	 * Get the price of the item to be purchased.
	 *
	 * @var Cart The cart, in the event the price depends on other factors
	 * @return float The price of the item to be purchased
	 */
	public function getItemPrice(Trolly\Cart $cart): float;
}
