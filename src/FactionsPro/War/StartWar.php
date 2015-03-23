<?php

namespace FactionsPro;

use pocketmine\scheduler\PluginTask;
use FactionsPro\FactionMain;

class StartWar extends PluginTask {
    
    public $main;
    public $attackers;
    public $defenders;


    public function __construct(FactionMain $main,$attackers, $defenders) {
        $this->main = $main;
        $this->defenders = $defenders;
        $this->attackers = $attackers;
        parent::__construct($main);
    }
    
    public function onRun($currentTick) {
        
        
    }
    
    public function functionName($param) {
        
    }
}