<?php

namespace Deep\FormTool\Core\InputTypes\Common;

interface IPluginableType
{
    public function plugin($plugin, $options = []);

    public function setDependencies($plugin);

    public function getPlugins();
}
