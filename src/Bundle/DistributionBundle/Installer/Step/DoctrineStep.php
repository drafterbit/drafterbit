<?php

namespace Drafterbit\Bundle\DistributionBundle\Installer\Step;

use Drafterbit\Bundle\DistributionBundle\Installer\Form\DoctrineStepType;
use Symfony\Component\Yaml\Yaml;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader as DataFixturesLoader;
use Doctrine\Bundle\DoctrineBundle\Command\DoctrineCommand;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\Tools\SchemaTool;

class DoctrineStep implements StepInterface {

    private $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function getFormType()
    {
        return new DoctrineStepType;
    }

    /**
     * @see StepInterface
     */
    public function getTemplate()
    {
        return 'DistributionBundle:Step:doctrine.html.twig';
    }

    /**
     * @return array
     */
    public static function getDrivers()
    {
        return array(
            'pdo_mysql' => 'MySQL (PDO)',
            'pdo_sqlite' => 'SQLite (PDO)',
            'pdo_pgsql' => 'PosgreSQL (PDO)',
            'oci8' => 'Oracle (native)',
            'ibm_db2' => 'IBM DB2 (native)',
            'pdo_oci' => 'Oracle (PDO)',
            'pdo_ibm' => 'IBM DB2 (PDO)',
            'pdo_sqlsrv' => 'SQLServer (PDO)',
        );
    }

    /**
     * @todo clean table name
     * @todo create generated secret key
     */
    public function process($data, $installer = null) {

        return $this->testConnectionFailed($data);
    }

    private function testConnectionFailed($param) {
         $connection  = $this->container->get('doctrine.dbal.connection_factory')
            ->createConnection($param);

        try  {
            $connection->connect();
            $connection->exec('USE '.$param['name']);
            return false;
        } catch (\Exception $e) {

            if($e instanceof \Doctrine\DBAL\DBALException) {
                $e = $e->getPrevious();
            }
            if($e instanceof \PDOException) {
                return $e->getMessage();
            }

            throw $e;
        }
    }

    public function isDone()
    {
        $installer  = $this->container->get('installer');

        $param = $installer->get('doctrine');
        $installedParam = [];
        $parametersPath = $this->container->getParameter('parameters_path');
        $temp = Yaml::parse(file_get_contents($parametersPath.'/parameters.yml'));

        if($temp){
            $parameters = $temp['parameters'];
            $installedParam['driver'] = $parameters['database_driver'];
            $installedParam['host'] = $parameters['database_host'];
            $installedParam['name'] = $parameters['database_name'];
            $installedParam['user'] = $parameters['database_user'];
            $installedParam['password'] = $parameters['database_password'];
            $installedParam['port'] = $parameters['database_port'];
            $installedParam['table_prefix'] = $parameters['database_table_prefix'];
            $installedParam['path'] = isset($parameters['database_path']) ? $parameters['database_path'] : null;
        }

        if($param == $installedParam) {
            return !$this->testConnectionFailed($param);
        }

        return false;
    }

    public function finalize()
    {
        $doctrine = $this->container->get('doctrine');
        $em = $doctrine->getManager();

        $this->createDatabaseSchema($em);
        $this->loadFixtures($em);
    }

    /**
     * Creat the database schema
     */
    public function createDatabaseSchema($em)
    {
        $metadatas = $em->getMetadataFactory()->getAllMetadata();

        if ( ! empty($metadatas)) {
            $schemaTool = new SchemaTool($em);
            $schemaTool->createSchema($metadatas);
        }
    }

    /**
     * Load data ficures
     */
    public function loadFixtures($em)
    {
        $paths = array();
        foreach ($this->container->get('kernel')->getBundles() as $bundle) {
            $paths[] = $bundle->getPath().'/DataFixtures/ORM';
        }

        $loader = new DataFixturesLoader($this->container);
        foreach ($paths as $path) {
            if (is_dir($path)) {
                $loader->loadFromDirectory($path);
            }
        }
        $fixtures = $loader->getFixtures();
        if (!$fixtures) {
            throw new InvalidArgumentException(
                sprintf('Could not find any fixtures to load in: %s', "\n\n- ".implode("\n- ", $paths))
            );
        }
        $purger = new ORMPurger($em);
        //$purger->setPurgeMode($input->getOption('purge-with-truncate') ? ORMPurger::PURGE_MODE_TRUNCATE : ORMPurger::PURGE_MODE_DELETE);
        $executor = new ORMExecutor($em, $purger);
        //$executor->setLogger(function($message) use ($output) {
        //    $output->writeln(sprintf('  <comment>></comment> <info>%s</info>', $message));
        //});
        $executor->execute($fixtures, TRUE);
    }
}