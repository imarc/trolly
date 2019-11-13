<?php

namespace Trolly;

/**
 * Session storage provides storing of the cart in session
 */
class SessionStorage implements Storage
{
	/**
	 *
	 */
	public function load(): array
	{
		return $_SESSION[__NAMESPACE__] ?? [];
	}

	/**
	 *
	 */
	public function save(array $data): bool
	{
		$_SESSION[__NAMESPACE__] = $data;

		return TRUE;
	}
}
