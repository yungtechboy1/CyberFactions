<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace FactionsPro;

use pocketmine\scheduler\PluginTask;
use pocketmine\Server;
use pocketmine\Player;


/**
 * Description of KickPlayer
 *
 * @author carlt_000
 */
class SetNameTeg extends PluginTask {
    
    private $main;
    private $player;
    private $tag;
    public function __construct(FactionMain $main,Player $player,$tag ) {
        $this->main = $main;
        $this->player = $player;
        $this->tag = $tag;
        parent::__construct ( $main );
    }
    
    public function onRun($t) {
        $this->player->setNameTag($this->tag);
        echo "\nNAME TAG SET =".$this->tag;
    }
    //put your code here
}
