<?php
namespace Trolly;

class SalesTaxer implements Taxer
{
	const LABEL = 'Sales Tax';
	const RATE  = 0.0875;


	/**
	 *
	 */
	public function match(Item $item, Cart $cart): bool
	{
		return $item instanceof Item\Taxable;
	}


	/**
	 *
	 */
	public function apply(Item $item, Cart $cart, array $context = array())
	{
		return $item->setTaxAmount(self::LABEL, $item->getPrice($cart) * self::RATE);
	}
}
