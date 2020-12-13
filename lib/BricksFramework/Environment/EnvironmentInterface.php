<?php

/** @copyright Sven Ullmann <kontakt@sumedia-webdesign.de> **/

namespace BricksFramework\Environment;

use Composer\Autoload\ClassLoader;

interface EnvironmentInterface
{
    public function getEnvironmentDirectory() : string;
    public function getApplicationDirectory() : string;
    public function getAutoloader() : ClassLoader;
    public function getDefaultEnvironment() : string;
    public function getCurrentEnvironment() : string;
    public function getData() : array;
    public function getEnvironments() : array;
    public function getProductiveEnvironments() : array;
    public function loadEnvironment(string $dotenvDirectory) : void;
}