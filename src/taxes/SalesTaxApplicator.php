<?php
namespace Trolly;

class SalesTaxApplicator implements TaxApplicator
{
	const LABEL = 'Sales Tax';
	const RATE  = 0.0875;


	/**
	 *
	 */
	public function match(Item $item): bool
	{
		return $item instanceof Item\Taxable;
	}


	/**
	 *
	 */
	public function apply(Item $item, Cart $cart)
	{
		return $item->setTaxAmount(self::LABEL, $item->getPrice($cart) * self::RATE);
	}
}
