<?php

declare(strict_types=1);

namespace ZM\Entity;

class InputArguments
{
    private $arguments;

    public function __construct(array $arguments)
    {
        $this->arguments = $arguments;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getArgument($name)
    {
        return $this->arguments[$name] ?? null;
    }

    public function get($name)
    {
        return $this->getArgument($name);
    }
}
