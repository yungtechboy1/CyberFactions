<?php

namespace FactionsPro;

use pocketmine\scheduler\PluginTask;
use FactionsPro\FactionMain;
use pocketmine\level\Level;

class FactionTask extends PluginTask {
	
    public $main;
    
    public function __construct(FactionMain $main) {
        $this->main = $main;
        parent::__construct($main);
    }
    
    public function onRun($currentTick) {
            //$onlinePlayers = $this->getOwner()->getServer()->getOnlinePlayers();
            //$this->getOwner()->plotChecker($onlinePlayers);
            $this->main->chache = array();
            $this->main->death = array();
            foreach ($this->main->getServer()->getLevels() as $level) {
                foreach ($level->getEntities() as $e){
                    if($e instanceof \pocketmine\Player)break;
                    $e->kill();
                }
            }
            if($this->main->restart++ >= 15)$this->main->getServer ()->shutdown ();
    }
}