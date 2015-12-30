<?php

namespace FactionsPro;

use pocketmine\scheduler\PluginTask;
use FactionsPro\FactionMain;
use pocketmine\math\Vector3;
use pocketmine\block\Block;
use pocketmine\level\Level;


class GlowingTNT extends PluginTask {
	
    public $main;
    public $pos;
    public $level;
    public $tick;
    
    public function __construct(FactionMain $main, Vector3 $pos, Level $level, $tick = 20) {
        $this->main = $main;
        $this->pos = $pos;
        $this->level = $level;
        $this->tick = $tick;
        parent::__construct($main);
    }
    
    public function onRun($currentTick) {
        if($this->tick > 0){
            //Every 4 Ticks
            $this->tick--;
            $particle = new \pocketmine\level\particle\SmokeParticle($newpos);
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
            
            $this->level->addParticle($particle);
            $this->level->addParticle($particle2);
            $this->level->addParticle($particle3);
            $this->level->addParticle($particle4);
            $this->level->addParticle($particle5);
            $this->level->addParticle($particle6);
            $this->level->addParticle($particle7);
            $this->level->addParticle($particle8);
            $this->level->addParticle($particle9);
            $this->level->addParticle($particle10);
            $this->main->getServer()->getScheduler()->scheduleDelayedTask(new GlowingTNT($this->main, $this->pos, $this->level, $this->tick), 5);
            return true;
        }
        for ($x = -5; $x <= 5; $x++) {
            for ($y = -5; $y <= 5; $y++) {
                for ($z = -5; $z <= 5; $z++) {
                    $newpos = clone $this->pos;
                    $newpos->add($x, $y, $z);
                    $particle = new \pocketmine\level\particle\ExplodeParticle($newpos);
                    $this->level->addParticle($particle);
                    $block = $this->level->getBlock($newpos);
                    if($x < 3 && $y < 3 && $z < 3 && $x > -3 && $y > -3 && $z > -3 ){
                        $this->level->dropItem($newpos, \pocketmine\item\Item::get($block->getId()));
                    }
                    $this->level->setBlock($newpos, new Block(0), true);
                }
            }   
        }
        $this->level->addSound(new \pocketmine\level\sound\LaunchSound($this->pos));
    }
}