<?php

namespace FactionsPro;

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
        $this->main->wars["ATTACKS"][$this->attackers] = strtotime("now");
        $this->main->wars["DEFENDS"][$this->defenders] = strtotime("now");
        
    }
    
    public function functionName($param) {
        
    }
}