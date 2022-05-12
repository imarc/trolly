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
	public function getItemMinimum(): int;


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
	public function getQualifiedItemDiscount(Item\Discountable $item, Cart $cart): float;


	/**
	 *
	 */
	public function isPercentDiscount(): bool;
}
