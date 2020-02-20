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
	protected $normalizers = array();


	/**
	 *
	 */
	public function __construct(Storage $storage)
	{
		$this->storage = $storage;
	}


	/**
	 *
	 */
	public function addItem(Item $item, $replace_if_exists = FALSE): Cart
	{
		$existing_item = $this->getItem($item->getItemKey());

		if ($existing_item) {
			if ($replace_if_exists) {
				$this->removeItems($item->getItemKey());

				$this->data['items'][] = $item;

			} elseif ($item instanceof Quantifiable) {
				$existing_item->setItemQuantity(
					$existing_item->getItemQuantity() + $item->getItemQuantity()
				);

			} else {
				throw new InvalidItemException('This item is already added to the cart.');

			}

		} else {
			$this->data['items'][] = $item;
		}

		$this->refresh();

		if (!$this->getItem($item->getItemKey())) {
			throw new InvalidItemException('The item could not be added at this time.');
		}

		return $this;
	}


	/**
	 *
	 */
	public function addPromotion(Promotion $promotion): Cart
	{
		$existing_promotion = $this->getPromotion($promotion->getPromotionKey());

		if ($existing_promotion) {
			throw new InvalidPromotionException('The promotion could not be added at this time.');
		} else {
			$this->data['promotions'][] = $promotion;
		}

		$this->refresh();

		if (!$this->getPromotion($promotion->getPromotionKey())) {
			throw new InvalidPromotionException('This promotion does not apply to anything.');
		}

		return $this;
	}


	/**
	 *
	 */
	public function getItem(string $key): ?Item
	{
		foreach ($this->getItems() as $item) {
			if ($item->getItemKey() == $key) {
				return $item;
			}
		}

		return NULL;
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
	public function getPromotion(string $key)
	{
		foreach ($this->getPromotions() as $promotion) {
			if ($promotion->getPromotionKey() == $key) {
				return $promotion;
			}
		}

		return NULL;
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
		$data = $this->storage->load() + [
			'items'      => [],
			'promotions' => []
		];

		foreach ($data as $key => $values) {
			foreach ($values as $index => $value) {
				foreach ($this->normalizers as $normalizer) {
					if ($normalizer->match($value)) {
						$data[$key][$index] = $normalizer->denormalize($value);
					}
				}
			}
		}

		$this->data = $data;

		return $this;
	}


	/**
	 *
	 */
	public function price(Item $item, int $flags = 0, array $context = array())
	{
		$price = 0;

		foreach ($this->pricers as $pricer) {
			if ($pricer->match($item)) {
				$price = $pricer->price($item, $this, $flags, $context);
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
			$this->data['items'] = $this->getItems(function($item) use ($keys) {
				return !in_array($item->getItemKey(), $keys);
			});

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
	public function setNormalizers(Normalizer ...$normalizers)
	{
		$this->normalizers = $normalizers;
	}


	/**
	 *
	 */
	public function setPricers(Pricer ...$pricers)
	{
		$this->pricers = $pricers;
	}


	/**
	 *
	 */
	public function save(): Cart
	{
		$data = $this->data;

		foreach ($data as $key => $values) {
			foreach ($values as $index => $value) {
				foreach ($this->normalizers as $normalizer) {
					if ($normalizer->match($value)) {
						$data[$key][$index] = $normalizer->normalize($value);
					}
				}
			}
		}

		$this->storage->save($data);

		return $this;
	}


	/**
	 *
	 */
 	public function walk($callback): Cart
 	{
 		array_walk($this->data['items'], $callback, $this);

		return $this;
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
