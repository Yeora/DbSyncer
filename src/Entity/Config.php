<?php

declare(strict_types=1);

namespace Yeora\Entity;

class Config
{
    /**
     * @var mixed[] $config
     */
    public array $config = [
        'compress' => 'None',
        'no-data' => false,
        'add-drop-table' => true,
        'single-transaction' => true,
        'lock-tables' => true,
        'add-locks' => true,
        'extended-insert' => true,
        'disable-foreign-keys-check' => true,
        'skip-triggers' => false,
        'add-drop-trigger' => true,
        'databases' => false,
        'add-drop-database' => true,
        'hex-blob' => true,
    ];

    /**
     * @param mixed[] $generalConfig
     * @param mixed[] $hostConfig
     */
    public function __construct(array $generalConfig = [], array $hostConfig = [])
    {
        $this->config = array_merge($this->config, $generalConfig);
        $this->config = array_merge($this->config, $hostConfig);
    }

    /**
     * @return mixed[]
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @param mixed[] $config
     */
    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

}