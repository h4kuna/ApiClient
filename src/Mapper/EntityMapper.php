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
						$val = $data->$name;
						if ($val instanceof \stdClass && !$type) {
							$val = (array)$val;
						} elseif ($type && is_string($val) && $type->getName() === 'Ramsey\Uuid\UuidInterface' && Uuid::isValid($val)) {
							$val = Uuid::fromString($val);
						} elseif ($val instanceof \stdClass && $type && $type->getName() === 'array') {
							$val = (array)$val;
						} elseif ($val instanceof \stdClass && $type) {
							$val = self::create($val, $type->getName());
						} elseif ($val && $type && $type->getName() === 'DateTime') {
							$val = new \DateTime($val);
						}
						$constructorParameters[$parameter->getName()] = $val;
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
					$val = $data->$pn;
					$type = $property->getType();
					if ($val instanceof \stdClass && !$type) {
						$val = (array)$data->$pn;
					} elseif ($type && $type->getName() === 'Ramsey\Uuid\UuidInterface' && Uuid::isValid($data->$pn)) {
						$val = Uuid::fromString($data->$pn);
					} elseif ($val instanceof \stdClass && $type && $type->getName() === 'array') {
						$val = (array)$data->$pn;
					} elseif ($val instanceof \stdClass && $type) {
						$val = self::create($data->$pn, $type->getName());
					} elseif ($val && $type && $type->getName() === 'DateTime') {
						$val = new \DateTime($val);
					}

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
}
