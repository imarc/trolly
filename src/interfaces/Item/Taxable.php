<?php

namespace Trolly\Item;

use Trolly;

/**
 *  This interface should be used to designate if an item has tax information
 *  that can be retrieved or set.
 */
interface Taxable extends Trolly\Item
{
	/**
	 *
	 */
	public function isTaxable(Trolly\Cart $cart): bool;


	/**
	 * Return the amount of tax for a specific tax.  Returns null
	 * if not found.
	 */
	public function getTaxAmount($key): ?float;


	/**
	 *
	 */
	public function getTaxAmounts(): array;


	/**
	 *
	 */
	public function setTaxAmount($label, $amount);
}
