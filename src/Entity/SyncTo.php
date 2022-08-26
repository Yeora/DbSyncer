<?php

declare(strict_types=1);

namespace Yeora\Entity;

class SyncTo extends Host
{
    /**
     * @param mixed[] $data
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }
}