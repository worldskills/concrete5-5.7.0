<?php
namespace Concrete\Core\Database;

use Concrete\Core\Application\Application;
use Concrete\Core\Cache\Adapter\DoctrineCacheDriver;
use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Package\Package;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Config;
use Database;

class DatabaseManagerORM
{

    /**
     * The application instance.
     *
     * @var Application
     */
    protected $app;

    /**
     * The active entity manager instances.
     * 
     * @var Doctrine\ORM\EntityManager[]
     */
    protected $entityManagers = array();

    /**
     * Create a new database manager instance.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Return all of the available entity managers.
     *
     * @return \Doctrine\ORM\EntityManager[]
     */
    public function getEntityManagers()
    {
        return $this->entityManagers;
    }

    /**
     * Gets a context specific entity manager. Allows easier management of
     * entities where different settings than the core settings are needed
     * for the EntityManager object.
     * 
     * @param mixed $context
     * @param string $name
     * @return \Doctrine\ORM\EntityManager
     */
    public function entityManager($context = null, $connectionName = null)
    {
        if ($connectionName === null) {
            $connectionName = Database::getDefaultConnection();
        }
        $name = $connectionName . '_';
        if ($context instanceof Package) {
            $name .= 'pkg_' . $context->getPackageHandle();
        } else {
            $name .= 'core';
        }
        if (!isset($this->entityManagers[$name])) {
            $this->entityManagers[$name] = static::makeEntityManager(Database::connection($connectionName), $context);
        }
        return $this->entityManagers[$name];
    }

    /**
     * Makes a new entity manager instance for the given context object
     * (e.g. a package) or if no context object is given, for the core context.
     * 
     * @param Connection $connection
     * @param mixed      $context
     * @return \Doctrine\ORM\EntityManager
     */
    public static function makeEntityManager(Connection $connection, $context = null)
    {
        $config = Setup::createConfiguration(
            Config::get('concrete.cache.doctrine_dev_mode'),
            Config::get('database.proxy_classes'),
            new DoctrineCacheDriver('cache/expensive')
        );

        // TODO: Once it is figured out how to properly use the entities in
        //       the core, this spot might need to be revisited.
        $path = DIR_BASE_CORE . '/' . DIRNAME_CLASSES;
        //$path = DIR_BASE_CORE . '/' . DIRNAME_CLASSES . '/' . DIRNAME_ENTITIES;
        if ($context instanceof Package) {
            $path = $context->getPackageEntitiesPath();
        } else if (is_object($context) && method_exists($context, 'getEntitiesPath')) {
            $path = $context->getEntitiesPath();
        }

        $driverImpl = $config->newDefaultAnnotationDriver($path);
        $config->setMetadataDriverImpl($driverImpl);
        if (is_object($context) && method_exists($context, 'modifyEntityManagerConfiguration')) {
            $context->modifyEntityManagerConfiguration($config);
        }
        return EntityManager::create($connection, $config);
    }

}
