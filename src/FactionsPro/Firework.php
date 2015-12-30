<?php

namespace FactionsPro;

use pocketmine\scheduler\PluginTask;
use FactionsPro\FactionMain;
use pocketmine\math\Vector3;

class Firework extends PluginTask {
	
    public $main;
    public $age;
    public $level;
    /**
     *
     * @var Vector3
     */
    public $pos;
    
    public function __construct(FactionMain $main, Vector3 $pos,\pocketmine\level\Level $level, $age = 0) {
        $this->main = $main;
        $this->age = $age;
        $this->pos = $pos;
        $this->level = $level;
        parent::__construct($main);
    }
    
    public function onRun($currentTick) {
            if($this->age < 10){
                $this->age++;
                $this->pos->y++;
                $this->main->getServer()->getScheduler()->scheduleDelayedTask(new Firework($this->main, $this->pos, $this->level, $this->age), 3);
                $particle = new \pocketmine\level\particle\SmokeParticle($this->pos, 1);
                $this->level->addParticle($particle);
                return true;
            }
            
                    $player->sendPopup(\pocketmine\utils\TextFormat::GREEN."Please Choose a Chest Type!");
                    $particle = new \pocketmine\level\particle\FlameParticle($this->pos);
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
                
    }
}