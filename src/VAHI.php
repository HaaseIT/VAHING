<?php declare(strict_types=1);

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

    /**
     * @var string
     */
    protected $requestUri;

    /** @var HelperDirectory */
    protected $helperDirectory;

    public function __construct(string $publicdir)
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

        $this->requestUri = urldecode($this->serviceManager->get('request')->getRequestTarget());

        $this->helperDirectory = new HelperDirectory($this->requestUri);
        try {
            $this->helperDirectory->init();
        } catch (\Exception $e) {
            die($e->getMessage()); // todo
        }
    }

    protected function setupTwig()
    {
        $this->serviceManager->setFactory('twig', function (): \Twig_Environment {
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

    public function getServiceManager(): ServiceManager
    {
        return $this->serviceManager;
    }

    public function gatherPageData(): array
    {
        return $this->helperDirectory->getCurrentDirectory();
    }
}
