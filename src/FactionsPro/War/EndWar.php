<?php

namespace FactionsPro\War;

use pocketmine\scheduler\PluginTask;
use FactionsPro\FactionMain;

class EndWar extends PluginTask {
    
    public $main;
    public $fac;


    public function __construct(FactionMain $main,$facs) {
        $this->main = $main;
        $this->fac = $facs;
        parent::__construct($main);
    }
    
    public function onRun($currentTick) {
        unset($this->main->atwar[$facs]);
        
    }
    
    public function functionName($param) {
        
    }
}