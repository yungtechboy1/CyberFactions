<?php

namespace FactionsPro;

use pocketmine\scheduler\PluginTask;
use FactionsPro\FactionMain;

class FactionTask extends PluginTask {
	
    public $main;
    
    public function __construct(FactionMain $main) {
        $this->main = $main;
        parent::__construct($main);
    }
    
    public function onRun($currentTick) {
            $onlinePlayers = $this->getOwner()->getServer()->getOnlinePlayers();
            $this->getOwner()->plotChecker($onlinePlayers);
            $this->main->chache = array();
    }
}