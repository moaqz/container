<?php

declare(strict_types=1);

namespace Moaqz\Container;

use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionUnionType;

class Container implements ContainerInterface
{
  private array $entries = [];

  public function get(string $id)
  {
    if ($this->has($id)) {
      $entry = $this->entries[$id];
      return $entry($this);
    }

    return $this->resolve($id);
  }

  public function has(string $id): bool
  {
    return isset($this->entries[$id]);
  }

  /**
   * Bind an entry to the container.
   * 
   * @param string $id Identifier of the entry to bind.
   * @param callable $cb The callable that returns the entry.
   */
  public function bind(string $id, callable $cb)
  {
    $this->entries[$id] = $cb;
  }

  /**
   * Resolve an entry.
   * 
   * @param string $id Identifier of the entry to resolve.
   * @return mixed The resolved entry.
   * @throws ContainerException If the entry cannot be resolved.
   */
  private function resolve(string $id): object
  {
    $reflectionClass = new ReflectionClass($id);

    /**
     * Identifier belongs to an abstract class, trait or interface.
     */
    if (!$reflectionClass->isInstantiable()) {
      throw new ContainerException("class {$id} is not instantiable");
    }

    $constructor = $reflectionClass->getConstructor();

    /**
     * Class does not have any dependencies to resolve.
     */
    if (!$constructor) {
      return $reflectionClass->newInstance();
    }

    $parameters = $constructor->getParameters();
    if (!$parameters) {
      return $reflectionClass->newInstance();
    }

    $dependencies = $this->resolveDependencies($id, $parameters);
    return $reflectionClass->newInstance(...$dependencies);
  }

  /**
   * Resolve dependencies of the entry.
   * 
   * @param string $id Identifier of the entry.
   * @param array $parameters Parameters of the constructor.
   * @return array The resolved dependencies.
   * @throws ContainerException If a dependency cannot be resolved.
   */
  private function resolveDependencies(string $id, array $parameters): array
  {
    $dependencies = [];

    foreach ($parameters as $param) {
      $type = $param->getType();
      $parameterName = $param->getName();

      if (!$param->hasType()) {
        throw new ContainerException(
          "Failed to resolve class {$id} because parameter {$parameterName} has no type hint",
        );
      }

      if (
        $type instanceof ReflectionUnionType ||
        $type instanceof ReflectionIntersectionType
      ) {
        throw new ContainerException(
          "Failed to resolve class {$id} because type is not allowed"
        );
      }

      if ($type instanceof ReflectionNamedType && $type->isBuiltin()) {
        throw new ContainerException(
          "Failed to resolve class {$id} because type is not allowed"
        );
      }

      array_push(
        $dependencies,
        $this->get($type->getName()),
      );
    }

    return $dependencies;
  }
}
