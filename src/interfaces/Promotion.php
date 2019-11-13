<?php

namespace Trolly;

/**
 *
 */
interface Promotion
{
	const LEVEL_CART = 1;
	const LEVEL_ITEM = 2;

	/**
	 *
	 */
	public function discount(Item\Discountable $item, Cart $cart): float;


	/**
	 *
	 */
	public function getPromotionKey(): string;


	/**
	 *
	 */
	public function getPromotionScope(): int;
}
