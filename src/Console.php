<?php

namespace Ocean\Installer;

use Ocean\Installer\Commands\CreateCommand;
use Symfony\Component\Console\Application;

class Console extends Application
{
    public function __construct()
    {
        parent::__construct('Ocean Installer', '1.0.0');

        $this->add(new CreateCommand());
    }
}
