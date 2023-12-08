<?php
/*
 * PepipostLib
 *
 * This file was automatically generated by APIMATIC v2.0 ( https://apimatic.io ).
 */

namespace PepipostLib;

use PepipostLib\Controllers;

/**
 * PepipostLib client class
 */
class PepipostClient
{
    /**
     * Constructor with authentication and configuration parameters
     */
    public function __construct(
        $apiKey = null
    ) {
        Configuration::$apiKey = $apiKey ? $apiKey : Configuration::$apiKey;
    }
    /**
     * Singleton access to Send controller
     * @return Controllers\SendController The *Singleton* instance
     */
    public function getSend()
    {
        return Controllers\SendController::getInstance();
    }
}