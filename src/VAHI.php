<?php

namespace HaaseIT\VAHI;


use Zend\ServiceManager\ServiceManager;

class VAHI
{
    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    public function __construct($publicdir)
    {
        define('PATH_PUBLIC', $publicdir);
        define('PATH_BASE', dirname(PATH_PUBLIC));
        define('PATH_SRC', PATH_BASE.DIRECTORY_SEPARATOR.'src');
        define('PATH_VIEWS', PATH_SRC.DIRECTORY_SEPARATOR.'views');
        define('PATH_CACHE', PATH_BASE.DIRECTORY_SEPARATOR.'cache');
        define('PATH_CACHE_TEMPLATES', PATH_CACHE.DIRECTORY_SEPARATOR.'templates');
    }

    public function init()
    {
        $this->serviceManager = new ServiceManager();

        $this->serviceManager->setFactory('config', function () {
            return new HelperConfig();
        });

        $this->setupTwig();
    }

    protected function setupTwig()
    {
        $this->serviceManager->setFactory('twig', function (ServiceManager $serviceManager) {
            $loader = new \Twig_Loader_Filesystem([PATH_VIEWS]);

            $twig_options = [
                'autoescape' => false,
            ];
            if ($this->config->getCore('templatecache_enable') &&
                is_dir(PATH_CACHE_TEMPLATES) && is_writable(PATH_CACHE_TEMPLATES)) {
                $twig_options['cache'] = PATH_CACHE_TEMPLATES;
            }
            $twig = new \Twig_Environment($loader, $twig_options);

            return $twig;
        });
    }
}