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
	public function match(Item $item, Cart $cart): bool;


	/**
	 *
	 */
	public function apply(Item $item, Cart $cart, array $context = array());
}
