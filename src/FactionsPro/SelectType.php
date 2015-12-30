<?php

namespace FactionsPro;

use pocketmine\scheduler\PluginTask;
use FactionsPro\FactionMain;

class SelectType extends PluginTask {
	
    public $main;
    
    public function __construct(FactionMain $main) {
        $this->main = $main;
        parent::__construct($main);
    }
    
    public function onRun($currentTick) {
            foreach($this->main->block as $i=>$p){
                $player = $this->main->getServer()->getPlayer($p);
                if($player instanceof \pocketmine\Player){
                    $player->sendPopup(\pocketmine\utils\TextFormat::GREEN."Please Choose a Chest Type!");
                    $particle = new \pocketmine\level\particle\FlameParticle($player);
                    $particle->y++;
                    $particle2 = clone $particle;
                    $particle2->y++;
                    $particle3 = clone $particle;
                    $particle3->x++;
                    $particle4 = clone $particle;
                    $particle4->x--;
                    $particle5 = clone $particle;
                    $particle5->z++;
                    $particle6 = clone $particle;
                    $particle6->z--;
                    
                    $particle7 = clone $particle2;
                    $particle7->x++;
                    $particle8 = clone $particle2;
                    $particle8->x--;
                    $particle9 = clone $particle2;
                    $particle9->z++;
                    $particle10 = clone $particle2;
                    $particle10->z--;
                    
                    //$player->getLevel()->addParticle( $particle );
                    $player->getLevel()->addParticle($particle);
                    $player->getLevel()->addParticle($particle2);
                    $player->getLevel()->addParticle($particle3);
                    $player->getLevel()->addParticle($particle4);
                    $player->getLevel()->addParticle($particle5);
                    $player->getLevel()->addParticle($particle6);
                    $player->getLevel()->addParticle($particle7);
                    $player->getLevel()->addParticle($particle8);
                    $player->getLevel()->addParticle($particle9);
                    $player->getLevel()->addParticle($particle10);
                }else{
                    unset ($this->main->block[$i]);
                }
            }
    }
}