<?php

declare(strict_types=1);

namespace Yeora\Entity;

class SyncFrom extends Host
{
    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }
}