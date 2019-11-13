<?php

namespace Trolly\Item;

/**
 *
 */
interface Discountable
{


	/**
	 * Clear item discounts on this item.
	 *
	 * @return int The number of discounts removed from this item
	 */
	public function clearItemDiscounts(): int;


	/**
	 * Get item discounts on this item.
	 *
	 * @return array The discount amounts applied to this item, keyed by their promotion ID.
	 */
	public function getItemDiscounts(): array;


	/**
	 * Set a discount on the item.
	 *
	 * @return Discountable The item instance for method chaining
	 */
	public function setItemDiscount(string $id, float $amount): Discountable;
}
