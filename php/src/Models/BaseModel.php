<?php

declare(strict_types=1);

namespace SusuDigital\Models;

/**
 * Base model with shared serialisation helpers.
 *
 * All models accept a plain associative array in their constructor and
 * expose a toArray() / toJson() method for outbound payloads.
 */
abstract class BaseModel
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        $this->hydrate($data);
    }

    /**
     * Populate properties from an associative array.
     * Converts camelCase keys → snake_case to match PHP conventions.
     *
     * @param array<string, mixed> $data
     */
    protected function hydrate(array $data): void
    {
        foreach ($data as $key => $value) {
            $property = $this->camelToSnake($key);
            if (property_exists($this, $property)) {
                $this->$property = $value;
            } elseif (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Serialize to a plain associative array (null values excluded).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [];
        foreach (get_object_vars($this) as $key => $value) {
            if ($value === null) {
                continue;
            }
            if ($value instanceof BaseModel) {
                $result[$key] = $value->toArray();
            } elseif (is_array($value)) {
                $result[$key] = array_map(
                    static fn ($v) => $v instanceof BaseModel ? $v->toArray() : $v,
                    $value,
                );
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * Serialize to JSON.
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static($data);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function camelToSnake(string $key): string
    {
        return strtolower(preg_replace('/[A-Z]/', '_$0', lcfirst($key)) ?? $key);
    }
}
