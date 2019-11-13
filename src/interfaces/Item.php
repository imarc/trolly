<?php

namespace Trolly;

/**
 * An item is any object that can be added to the cart.
 */
interface Item
{
	/**
	 * Get the group to which this item belongs.
	 */
	public function getItemGroup(): string;


	/**
	 * Get the unique key for this item.  Items with the same key are considered duplicates.
	 */
	public function getItemKey(): string;


	/**
	 * Get the priority in which this item should be processed relative to other items.
	 */
	public function getItemPriority(): int;
}
