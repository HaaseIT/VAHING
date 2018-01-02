<?php declare(strict_types=1);

namespace HaaseIT\VAHI;


use Symfony\Component\Yaml\Yaml;

class HelperConfig
{
    /**
     * @var array
     */
    protected $core;

    public function __construct()
    {
        $this->loadCore();
    }

    private function loadCore()
    {
        $core = Yaml::parse(file_get_contents(PATH_CONFIG.DIRECTORY_SEPARATOR.'core.yml'));

        $this->core = $core;
    }

    public function getCore(string $setting): ?string
    {
        return !empty($this->core[$setting]) ? $this->core[$setting] : null;
    }
}
