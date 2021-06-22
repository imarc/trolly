<?php

namespace Trolly;

/**
 *
 */
interface Promotion
{
	const TYPE_FIXED   = 1;
	const TYPE_PERCENT = 2;

	/**
	 *
	 */
	public function getPromotionKey(): string;


	/**
	 *
	 */
	public function getPromotionType(): int;


	/**
	 *
	 */
	public function getQualifiedItems(Cart $cart, array &$holds = array(), array &$throws = array()): array;


	/**
	 *
	 */
	public function getQualifiedItemDiscount(Item $item, Cart $cart): float;
}
