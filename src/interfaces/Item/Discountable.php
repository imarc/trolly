<?php

namespace Trolly\Item;

/**
 *
 */
interface Discountable
{
	const PRICE_ITEM_DISCOUNT = 2147483648;

	/**
	 * Get item discounts on this item.
	 *
	 * @return array The discount amounts applied to this item, keyed by their promotion ID.
	 */
	public function getItemDiscounts($non_zero_only): array;


	/**
	 * Set a discount on the item.
	 *
	 * @return Discountable The item instance for method chaining
	 */
	public function setItemDiscount(string $id, ?float $amount): Discountable;
}
