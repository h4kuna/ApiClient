<?php declare(strict_types=1);

namespace MirkoHuttner\ApiClient\Endpoint;

interface IEndpoint
{
	public function getUrl(): string;

	public function getMethod(): string;

	public function getAuthType(): string;

	public function getEntityClassName(): ?string;

	public function isArray(): bool;

	public function getData(): ?array;

	public function getAdditionalQueryData(): ?array;

	public function isBinary(): bool;

	public function hasCount(): bool;
}
