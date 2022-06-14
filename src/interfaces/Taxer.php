<?php

namespace Trolly;

/**
 *
 */
interface Taxer
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
