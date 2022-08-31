<?php

namespace Yeora\Entity;

use Symfony\Component\Console\Question\Question;
use Yeora\Helper\AskHelper;

abstract class Host
{
    public ?string $hostname = null;
    public ?int $port = null;
    public ?string $username = null;
    public ?string $password = null;
    public ?string $database = null;
    protected ?string $type = 'Host';

    /**
     * @var mixed[]|null
     */
    public ?array $tables = null;
    /**
     * @var mixed[]|null
     */
    public ?array $conditions = null;
    /**
     * @var mixed[]|null
     */
    public ?array $limits = null;

    /**
     * @var mixed[]|null
     */
    public ?array $config = null;

    /**
     * @param mixed[] $data
     */
    public function __construct(array $data = [])
    {
        $tables     = $data['tables'] ?? [];
        $conditions = $data['conditions'] ?? [];
        $limits     = $data['limits'] ?? [];
        $config     = $data['config'] ?? [];

        foreach ($data['credentials'] as $key => $val) {
            $key = 'set' . ucfirst($key);
            if (method_exists(__CLASS__, $key)) {
                $this->$key($val);
            }
        }

        $this->setTables($tables);
        $this->setConditions($conditions);
        $this->setLimits($limits);
        $this->setConfig($config);
    }


    public function checkMissingHostCredentials(): void
    {
        $eol = PHP_EOL;

        if ( ! $this->getUsername()) {
            $question = new Question(
                sprintf(
                    PHP_EOL . '<comment>Please enter the USERNAME for your %s Host (Host=%s Database=%s): </comment>',
                    $this->type,
                    $this->getHostname(),
                    $this->getDatabase()
                )
            );
            $question->setHidden(false);
            $question->setHiddenFallback(false);
            $username = AskHelper::ask($question);
            $this->setUsername(($username));
            $eol = null;
        }

        if ( ! $this->getPassword()) {
            $question = new Question(
                sprintf(
                    $eol . '<comment>Please enter the PASSWORD for your %s Host (Host=%s Database=%s User=%s): </comment>',
                    $this->type,
                    $this->getHostname(),
                    $this->getDatabase(),
                    $this->getUsername()
                )
            );
            $question->setHidden(true);
            $question->setHiddenFallback(false);
            $password = AskHelper::ask($question);
            $this->setPassword(($password));
        }
    }

    /**
     * @return ?string
     */
    public function getHostname(): ?string
    {
        return $this->hostname;
    }

    /**
     * @param ?string $hostname
     */
    public function setHostname(?string $hostname): void
    {
        $this->hostname = $hostname;
    }

    /**
     * @return ?int
     */
    public function getPort(): ?int
    {
        return $this->port;
    }

    /**
     * @param ?int $port
     */
    public function setPort(?int $port): void
    {
        $this->port = $port;
    }

    /**
     * @return ?string
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * @param ?string $username
     */
    public function setUsername(?string $username): void
    {
        $this->username = $username;
    }

    /**
     * @return ?string
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @param ?string $password
     */
    public function setPassword(?string $password): void
    {
        $this->password = $password;
    }

    /**
     * @return ?string
     */
    public function getDatabase(): ?string
    {
        return $this->database;
    }

    /**
     * @param ?string $database
     */
    public function setDatabase(?string $database): void
    {
        $this->database = $database;
    }

    /**
     * @return mixed[]|null
     */
    public function getTables(): ?array
    {
        return $this->tables;
    }

    /**
     * @param mixed[]|null $tables
     */
    public function setTables(?array $tables): void
    {
        $this->tables = $tables;
    }

    /**
     * @return mixed[]|null
     */
    public function getConditions(): ?array
    {
        return $this->conditions;
    }

    /**
     * @param mixed[]|null $conditions
     */
    public function setConditions(?array $conditions): void
    {
        $this->conditions = $conditions;
    }

    /**
     * @return mixed[]|null
     */
    public function getLimits(): ?array
    {
        return $this->limits;
    }

    /**
     * @param mixed[]|null $limits
     */
    public function setLimits(?array $limits): void
    {
        $this->limits = $limits;
    }

    /**
     * @return mixed[]
     */
    public function getConfig(): array
    {
        return $this->config ?? [];
    }

    /**
     * @param mixed[]|null $config
     */
    public function setConfig(?array $config): void
    {
        $this->config = $config;
    }

}