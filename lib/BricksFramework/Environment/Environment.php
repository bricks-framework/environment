<?php

/** @copyright Sven Ullmann <kontakt@sumedia-webdesign.de> **/

declare(strict_types=1);

namespace BricksFramework\Environment;

use Symfony\Component\Dotenv\Dotenv;
use Composer\Autoload\ClassLoader;

class Environment implements EnvironmentInterface
{
    /** @var string */
    protected $environmentDirectory;

    /** @var string */
    protected $applicationDirectory;

    /**
     * @var ClassLoader
     */
    protected $autoloader;

    protected $environments = [];

    protected $productiveEnvironments = [];

    /**
     * @var Dotenv
     */
    protected $dotenv;

    /**
     * @var string
     */
    protected $currentEnviroment;

    protected $data = [];

    public function __construct(ClassLoader $autoloader)
    {
        $this->autoloader = $autoloader;
        $this->dotenv = new Dotenv();
    }

    public function getAutoloader() : ClassLoader
    {
        return $this->autoloader;
    }

    protected function setCurrentEnvironment(string $env) : void
    {
        if (!in_array($env, $this->environments)) {
            throw new Exception\InvalidEnvironmentException('the given environment ' . $env . ' does not exists');
        }
        $this->currentEnviroment = $env;
    }

    public function getCurrentEnvironment() : string
    {
        return $this->currentEnviroment;
    }

    public function getData() : array
    {
        return $this->data;
    }

    public function getEnvironments() : array
    {
        return $this->environments;
    }

    public function getProductiveEnvironments() : array
    {
        return $this->productiveEnvironments;
    }

    public function getDefaultEnvironment() : string
    {
        return current($this->productiveEnvironments);
    }

    /**
     * @throws Exception\CouldNotFindEnvFilesException
     */
    public function loadEnvironment(string $dotenvDirectory) : void
    {
        $this->environmentDirectory = realpath($dotenvDirectory);

        $filesToLoad = $this->getFilesToLoad($dotenvDirectory);

        foreach ($filesToLoad as $file) {
            $this->data = array_merge($this->data, $this->dotenv->parse(file_get_contents($file), basename($file)));
        }

        $this->applicationDirectory = realpath($dotenvDirectory . DIRECTORY_SEPARATOR .
            $this->data['BRICKS_APPLICATION_DIRECTORY'] ?? 'application');

        $this->environments = isset($this->data['BRICKS_APPLICATION_ENVIRONMENTS'])
            ? explode(',', $this->data['BRICKS_APPLICATION_ENVIRONMENTS']) : ['prod'];
        $this->productiveEnvironments = isset($this->data['BRICKS_APPLICATION_PRODUCTIVE_ENVIRONMENTS'])
            ? explode(',', $this->data['BRICKS_APPLICATION_PRODUCTIVE_ENVIRONMENTS']) : ['prod'];

        $currentEnviroment = $this->data['BRICKS_APPLICATION_ENVIRONMENT'] ?? 'prod';
        $this->setCurrentEnvironment($currentEnviroment);

        if ($this->data['BRICKS_ENABLE_CUSTOM_CODE']) {
            set_include_path(
                get_include_path() . PATH_SEPARATOR .
                $this->applicationDirectory . DIRECTORY_SEPARATOR . 'custom'
            );
        }
    }

    public function getEnvironmentDirectory() : string
    {
        return $this->environmentDirectory;
    }

    public function getApplicationDirectory() : string
    {
        return $this->applicationDirectory;
    }

    /**
     * @throws Exception\CouldNotFindEnvFilesException
     */
    protected function getFilesToLoad(string $dotenvDirectory) : array
    {
        $files = glob(realpath($dotenvDirectory) . '/.*');

        $envFile = $this->extract($files, '.env.bricks');
        $envFileLocal = $this->extract($files, '.env.bricks.local');
        $envFileTest = $this->extract($files, '.env.bricks.test');

        if (empty($envFile)) {
            throw new Exception\CouldNotFindEnvFilesException('there are no environment files');
        }

        $filesToLoad = [$envFile];

        if (!empty($envFileTest)) {
            array_push($filesToLoad, $envFileTest);
        }
        if (!empty($envFileLocal) && empty($envFileTest)) {
            array_push($filesToLoad, $envFileLocal);
        }

        return $filesToLoad;
    }

    protected function extract(array $files, string $extractEnvFile) : string
    {
        foreach ($files as $file) {
            if (basename($file) == $extractEnvFile) {
                return $file;
            }
        }
        return '';
    }
}
