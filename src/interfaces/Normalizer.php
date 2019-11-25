<?php

namespace Trolly;

/**
 *
 */
interface Normalizer
{
	public function match($value): bool;

	public function denormalize(array $value);

	public function normalize($value): array;
}
