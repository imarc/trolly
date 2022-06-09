<?php

namespace Trolly;

use RuntimeException;
use InvalidArgumentException;
use Trolly\Item\Discountable;

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
	protected $taxers = array();


	/**
	 *
	 */
	protected $normalizers = array();


	/**
	 *
	 */
	public function __construct(Storage $storage, $purchaser = NULL)
	{
		$this->storage   = $storage;
		$this->purchaser = $purchaser;
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
				$this->addItem($item);

			} elseif ($existing_item instanceof Item\Quantifiable && $item instanceof Item\Quantifiable) {
				$existing_item->setItemQuantity(
					$existing_item->getItemQuantity() + $item->getItemQuantity()
				);

			} else {
				throw new InvalidItemException('This item is already added to the cart.');

			}

		} else {
			$this->data['items'][] = $item;
		}

		return $this;
	}


	/**
	 *
	 */
	public function addPromotion(Promotion $promotion): Cart
	{
		$holds        = array();
		$throw        = array();
		$scrap        = array();
		$count        = 0;
		$items        = $promotion->getQualifiedItems($this, $holds, $throw);
		$promo_key    = $promotion->getPromotionKey();

		foreach ($items as $item) {
			while ($item) {
				$discount = $promotion->getQualifiedItemDiscount($item, $this);
				$price    = $this->price($item);

				if ($discount > $price) {
					$discount = $price;
				}

				if ($discount) {
					$item->setItemDiscount($promo_key, $discount * -1);
					$item = NULL;
					$count++;

				} else {
					array_unshift($scrap, $item);
					$item = array_shift($holds);
				}
			}
		}

		if (!$count) {
			throw new InvalidPromotionException(
				'No items qualified for discounts via this promotion.'
			);
		}

		$this->data['promotions'][] = $promotion;

		if ($promotion->getItemMinimum() > 1) {
			array_walk($holds, function ($item) use ($promo_key) {
				$item->setItemDiscount($promo_key, 0);
			});

			array_walk($scrap, function ($item) use ($promo_key) {
				$item->setItemDiscount($promo_key, 0);
			});
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
	public function getPricer(Item $item): Pricer
	{
		foreach ($this->pricers as $pricer) {
			if ($pricer->match($item)) {
				return $pricer;
			}
		}

		throw new RuntimeException(sprintf(
			'Cannot find pricer for item of type "%s", none registered',
			get_class($item)
		));
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
	public function getPurchaser()
	{
		return $this->purchaser;
	}


	/**
	 *
	 */
	public function getTaxApplicators(Item $item)
	{
		$taxers = array();

		foreach ($this->taxers as $taxer) {
			if ($taxer->match($item)) {
				$taxers[] = $taxer;
			}
		}

		return $taxers;
	}


	/**
	 *
	 */
	public function getTotal(): float
	{
		return $this->getTotalPrice() + $this->getTotalTax();
	}


	/**
	 *
	 */
	public function getTotalPrice(): float
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
	public function getTotalTax(): float
	{
		$total = 0;

		foreach ($this->data['items'] as $item) {
			$total += array_sum($item->getTaxAmounts());
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
						break;
					}
				}
			}
		}

		$this->data = $data;

		$this->refresh();

		return $this;
	}


	/**
	 *
	 */
	public function price(Item $item, int $flags = 0, array $context = array()): float
	{
		$pricer = $this->getPricer($item);
		$price  = $pricer->price($item, $this, $flags, $context);

		if ($item instanceof Item\Quantifiable && (empty($flags) || $flags & Item\Quantifiable::PRICE_ITEM_QUANTITY)) {
			$price = $price * $item->getItemQuantity();
		}

		if ($item instanceof Item\Discountable) {
			$discount_map = $item->getItemDiscountMap();

			foreach ($item->getItemDiscounts($this) as $key => $amount) {
				$promotion   = $this->getPromotion($key);
				$use_fixed   = $flags & $item::PRICE_ITEM_FIXED_DISCOUNT;
				$use_percent = $flags & $item::PRICE_ITEM_PERCENT_DISCOUNT;

				if (empty($flags)) {
					$price += $amount;

				} else {
					if ($promotion->isPercentDiscount() && !$use_percent) {
						continue;
					}

					if ($promotion->isFixedDiscount() && !$use_fixed) {
						continue;
					}

					if ($discount_map) {
						foreach ($discount_map as $flag => $types) {
							if (!($flags & $flag)) {
								continue;
							}

							if (!in_array($promotion->getPromotionType(), $types)) {
								continue;
							}

							$price += $amount;
						}

					} else {
						$price += $amount;
					}
				}
			}
		}

		return $price;
	}


	/**
	 *
	 */
	public function refresh(): Cart
	{
		uasort($this->data['items'], function ($a, $b) {
			return $a->getItemPriority() - $b->getItemPriority();
		});

		uasort($this->data['promotions'], function ($a, $b) {
			return $a->getPromotionType() - $b->getPromotionType();
		});

		$promotions = $this->getPromotions();

		if (count($promotions)) {
			$this->removePromotions();

			foreach ($promotions as $promotion) {
				try {
					$this->addPromotion($promotion);
				} catch (InvalidPromotionException $e) {
					continue;
				}
			}
		}

		foreach ($this->data['items'] as $item) {
			$taxers = $this->getTaxApplicators($item);

			foreach ($taxers as $taxer) {
				$taxer->apply($item, $this);
			}
		}

		return $this;
	}


	/**
	 *
	 */
	public function removeItems(string ...$keys): Cart
	{
		if (!count($keys)) {
			if (count($this->getItems())) {
				return $this->removeItems(...array_map(function ($item) {
					return $item->getItemKey();
				}, $this->getItems()));
			}

		} else {
			foreach ($keys as $key) {
				$item = $this->getItem($key);

				if (!$item) {
					throw new InvalidArgumentException('Invalid key specified for item removal');
				}
			}

			$this->data['items'] = $this->getItems(function ($item) use ($keys) {
				return !in_array($item->getItemKey(), $keys);
			});
		}

		return $this;
	}


	/**
	 *
	 */
	public function removePromotions(string ...$keys): Cart
	{
		if (!count($keys)) {
			if (count($this->getPromotions())) {
				return $this->removePromotions(...array_map(function ($promotion) {
					return $promotion->getPromotionKey();
				}, $this->getPromotions()));
			}

		} else {
			$discountable_items = $this->getItems(function($item) {
				return $item instanceof Discountable;
			});

			foreach ($keys as $key) {
				$promotion = $this->getPromotion($key);

				if (!$promotion) {
					throw new InvalidArgumentException('Invalid key specified for promotion removal');
				}

				foreach ($discountable_items as $discountable_item) {
					$discountable_item->setItemDiscount($key, NULL);
				}
			}

			$this->data['promotions'] = $this->getPromotions(function ($promotion) use ($keys) {
				return !in_array($promotion->getPromotionKey(), $keys);
			});
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
	public function setTaxApplicators(TaxApplicator ...$taxers)
	{
		$this->taxers = $taxers;
	}


	/**
	 *
	 */
	public function save(): Cart
	{
		$this->refresh();

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
}
