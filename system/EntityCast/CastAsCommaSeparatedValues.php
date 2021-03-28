<?php

/**
 * This file is part of the CodeIgniter 4 framework.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CodeIgniter\EntityCast;

/**
 * Class CastAsCommaSeparatedValues
 */
class CastAsCommaSeparatedValues extends AbstractCast
{

	/**
	 * @inheritDoc
	 */
	public static function get($value, array $params = []): array
	{
		return explode(',', $value);
	}

	/**
	 * @inheritDoc
	 */
	public static function set($value, array $params = []): string
	{
		return implode(',', $value);
	}
}
