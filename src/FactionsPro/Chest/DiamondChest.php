<?php

namespace FactionsPro\Chest;

use pocketmine\scheduler\PluginTask;
use FactionsPro\FactionMain;

class DiamondChest extends PluginTask {
	
    public $main;
    
    public function __construct(FactionMain $main, Player $player, $time) {
        $this->main = $main;
        parent::__construct($main);
    }
    
    public function onRun($currentTick) {
            foreach($this->main->block as $i=>$p){
                $player = $this->main->getServer()->getPlayer($p);
                if($player instanceof \pocketmine\Player){
                    $player->sendPopup(\pocketmine\utils\TextFormat::GREEN."Please Choose a Chest Type!");
                }else{
                    unset ($this->main->block[$i]);
                }
            }
    }
}