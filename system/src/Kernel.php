<?php namespace Drafterbit\System;

use Monolog\Logger;
use Drafterbit\Framework\Application;
use Drafterbit\System\Log\DoctrineDBALHandler;
use Drafterbit\System\Provider\WidgetServiceProvider;
use Drafterbit\System\Provider\ExtensionServiceProvider;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Kernel extends Application
{
    /**
     * Application admin navigation.
     *
     * @var array
     */
    protected $nav = array();

    /**
     * Permissions.
     *
     * @var array
     */
    protected $permissions = array();

    /**
     * Log Entity Labels.
     *
     * @var array
     */
    protected $logEntityLabels = array();
    
    /**
     * Application Fronpage options.
     *
     * @var array
     */
    protected $frontpage = array();

    public function __construct($environment = 'development', $debug = true)
    {
        parent::__construct($environment, $debug);

        $this->register(new ExtensionServiceProvider);
        $this->register(new WidgetServiceProvider);

        foreach (include $this->getResourcesPath('config/services.php')
            as $provider => $services) {
            $this->addDeferred($provider, $services);
        }
    }

    public function addNav($nav)
    {
        $this->nav = array_merge($this->nav, $nav);
    }

    public function getNav()
    {
        return $this->nav;
    }

    public function addPermission($extension, $permissions)
    {
        if (!isset($this->permissions[$extension])) {
            $this->permissions[$extension] = $permissions;
        } else {
            $this->permissions[$extension] = array_merge($this->permissions[$extension], $permissions);
        }
    }

    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * Return widget ui based on given position
     *
     * @param  string $position
     * @return string
     */
    public function widget($position)
    {
        $qb = $this['db']->createQueryBuilder();
        
        $widgets = $qb->select('*')
            ->from('#_widgets', 'w')
            ->where('position=:position')
            ->setParameter('position', $position)
            ->execute()->fetchAll();

        usort(
            $widgets,
            function($a, $b) {
                if ($a['sequence'] == $b['sequence']) {
                    return $a['id'] - $b['id'];
                }

                return $a['sequence'] < $b['sequence'] ? -1 : 1;
            }
        );

        $output = null;
        foreach ($widgets as $widget) {
            $output .=
            $this['widget']->get($widget['name'])->run(json_decode($widget['data'], true));
        }

        return $output;
    }

    /**
     * Return front end menus on given position
     *
     * @param  string $position
     * @return string
     */
    public function menus($position)
    {
        $qb = $this['db']->createQueryBuilder();
        
        $data = $qb->select('*')
            ->from('#_menus', 'm')
            ->where('position=:position')
            ->andWhere('theme=:theme')
            ->setParameter('position', $position)
            ->setParameter('theme', $this['themes']->current())
            ->getResult();

        usort(
            $data,
            function($a, $b) {
                if ($a['sequence'] == $b['sequence']) {
                    return $a['id'] - $b['id'];
                }

                return $a['sequence'] < $b['sequence'] ? -1 : 1;
            }
        );

        foreach ($data as &$item) {
            if ($item['type'] == 1) {
                $item['link'] = strtr($item['link'], array("%base_url%" => base_url()));
            } elseif ($item['type'] == 2) {
                $pages = $this->getFrontpage();
                $item['link'] = base_url($pages[$item['page']]['defaults']['slug']);
            }
        }

        return $data;
    }

    public function loadsystem()
    {
        try {
            $schema = $this['db']->getSchemaManager();
            
            if (!$schema->tablesExist('#_system')) {
                throw new InstallationException("No System Table", 2);
            }

            $this['extension.manager']->load('system');
            return $this->getExtension('system')->model('System')->all();

        } catch (\PDOException $e) {
            if (in_array($e->getCode(), ['1045', '1044','1046', '1049'])) {
                die('bad config :(');
            }

            throw $e;
        }
    }

    public function configure($configFile)
    {
        if (!file_exists($configFile)) {
            throw new InstallationException('No Config File', 1);
        }

        $config = $this['config']->load($configFile);

        $this['router']->addReplaces('%admin%', $config['path.admin']);

        $this['path.cache'] =  $this['path.content'].'cache/data';

        foreach ([
            'fontawesome' => 'Drafterbit\\System\\Asset\Filter\\DrafterbitFontAwesomeFilter',
            'chosen_css' => 'Drafterbit\\System\\Asset\Filter\\DrafterbitChosenFilter'
            ]
            as $name => $class) {
                $this['asset']->getFilterManager()->set($name, new $class($this['dir.system'].'/vendor/web'));
        }

        $system = $this->loadsystem();

        //language
        $this['translator']->setLocale($system['language']);

        //theme
        $theme = $system['theme'];

        $this['path.themes'] = $this['path.content'].'themes/';
        
        $this['themes']->current($theme);
        
        $this['themes']->registerAll();

        // add language catalogue
        if (is_dir($path = $this['path.themes'].$this['themes']->current().'/l10n')) {
            $this['translator']->addPath($path);
        }

        $this['path.theme'] = $this['path.themes'].$this['themes']->current().'/';

        $extensions = array();
        if ($system !== false) {
            $extensions = json_decode($system['extensions'], true);
        }

        foreach ($extensions as $extension => $version) {
            $this['extension.manager']->load($extension);
        }

        $this['widget']->registerAll();
        
        date_default_timezone_set($system['timezone']);

        if (! $this['debug']) {
            $this['exception']->error(
                function(NotFoundHttpException $e){
                
                    if (is_file($this['path.theme'].'404.html')) {
                        return $this['twig']->render('404.html');
                    }

                    return file_get_contents($this->getResourcesPath('views/404.html'));
                }
            );
        }

        $this['log.db'] = function(){
            $logger =  new Logger('db.log');
            $logger->pushHandler(new DoctrineDBALHandler($this['db']));
            return $logger;
        };

        $this['log.db']->pushProcessor(
            function ($record) {
                $record['formatted'] = "%message%";
                return $record;
            }
        );

        // base url
        $this['dispatcher']->addListener(
            'boot',
            function(){

                // frontpage
                $frontpage = $this->getFrontpage();
                $system = $this->getExtension('system')->model('@system\System')->all();
                $homepage = $system['homepage'];
                $route = $frontpage[$homepage];

                $this['router']->addRouteDefinition(
                    '/',
                    [
                    'controller' => $route['controller'],
                    'defaults' => $route['defaults']
                    ]
                );

                // pages
                $reservedBaseUrl = implode('|', $this->getReservedBaseUrl());
                $this['router']->addRouteDefinition(
                    '/{slug}',
                    [
                    'controller' => '@pages\Frontend::view',
                    'requirements' => [
                    // @prototype  'slug' => "^(?!(?:backend|blog)(?:/|$)).*$"
                    'slug' => "^(?!(?:%admin%|".$reservedBaseUrl."|)(?:/|$)).*$"
                    ]
                    ]
                );
            },
            -512
        );

        $this->addMiddleware('Drafterbit\\System\\Middlewares\\Security', array($this, $this['session'], $this['router']));
        $this->addMiddleware('Drafterbit\\System\\Middlewares\\Log', array($this));
    }

    public function getFrontpage()
    {
        $qb = $this['db']->createQueryBuilder();

        $pages = $qb->select('*')
            ->from('#_pages', 'p')
            ->execute()->fetchAll();

        $options = array();
        foreach ($pages as $page) {
            $options['pages:'.$page['id']] = [
                'label' => $page['title'],
                'controller' => '@pages\Frontend::home',
                'defaults' => ['id' => $page['id'], 'slug' => $page['slug']]
            ];
        }

        return array_merge($this->frontpage, $options);
    }

    public function addFrontPageOption($array)
    {
        $this->frontpage = array_merge($this->frontpage, $array);
    }

    public function getFrontPageOption()
    {
        $options = array();

        foreach ($this->getFrontpage() as $id => $param) {
            $options[$id] = $param['label'];
        }

        return $options;
    }

    public function getReservedBaseUrl()
    {
        $urls = array();
        foreach ($this->getExtensions() as $extension) {
            if (method_exists($extension, 'getReservedBaseUrl')) {
                $urls =  array_merge($urls, $extension->getReservedBaseUrl());
            }
        }

        return $urls;
    }

    public function setContentDir($dir)
    {
        $this['path.content'] = realpath($dir).'/';
        $this['config']->addReplaces('%content_dir%', $dir);
    }

    public function setConfigFile($file)
    {
        $this['config_file'] = $file;
    }

    public function run()
    {
        $this['dir.content']     = basename($this['path.content']);
        $this['dir.system']      = basename($this['path']);
        $this['path.extensions'] = $this['path.content'] . '/extensions';
        $this['path.install']    = $this['path.public'] =  getcwd().'/';

        // asset
        $this['config']->addReplaces('%path.vendor.asset%', $this['path'].'vendor/web');
        $this['config']->addReplaces('%path.system.asset%', $this['path'].'Resources/public/assets');

        $config = $this['config']['app'];
        $this['debug'] = $config['debug'];

        $this['exception']->setDebug($this['debug']);

        if ($config['error.log']) {
            $this['exception']
                ->error(
                    function(\Exception $exception, $code) {
                        $this['log']->addError($exception);
                    }
                );
        }

        $this['asset']->setCachePath($this['path.content'].'cache/asset');

        foreach ($this['config']->get('asset.assets') as $name => $value) {
            $this['asset']->register($name, $value);
        }

        try {
            $this->configure($this['config_file']);

        } catch (InstallationException $e) {
            
            $code = $e->getCode();
            $this['extension.manager']->load('installer');
            $this->getExtension('installer')->setStart($code);
        }

        parent::run();
    }

    function getLogEntityLabel($entity, $id)
    {
        return call_user_func_array($this->logEntityLabels[$entity], array($id));
    }

    function addLogEntityFormatter($entity, $callback)
    {
        $this->logEntityLabels[$entity] = $callback;
    }

    function dashboardWidgets()
    {
        $widgets = array();
        
        foreach ($this->getExtensions() as $extension) {
            if (method_exists($extension, 'dashboardWidgets')) {
                $widgets =  array_merge($widgets, $extension->dashboardWidgets());
            }
        }

        return $widgets;
    }

    function getStat()
    {
        $stat = array();
        
        foreach ($this->getExtensions() as $extension) {
            if (method_exists($extension, 'getStat')) {
                $stat =  array_merge($stat, $extension->getStat());
            }
        }

        return $stat;
    }
}
