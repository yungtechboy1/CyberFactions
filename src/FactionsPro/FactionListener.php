<?php

namespace FactionsPro;

use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\Player;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\utils\TextFormat;
use pocketmine\scheduler\PluginTask;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\utils\Config;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\block\BlockPlaceEvent;


class FactionListener implements Listener {
	
	public $plugin;
	
	public function __construct(FactionMain $pg) {
		$this->plugin = $pg;
	}
	
	
	public function factionPVP(EntityDamageEvent $factionDamage) {
		if($factionDamage instanceof EntityDamageByEntityEvent) {
			if(!($factionDamage->getEntity() instanceof Player) or !($factionDamage->getDamager() instanceof Player)) {
				return true;
			}
			if(($this->plugin->isInFaction($factionDamage->getEntity()->getPlayer()->getName()) == false) or ($this->plugin->isInFaction($factionDamage->getDamager()->getPlayer()->getName()) == false) ) {
				return true;
			}
			if(($factionDamage->getEntity() instanceof Player) and ($factionDamage->getDamager() instanceof Player)) {
				$player1 = $factionDamage->getEntity()->getPlayer()->getName();
				$player2 = $factionDamage->getDamager()->getPlayer()->getName();
				if($this->plugin->sameFaction($player1, $player2) == true) {
					$factionDamage->setCancelled(true);
				}
			}
		}
	}
	public function factionBlockBreakProtect(BlockBreakEvent $event) {
            if ($event->getPlayer() instanceof Player){
                if($event->getPlayer()->isOp()){
                    return true;
                }
            }
		if($this->plugin->pointIsInPlot($event->getBlock()->getFloorX(), $event->getBlock()->getFloorZ())) {
			if( ($this->plugin->factionFromPoint($event->getBlock()->getFloorX(), $event->getBlock()->getFloorZ())) != $this->plugin->getPlayerFaction($event->getPlayer()->getName())) {
				$faction = $this->plugin->factionFromPoint($event->getBlock()->getFloorX(), $event->getBlock()->getFloorZ());
                                if ($this->plugin->AtWar($this->plugin->getPlayerFaction($event->getPlayer()->getName()),$faction)){
                                    return true;
                                }
                                $event->setCancelled(true);
				$event->getPlayer()->sendMessage("[CyberFaction] This area is claimed by $faction");
				return true;    
			}
		}
	}
	
	public function factionBlockPlaceProtect(BlockPlaceEvent $event) {
            if ($event->getPlayer() instanceof Player){
                if($event->getPlayer()->isOp()){
                    return true;
                }
            }
		if($this->plugin->pointIsInPlot($event->getBlock()->getFloorX(), $event->getBlock()->getFloorZ())) {
                    if( ($this->plugin->factionFromPoint($event->getBlock()->getFloorX(), $event->getBlock()->getFloorZ())) != $this->plugin->getPlayerFaction($event->getPlayer()->getName())) {
                                $faction = $this->plugin->factionFromPoint($event->getBlock()->getFloorX(), $event->getBlock()->getFloorZ());
                                if ($this->plugin->AtWar($this->plugin->getPlayerFaction($event->getPlayer()->getName()),$faction)){
                                    return true;
                                }		
                                $event->setCancelled(true);
				$event->getPlayer()->sendMessage("[CyberFaction] This area is claimed by $faction");
				return true;
			}
		}
	}
	
}
