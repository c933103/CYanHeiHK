<?php

namespace App;

use Pimple\Container as BaseContainer;

class Container extends BaseContainer
{
    private $commands;

    public function addCommand($key, $value)
    {
        $this->offsetSet($key, $value);
        $this->commands[$key] = true;
    }

    public function addContainerAwareCommands(array $classNames)
    {
        foreach ($classNames as $fqcn) {
            $name = '_cmd_' . (count($this->commands) + 1);
            $instance = new $fqcn($this);
            $this->addCommand($name, $instance);
        }
    }

    public function getCommands()
    {
        $commands = [];
        foreach ($this->commands as $key => $_) {
            $commands[] = $this->offsetGet($key);
        }

        return $commands;
    }
}