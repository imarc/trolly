<?php

namespace Trolly;

use Psr\Cache\CacheItemPoolInterface;
use RuntimeException;

class Cart
{
	/**
	 *
	 */
	protected $cache = NULL;


	/**
	 *
	 */
	protected $data = array();


	/**
	 *
	 */
	protected $id = NULL;


	/**
	 *
	 */
	protected $pricers = array();


	/**
	 *
	 */
	public function __construct(Storage $storage, Pricer ...$pricers)
	{
		$this->storage = $storage;
		$this->pricers = $pricers;


		$this->load();
	}


	/**
	 *
	 */
	public function addItem(Item $item): Cart
	{
		$key = md5($item->getItemKey());

		if (isset($this->data['items'][$key])) {
			if (!$item instanceof Quantifiable) {
				throw new InvalidItemException('This item is already added to the cart.');
			}

			$item->setItemQuantity($this->data['items'][$key]->getItemQuantity() + 1);
		}

		$this->data['items'][$key] = $item;
		$this->refresh();

		if (!isset($this->data['items'][$key])) {
			throw new InvalidItemException('This item could not be added at this time.');
		}

		return $this;
	}


	/**
	 *
	 */
	public function addPromotion(Promotion $promotion): Cart
	{
		$key = md5(strtolower($promotion->getPromotionKey()));

		if (isset($this->data['promotions'][$key])) {
			throw new InvalidPromotionException('This promotion is already added to the cart.');
		}

		$this->data['promotions'][$key] = $promotion;
		$this->refresh();

		if (!isset($this->data['promotions'][$key])) {
			throw new InvalidPromotionException('This promotion does not apply to anything.');
		}

		return $this;
	}


	/**
	 *
	 */
	public function getItems(callable $filter = NULL): array
	{
		if (!$filter) {
			return $this->data['items'];
		}

		return array_filter($this->data['items'], $filter);
	}


	/**
	 *
	 */
	public function getItemGroups(array $context = array()): array
	{
		$groups = array();

		foreach ($this->data['items'] as $item) {
			$group = $item->getItemGroup($context);

			if (!isset($groups[$group])) {
				$groups[$group] = array();
			}

			$groups[$group][] = $item;
		}

		return $groups;
	}


	/**
	 *
	 */
	public function getPromotions(callable $filter = NULL): array
	{
		if (!$filter) {
			return $this->data['promotions'];
		}

		return array_filter($this->data['promotions'], $filter);
	}


	/**
	 *
	 */
	public function getTotal(): float
	{
		$total = 0;

		foreach ($this->data['items'] as $item) {
			$total = $total + $this->price($item);
		}

		return $total;
	}


	/**
	 *
	 */
	public function load(): Cart
	{
		$this->data = $this->storage->load() + [
			'items'      => [],
			'promotions' => []
		];

		return $this;
	}


	/**
	 *
	 */
	public function price(Item $item)
	{
		$price = 0;

		foreach ($this->pricers as $pricer) {
			if ($pricer->match($item)) {
				$price = $pricer->price($item, $this);
			}
		}

		if ($item instanceof Quantifiable) {
			$price = $price * $item->getItemQuantity();
		}

		if ($item instanceof Discountable) {
			$price = $price + array_sum($item->getItemDiscounts());
		}

		return $price;
	}


	/**
	 *
	 */
	public function removeItems(string ...$keys): Cart
	{
		if (count($keys)) {
			foreach ($keys as $key) {
				unset($this->data['items'][md5($key)]);
			}

		} else {
			$this->data['items'] = array();

		}

		return $this;
	}


	/**
	 *
	 */
	public function removePromotions(string ...$keys): Cart
	{
		if (count($keys)) {
			foreach ($keys as $key) {
				unset($this->data['promotions'][md5($key)]);
			}

		} else {
			$this->data['promotions'] = array();
		}

		return $this;
	}


	/**
	 *
	 */
	public function save(): Cart
	{
		$this->storage->save($this->data);

		return $this;
	}


	/**
	 *
	 */
 	public function walkItems($callback): Cart
 	{
 		array_walk($this->data['items'], $callback, $cart);
 	}


	/**
	 *
	 */
	protected function refresh(): Cart
	{
		$applied_promotions = array();
		$discountable_items = $this->getItems(function($item) {
			return $item instanceof Discountable;
		});

		foreach ($discountable_items as $item) {
			$item->clearItemDiscounts();

			foreach ($this->data['promotions'] as $key => $promotion) {
				$discount = $promotion->discount($item, $this);
				$price    = $this->price($item);

				if ($discount == 0) {
					continue;
				}

				if ($price == 0) {
					continue;
				}

				if (abs($discount) > $this->price($item)) {
					$discount = $price;
				}

				$item->setItemDiscount($promotion->getPromotionKey(), $discount);

				if (!in_array($key, $applied_promotions)) {
					$applied_promotions[] = $key;
				}
			}
		}

		foreach (array_diff(array_keys($this->data['promotions']), $applied_promotions) as $key) {
			unset($this->data['promotions'][$key]);
		}

		uasort($this->data['items'], function($a, $b) {
			return $a->getItemPriority() - $b->getItemPriority();
		});

		return $this;
	}
}
