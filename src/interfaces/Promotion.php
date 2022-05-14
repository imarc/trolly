<?php

namespace Trolly;

/**
 *
 */
interface Promotion
{
	const TYPE_FIXED = 1;
	const TYPE_PERCENT = 2;

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
	public function isFixedDiscount(): bool;

	/**
	 *
	 */
	public function isPercentDiscount(): bool;
}
