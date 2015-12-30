<?php

namespace FactionsPro;

use pocketmine\scheduler\PluginTask;
use FactionsPro\FactionMain;
use pocketmine\Player;
use pocketmine\math\Vector3;

class Teleport extends PluginTask {
	
    public $main,$player,$vec;
    
    public function __construct(FactionMain $main, Player $player, Vector3 $vec, $ff = false) {
        $this->main = $main;
        $this->player = $player;
        $this->vec = $vec;
        $this->ff = $ff;
        parent::__construct($main);
    }
    
    public function onRun($currentTick) {
        /*$chunk = $this->player->getLevel()->getChunk($this->vec->getX() >> 4, $this->vec->getZ() >> 4);
        if($chunk == null || !$chunk->isGenerated()){
            $this->player->sendMessage(\pocketmine\utils\TextFormat::RED."Chunks Could Not Be Loaded!\n Please Try Again!");
            return true;
        }*/
            $this->player->teleport($this->vec);
            $this->player->sendTip("Teleported!");
            $this->player->sendMessage(\pocketmine\utils\TextFormat::RED."Now Loading the Chunks! This Takes about 30 Secs!");
            if($ff = false)$this->main->getServer()->getScheduler()->scheduleDelayedTask(new Teleport($this->main,$this->player,$this->vec, true), 20 * 20);
    }
}