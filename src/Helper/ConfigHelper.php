<?php

namespace Yeora\Helper;

use Exception;
use JsonException;
use RuntimeException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class ConfigHelper
{
    /**
     * @param string $path
     *
     * @return mixed|null
     */
    private static function getYamlConfig(string $path)
    {
        try {
            return Yaml::parseFile($path);
        } catch (ParseException $e) {
            OutputHelper::printError($e->getMessage());
        }

        return null;
    }

    /**
     * @param string $path
     *
     * @return mixed|null
     */
    private static function getJsonConfig(string $path)
    {
        $content = (string)file_get_contents($path);
        try {
            return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            OutputHelper::printError($e->getMessage());
        }

        return null;
    }

    /**
     * @return mixed|null
     */
    private static function getDefaultConfig()
    {
        $defaultConfigPath = getcwd() . '/DbSyncer'; // Default config
        $yamlConfig        = $defaultConfigPath . '.yaml';

        if (file_exists($yamlConfig)) {
            return self::getYamlConfig($yamlConfig);
        }

        $jsonConfig = $defaultConfigPath . '.json';

        if (file_exists($jsonConfig)) {
            return self::getJsonConfig($jsonConfig);
        }

        throw new RuntimeException('Please provide a valid config file');
    }

    /**
     * @param string|null $config
     *
     * @return mixed|null
     */
    public static function getConfig(?string $config = null)
    {
        if ($config && file_exists($config)) {
            $mimeType = mime_content_type($config);

            switch ($mimeType) {
                case 'text/plain':
                    $configArray = self::getYamlConfig($config);
                    break;
                case 'application/json':
                    $configArray = self::getJsonConfig($config);
                    break;
                default:
                    throw new RuntimeException('Please provide a valid config file');
            }
        } elseif ( ! $config) {
            $configArray = self::getDefaultConfig();
        } else {
            throw new RuntimeException('Please provide a valid config file');
        }

        return $configArray;
    }

}