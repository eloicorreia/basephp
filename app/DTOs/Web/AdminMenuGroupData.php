<?php

declare(strict_types=1);

namespace App\DTOs\Web;

final readonly class AdminMenuGroupData
{
    /**
     * @param  list<array<string, mixed>>  $items
     */
    public function __construct(
        public string $code,
        public string $title,
        public ?string $icon,
        public int $order,
        public array $items,
    ) {}

    /**
     * @return array{code: string, title: string, icon: string|null, order: int, items: list<array<string, mixed>>}
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'title' => $this->title,
            'icon' => $this->icon,
            'order' => $this->order,
            'items' => $this->items,
        ];
    }
}
