<?php declare(strict_types=1);

namespace MirkoHuttner\ApiClient\ValueObject;

class ResponseDataCount
{
	/**
	 * @var mixed
	 */
	public $data;

	public ?int $count;

	/**
	 * @param mixed $data
	 * @param int|null $count
	 */
	public function __construct($data, ?int $count = null)
	{
		$this->data = $data;
		$this->count = $count;
	}
}
