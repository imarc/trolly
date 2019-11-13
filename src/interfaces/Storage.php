<?php

namespace Trolly;

/**
 * The storage interface provides
 */
interface Storage
{
	/**
	 *
	 */
	public function load(): array;

	/**
	 *
	 */
	public function save(array $data): bool;
}
