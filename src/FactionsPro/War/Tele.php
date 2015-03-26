<?php

namespace FactionsPro\War;

use FactionsPro\FactionMain;
use pocketmine\Player;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Tele
 *
 * @author carlt_000
 */
class Tele {
    
    private $main;
    private $player;


    public function __construct(FactionMain $main,Player $player) {
        $this->main = $main;
        $this->player = $player;
    }
    
    public function onRun($t) {
        $a = $this->main->atwar[$this->main->getPlayerFaction($this->player->getName())];
        if (isset($a)){
            
        }else{
            $this->player->sendMessage("Sorry, Your not able to use this command.\nMake sure that your faction is in a war\n Make sure your Attacking too!");
        }
    }
    //put your code here
}
