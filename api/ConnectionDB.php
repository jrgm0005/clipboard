<?php

use Exception;

include_once('Config.php');
include_once("Helper.php");

#This class is responsible for the connection with the Database.
class ConnectionDB
{

    #Declare our attribute that will receive the instance of database.
    public static $instance;
    private $config;

    private function __construct()
    {
    }

    #Create the method that will make the connection with the database and will set this connection in the attribute "instance".
    public static function getInstance(Config $config)
    {

        // Helper::saveInLog("LOADING DB");
        // Helper::saveInLog("CONFIG", $config);

        #Verify if the attribute already have a connection set in it.
        if (!isset(self::$instance)) {

            $configArray = $config->getConfigArray();
            #Create a new PDO object and make the connection with database.
            self::$instance = new PDO(
                'mysql:host=' . $configArray['host'] . ';dbname=' . $configArray['dbname'],
                $configArray['dbuser'],
                $configArray['dbpass'],
                array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")
            );

            self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$instance->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_EMPTY_STRING);

        }
        // Helper::saveInLog("DB LOADED SUCCESSFULLY");
        #Return the attribute with the connection setted in it.
        return self::$instance;

    }

}
