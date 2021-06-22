<?php

namespace Trolly\Item;

/**
 *  This interface should be used to designate that a Item item has quantity information
 *  that can be retrieved or set.
 */
interface Quantifiable
{
	const PRICE_ITEM_QUANTITY = 1073741824;

	/**
	 * Get the quantity of the item to be purchased.
	 *
	 * @return int The quantity of the item to be purchased
	 */
	public function getItemQuantity(): int;


	/**
	 *
	 * @return Quantifiable returns the item for method chaining
	 */
	public function setItemQuantity(int $quantity): Quantifiable;
}
