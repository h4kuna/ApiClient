<?php declare(strict_types=1);

namespace MirkoHuttner\ApiClient\RequestValue;

class BaseRequestValue
{
	public function toArray(): array
	{
		return get_object_vars($this);
	}
}
