<?php

declare(strict_types=1);

namespace ZM\Adapters\OneBot11;

use ZM\Adapters\AdapterInterface;

class OneBot11Adapter implements AdapterInterface
{
    use OneBot11IncomingTrait;
    use OneBot11OutgoingTrait;

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'onebot';
    }

    /**
     * {@inheritDoc}
     */
    public function getVersion(): string
    {
        return '11';
    }
}
