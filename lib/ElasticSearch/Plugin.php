<?php

/**
 * Class ElasticSearch_Plugin
 *
 * @author      Michal Maszkiewicz
 * @package     Elastic Search Plugin
 */

use ElasticSearch\Event\EventManager as DocumentEventManager;
use ElasticSearch\Job\CacheAllPagesJob;
use ElasticSearch\PluginConfig\ConfigDistFilePath;
use ElasticSearch\PluginConfig\ConfigFilePath;
use ElasticSearch\Repository\PageRepositoryFactory;



class ElasticSearch_Plugin extends Pimcore_API_Plugin_Abstract implements Pimcore_API_Plugin_Interface
{

    public function init()
    {
        $config = new Zend_Config_Xml(new ConfigFilePath());
        $repositoryFactory = new PageRepositoryFactory();
        $pageRepository = $repositoryFactory->build($config);

        $documentEventManager = new DocumentEventManager(
            Pimcore::getEventManager(),
            $pageRepository,
            new CacheAllPagesJob($pageRepository)
        );

        $documentEventManager->attachPostDelete();
        $documentEventManager->attachPostUpdate();
        // $documentEventManager->attachMaintenance();
    }

    public static function install()
    {
        if (self::isInstalled()) {
            return true;
        }

        $configPath = new ConfigFilePath();

        if (! is_writable($configPath->getDirectory())) {

            throw new RuntimeException(
                'Unable to write to config directory: ' . $configPath->getDirectory()
            );
        }

        if (copy(new ConfigDistFilePath(), $configPath)) {

            return true;
        }

        throw new RuntimeException('Unable to create a config file: ' . $configPath);
    }

    public static function uninstall()
    {
        if (self::isInstalled()) {

            unlink(new ConfigFilePath());

        }

        return true;
    }

    public static function isInstalled()
    {
        $configPath = new ConfigFilePath();

        if (file_exists($configPath)) {

            if (is_writable($configPath)) {

                // Consider installed as config file exists and is writable.
                return true;
            }

            throw new RuntimeException('Config file exists, but is not writable: ' . $configPath);

        }

        return false;
    }
}
