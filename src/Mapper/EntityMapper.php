<?php declare(strict_types=1);

namespace MirkoHuttner\ApiClient\Mapper;

use MirkoHuttner\ApiClient\Entity\BaseEntity;
use MirkoHuttner\ApiClient\Exception\UnexpectedStructureReturnedException;
use Ramsey\Uuid\Uuid;

class EntityMapper
{
	/**
	 * @param mixed $data
	 */
	public static function create($data, string $entityClass, bool $isBinary = false): BaseEntity
	{
		if (is_string($data) && $isBinary) {
			return new $entityClass($data);
		} elseif (is_object($data)) {
			$rc = new \ReflectionClass($entityClass);
			try {
				$constructor = $rc->getMethod('__construct');
			} catch (\ReflectionException $exception) {
				$constructor = null;
			}

			$constructorParameters = [];
			if ($constructor) {
				foreach ($constructor->getParameters() as $parameter) {
					$name = $parameter->getName();
					$type = $parameter->getType();

					if (isset($data->$name)) {
						$constructorParameters[$parameter->getName()] = self::transformValue($data->$name, $type);
					} elseif ($type && $type->allowsNull()) {
						$constructorParameters[$parameter->getName()] = null;
					} elseif ($type) {
						if ($type->getName() === 'string') {
							$constructorParameters[$parameter->getName()] = '';
						} elseif ($type->getName() === 'string') {
							$constructorParameters[$parameter->getName()] = '';
						} elseif (in_array($type->getName(), ['int', 'integer'])) {
							$constructorParameters[$parameter->getName()] = 0;
						} elseif ($type->getName() === 'float') {
							$constructorParameters[$parameter->getName()] = 0.;
						} elseif (in_array($type->getName(), ['bool', 'boolean'])) {
							$constructorParameters[$parameter->getName()] = false;
						}
					}
				}

				$entity = new $entityClass(...array_values($constructorParameters));
			} else {
				$entity = new $entityClass();
			}

			foreach ($rc->getProperties() as $property) {
				$pn = $property->getName();
				if (!key_exists($pn, $constructorParameters) && isset($data->$pn)) {
					$val = self::transformValue($data->$pn, $property->getType());

					$mn = 'set' . ucfirst($pn);
					if (method_exists($entity, $mn)) {
						$entity->$mn($val);
					} elseif (property_exists($entity, $pn)) {
						$entity->$pn = $val;
					}
				}
			}

			return $entity;
		} else {
			throw new UnexpectedStructureReturnedException();
		}
	}


	/**
	 * @param mixed $val
	 * @return mixed
	 */
	private static function transformValue($val, ?\ReflectionNamedType $type)
	{
		if ($val instanceof \stdClass && !$type) {
			$val = (array) $val;
		} elseif ($type && is_string($val) && $type->getName() === 'Ramsey\Uuid\UuidInterface' && Uuid::isValid($val)) {
			$val = Uuid::fromString($val);
		} elseif ($val instanceof \stdClass && $type && $type->getName() === 'array') {
			$val = (array) $val;
		} elseif ($type !== null && is_a($type->getName(), \DateTimeInterface::class, true)) {
			$class = $type->getName();

			if (is_numeric($val)) {
				return (new $class("@$val"))->setTimezone(new \DateTimeZone(date_default_timezone_get()));
			} else if ($val instanceof \stdClass) {
				return new $class($val->date, new \DateTimeZone($val->timezone));
			}

			return new $class((string) $val);
		} elseif ($val instanceof \stdClass && $type) {
			$val = self::create($val, $type->getName());
		}

		return $val;
	}

}
