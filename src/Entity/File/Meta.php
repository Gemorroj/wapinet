<?php

namespace App\Entity\File;

class Meta
{
    /**
     * @var array<string, int|float|string|\Stringable|\DateTimeInterface|array|null>
     */
    private array $meta = [];

    public function has(string $key): bool
    {
        return isset($this->meta[$key]);
    }

    public function get(string $key): int|float|string|\Stringable|\DateTimeInterface|array|null
    {
        return $this->meta[$key] ?? null;
    }

    public function set(string $key, int|float|string|\Stringable|\DateTimeInterface|array|null $value): self
    {
        $this->meta[$key] = $value;

        return $this;
    }

    public function __toString(): string
    {
        $out = '';
        foreach ($this->meta as $key => $value) {
            if ($value instanceof \DateTimeInterface) {
                $value = $value->format('r');
            } elseif (\is_array($value)) {
                $value = \json_encode($value, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT);
            }
            $out .= $key.': '.$value."\n";
        }

        return $out;
    }
}
