<?php

declare(strict_types=1);

namespace ZM\Exception\Solution;

class Solution
{
    public function __construct(
        private string $title,
        private string $description,
        private array $links,
    ) {}

    public function getSolutionTitle(): string
    {
        return $this->title;
    }

    public function getSolutionDescription(): string
    {
        return $this->description;
    }

    public function getDocumentationLinks(): array
    {
        return $this->links;
    }
}
