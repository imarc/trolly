<?php

namespace Trolly;

/**
 *
 */
interface Promotion
{
	/**
	 *
	 */
	public function getPromotionKey(): string;


	/**
	 *
	 */
	public function getQualifiedItems(Cart $cart, array &$holds = array(), array &$throws = array()): array;


	/**
	 *
	 */
	public function getQualifiedItemDiscount(Item $item, Cart $cart): float;
}
