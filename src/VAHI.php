<?php

namespace HaaseIT\VAHI;


use Zend\ServiceManager\ServiceManager;

class VAHI
{
    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * @var HelperConfig
     */
    protected $config;

    public function __construct($publicdir)
    {
        define('PATH_PUBLIC', $publicdir);
        define('PATH_BASE', dirname(PATH_PUBLIC));
        define('PATH_SRC', PATH_BASE.DIRECTORY_SEPARATOR.'src');
        define('PATH_CONFIG', PATH_BASE.DIRECTORY_SEPARATOR.'config');
        define('PATH_VIEWS', PATH_SRC.DIRECTORY_SEPARATOR.'views');
        define('PATH_CACHE', PATH_BASE.DIRECTORY_SEPARATOR.'cache');
        define('PATH_CACHE_TEMPLATES', PATH_CACHE.DIRECTORY_SEPARATOR.'templates');
    }

    public function init()
    {
        $this->serviceManager = new ServiceManager();

        $this->setupRequest();

        $this->serviceManager->setFactory('config', function () {
            return new HelperConfig();
        });
        $this->config = $this->serviceManager->get('config');

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

    protected function setupRequest()
    {
        // PSR-7 Stuff
        // Init request object
        $this->serviceManager->setFactory('request', function () {
            $request = \Zend\Diactoros\ServerRequestFactory::fromGlobals();

            // cleanup request
            $requesturi = urldecode($request->getRequestTarget());
            $parsedrequesturi = substr($requesturi, strlen(dirname(filter_input(INPUT_SERVER, 'PHP_SELF'))));
            if (substr($parsedrequesturi, 1, 1) !== '/') {
                $parsedrequesturi = '/'.$parsedrequesturi;
            }
            return $request->withRequestTarget(urlencode($parsedrequesturi));
        });
    }

    /**
     * @return ServiceManager
     */
    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    public function gatherPageData()
    {
        $requesturi = urldecode($this->serviceManager->get('request')->getRequestTarget());

        $currentPath = realpath(PATH_PUBLIC.$requesturi);

        if (substr($currentPath, 0, strlen(PATH_PUBLIC)) != PATH_PUBLIC) {
            die('404'); // todo
        }

        $entries = $this->getCurrentDirectoryEntries($currentPath);
        $entries = $this->cleanupDirectoryEntries($entries, $requesturi);
        $entries = $this->sortDirectoryEntries($entries, $currentPath);

        return $entries;
    }

    protected function sortDirectoryEntries($nodes, $currentPath)
    {
        foreach ($nodes as $node) {
            if (is_dir($currentPath.DIRECTORY_SEPARATOR.$node)) {
                $directories[] = $node;
            }

            if (is_file($currentPath.DIRECTORY_SEPARATOR.$node)) {
                if (getimagesize($currentPath.DIRECTORY_SEPARATOR.$node)) {
                    $images[] = $node;
                } else {
                    $files[] = $node;
                }
            }
        }

        if (!empty($directories) && is_array($directories)) {
            natsort($directories);
        }
        if (!empty($files) && is_array($files)) {
            natsort($files);
        }
        if (!empty($images) && is_array($images)) {
            natsort($images);
        }

        return ['directories' => $directories, 'files' => $files, 'images' => $images];
    }

    protected function cleanupDirectoryEntries($nodes, $requesturi)
    {
        $cleanNodes = [];
        foreach ($nodes as $node) {
            if ($requesturi == '/' && $node == '..') {
                continue;
            }

            if ($node != '..' && substr($node, 0, 1) == '.') {
                continue;
            }

            $cleanNodes[] = $node;
        }

        return $cleanNodes;
    }

    protected function getCurrentDirectoryEntries($currentPath)
    {
        $nodes = [];
        if ($handle = opendir($currentPath)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != '.') {
                    $nodes[] = $entry;
                }
            }

            closedir($handle);
        }

        return $nodes;
    }
}