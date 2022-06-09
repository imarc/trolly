<?php

namespace Trolly;

/**
 *
 */
interface TaxApplicator
{
	/**
	 *
	 */
	public function match(Item\Taxable $item): bool;


	/**
	 *
	 */
	public function apply(Item $item, Cart $cart, array $context = array());
}
