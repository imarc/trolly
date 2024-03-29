<?php

namespace Trolly\Item;

use Trolly;

/**
 *
 */
interface Discountable extends Trolly\Item
{
	const PRICE_ITEM_FIXED_DISCOUNT = 2147483648;
	const PRICE_ITEM_PERCENT_DISCOUNT = 4294967296;

	const PRICE_ITEM_DISCOUNT = (
		  self::PRICE_ITEM_FIXED_DISCOUNT
		+ self::PRICE_ITEM_PERCENT_DISCOUNT
	);


	/**
	 * Get item discounts on this item.
	 *
	 * @return array The discount amounts applied to this item, keyed by their promotion ID.
	 */
	public function getItemDiscounts(Trolly\Cart $cart, $flags = 0, $non_zero_only = FALSE): array;


	/**
	 * Set a discount on the item.
	 *
	 * @return Discountable The item instance for method chaining
	 */
	public function setItemDiscount(string $id, ?float $amount): Discountable;


	/**
	 * A discount map provides a way to map specific pricing flags to specific promotion types.
	 *
	 * @return array The discount map for the item
	 */
	public function getItemDiscountMap(): array;
}
