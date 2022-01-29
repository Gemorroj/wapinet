<?php

namespace App\Entity\File;

class Meta
{
    /**
     * @var array<string, int|float|string|\Stringable|null>
     */
    private array $meta = [];

    public function has(string $key): bool
    {
        return isset($this->meta[$key]);
    }

    public function get(string $key): int|float|string|null|\Stringable
    {
        return $this->meta[$key] ?? null;
    }

    public function set(string $key, int|float|string|null|\Stringable $value): self
    {
        $this->meta[$key] = $value;

        return $this;
    }

    public function __toString(): string
    {
        $out = '';
        foreach ($this->meta as $key => $value) {
            $out .= $key.': '.$value."\n";
        }

        return $out;
    }
}
