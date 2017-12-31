<?php

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
        $core = Yaml::parse(file_get_contents(PATH_CONFIG.'core.yml'));

        $this->core = $core;
    }

    /**
     * @param string|false $setting
     * @return mixed
     */
    public function getCore($setting = false)
    {
        if (!$setting) {
            return $this->core;
        }

        return !empty($this->core[$setting]) ? $this->core[$setting] : false;
    }
}