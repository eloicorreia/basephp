<?php

declare(strict_types=1);

namespace App\DTOs\Web;

final readonly class AdminMenuItemData
{
    /**
     * @param  list<array<string, mixed>>  $children
     */
    public function __construct(
        public string $code,
        public string $title,
        public ?string $routeName,
        public ?string $url,
        public ?string $icon,
        public int $order,
        public bool $active,
        public bool $opensInNewTab,
        public array $children,
    ) {}

    /**
     * @return array{code: string, title: string, route_name: string|null, url: string|null, icon: string|null, order: int, active: bool, opens_in_new_tab: bool, children: list<array<string, mixed>>}
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'title' => $this->title,
            'route_name' => $this->routeName,
            'url' => $this->url,
            'icon' => $this->icon,
            'order' => $this->order,
            'active' => $this->active,
            'opens_in_new_tab' => $this->opensInNewTab,
            'children' => $this->children,
        ];
    }
}
