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
use pocketmine\math\Vector3;
use onebone\economyapi\EconomyAPI;
use FactionsPro\FactionMain;


class FactionCommands {
	
	public $plugin;
	
	public function __construct(FactionMain $pg) {
		$this->plugin = $pg;
	}
	
	public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
		if($sender instanceof Player) {
			$player = $sender->getPlayer()->getName();
			if(strtolower($command->getName('f'))) {
				if(empty($args)) {
					$sender->sendMessage("[CyberFaction] Please use /f help for a list of commands");
				}
                                
                                 if (isset($args[0]) && ($args[0] == "chat" || $args[0] == "c")){
                                            foreach($this->plugin->getServer()->getOnlinePlayers() as  $p){
                                                if ($this->plugin->sameFaction($sender->getName(), $p->getName())){
                                                   $b[] = $p; 
                                                }
                                            }
                                            $chat = "";
                                            foreach($args as $cc=>$c){
                                                if ($cc !== 0){
                                                    $chat .= $c." ";
                                                }
                                            }
                                            foreach($b as $p){
                                                $p->sendMessage("**[FACTION]**: $chat");
                                            }
                                            return true;
                                        }
                                
				if(count($args == 2)) {
					
                                        //War
                                        if($args[0] == "war") {
                                                if(!($sender instanceof Player)){
                                                    return true;
                                                }
                                                if(!(ctype_alnum($args[1]))) {
							$sender->sendMessage("[CyberFaction] You may only use letters and numbers!");
							return true;
						}
                                                if (!$this->plugin->factionPartialName($args[1])){
                                                    $sender->sendMessage("Please Make Sure the Faction Name is right!");
                                                    return true;
                                                }
						if(!$this->plugin->isInFaction($sender->getName())) {
							$sender->sendMessage("[CyberFaction] You must Be In a faction!");
							return true;
						}
                                                if(!$this->plugin->isLeader($sender->getName())) {
                                                    if (!$this->plugin->isOfficer($sender->getName())){
							$sender->sendMessage("[CyberFaction] You must Be the Leader Or Officer To Declare War!");
							return true;
                                                    }
						}
                                                $attackers = $this->plugin->getPlayerFaction($sender->getName());
                                                $defenders = $this->plugin->factionPartialName($args[1]);
                                                $this->plugin->DeclareWar($attackers, $defenders);
                                                
                                            
                                        }
                                    
                                    
					//Create
					
					if($args[0] == "create") {
						if(!(ctype_alnum($args[1]))) {
							$sender->sendMessage("[CyberFaction] You may only use letters and numbers!");
							return true;
						}
						if($this->plugin->factionExists($args[1]) == true ) {
							$sender->sendMessage("[CyberFaction] Faction already exists");
							return true;
						}
						if(strlen($args[1]) > $this->plugin->prefs->get("MaxFactionNameLength")) {
							$sender->sendMessage("[CyberFaction] Faction name is too long. Please try again!");
							return true;
						}
						if($this->plugin->isInFaction($sender->getName())) {
							$sender->sendMessage("[CyberFaction] You must leave this faction first");
							return true;
						} else {
							$factionName = $args[1];
							$player = strtolower($player);
							$rank = "Leader";
							$stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
							$stmt->bindValue(":player", $player);
							$stmt->bindValue(":faction", $factionName);
							$stmt->bindValue(":rank", $rank);
							$result = $stmt->execute();
							$sender->sendMessage("[CyberFaction] Faction successfully created!");
							return true;
						}
					}
					
					//Invite
					
					if($args[0] == "invite" || $args[0] =="inv") {
						if( $this->plugin->isFactionFull($this->plugin->getPlayerFaction($player)) ) {
							$sender->sendMessage("[CyberFaction] Faction is full. Please kick players to make room.");
							return true;
						}
                                                //remove Player Exact
						$invited = $this->plugin->getServer()->getPlayerExact($args[1]);
						if($this->plugin->isInFaction($invited) == true) {
							$sender->sendMessage("[CyberFaction] Player is currently in a faction");
							return true;
						}
						if($this->plugin->prefs->get("OnlyLeadersCanInvite") & !($this->plugin->isLeader($player))) {
							$sender->sendMessage("[CyberFaction] Only your faction leader may invite!");
							return true;
						}
						if(!$invited instanceof Player) {
							$sender->sendMessage("[CyberFaction] Player not online!");
							return true;
						}
						if($invited->isOnline() == true) {
							$factionName = $this->plugin->getPlayerFaction($player);
							$invitedName = $invited->getName();
							$rank = "Member";
								
							$stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO confirm (player, faction, invitedby, timestamp) VALUES (:player, :faction, :invitedby, :timestamp);");
							$stmt->bindValue(":player", strtolower($invitedName));
							$stmt->bindValue(":faction", $factionName);
							$stmt->bindValue(":invitedby", $sender->getName());
							$stmt->bindValue(":timestamp", time());
							$result = $stmt->execute();
	
							$sender->sendMessage("[CyberFaction] Successfully invited $invitedName!");
							$invited->sendMessage("[CyberFaction] You have been invited to $factionName. Type '/f accept' or '/f deny' into chat to accept or deny!");
						} else {
							$sender->sendMessage("[CyberFaction] Player not online!");
						}
					}
					
					//Leader
					
					if($args[0] == "leader") {
						if($this->plugin->isInFaction($sender->getName()) == true) {
							if($this->plugin->isLeader($player) == true) {
								if($this->plugin->getPlayerFaction($player) == $this->plugin->getPlayerFaction($args[1])) {
									if($this->plugin->getServer()->getPlayer($args[1])->isOnline() == true) {
										$factionName = $this->plugin->getPlayerFaction($player);
										$factionName = $this->plugin->getPlayerFaction($player);
	
										$stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
										$stmt->bindValue(":player", $player);
										$stmt->bindValue(":faction", $factionName);
										$stmt->bindValue(":rank", "Member");
										$result = $stmt->execute();
	
										$stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
										$stmt->bindValue(":player", strtolower($this->plugin->getServer()->getPlayer($args[1])->getName()));
										$stmt->bindValue(":faction", $factionName);
										$stmt->bindValue(":rank", "Leader");
										$result = $stmt->execute();
	
	
										$sender->sendMessage("[CyberFaction] You are no longer leader!");
										$this->plugin->getServer()->getPlayerExact($args[1])->sendMessage("[CyberFaction] You are now leader \nof $factionName!");
									} else {
										$sender->sendMessage("[CyberFaction] Player not online!");
									}
								} else {
									$sender->sendMessage("[CyberFaction] Add player to faction first!");
								}
							} else {
								$sender->sendMessage("[CyberFaction] You must be leader to use this");
							}
						} else {
							$sender->sendMessage("[CyberFaction] You must be in a faction to use this!");
						}
					}
					
					//Promote
					
					if($args[0] == "promote") {
						$pp = $this->plugin->getServer()->getPlayer($args[1]);
                                                if (!($pp instanceof Player)){
                                                    $sender->sendMessage("[CyberFactions] Not A Player!");
                                                    return true;
                                                }
                                                $ppn = $pp->getName();
						$factionName = $this->plugin->getPlayerFaction($player);
						if (!($pp instanceof Player)){
                                                    $sender->sendMessage("[CyberFactions] Player Is Not Online Or Does Not Exist!");
                                                    return true;
                                                }
                                                
						if($this->plugin->isInFaction($sender->getName()) == false) {
							$sender->sendMessage("[CyberFaction] You must be in a faction to use this!");
							return true;
						}
						if($this->plugin->isLeader($player) == false) {
							$sender->sendMessage("[CyberFaction] You must be leader to use this");
							return true;
						}
						if($this->plugin->getPlayerFaction($player) != $this->getPlayerFaction($ppn)) {
							$sender->sendMessage("[CyberFaction] Player is not in this faction!");
							return true;
						}
						if($this->plugin->isOfficer($player) == true) {
							$sender->sendMessage("[CyberFaction] Player is already officer");
							return true;
						}
						$stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
						$stmt->bindValue(":player", strtolower($ppn));
						$stmt->bindValue(":faction", $factionName);
						$stmt->bindValue(":rank", "Officer");
						$result = $stmt->execute();
					}
					
					//Demote
					
					if($args[0] == "demote") {
					
						$pp = $this->plugin->getServer()->getPlayer($args[1]);
                                                $ppn = $pp->getName();
						$factionName = $this->plugin->getPlayerFaction($player);
						if (!($pp instanceof Player)){
                                                    $sender->sendMessage("Player Is Not Online Or Does Not Exist!");
                                                    return true;
                                                }
                                                
						if($this->plugin->isInFaction($sender->getName()) == false) {
							$sender->sendMessage("[CyberFaction] You must be in a faction to use this!");
							return true;
						}
						if($this->plugin->isLeader($player) == false) {
							$sender->sendMessage("[CyberFaction] You must be leader to use this");
							return true;
						}
						if($this->plugin->getPlayerFaction($player) != $this->getPlayerFaction($ppn)) {
							$sender->sendMessage("[CyberFaction] Player is not in this faction!");
							return true;
						}
						if($this->plugin->isOfficer($player) == false) {
							$sender->sendMessage("[CyberFaction] Player is not Officer");
							return true;
						}
						$stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
						$stmt->bindValue(":player", strtolower($this->plugin->getServer()->getPlayer($args[1])->getName()));
						$stmt->bindValue(":faction", $factionName);
						$stmt->bindValue(":rank", "Member");
						$result = $stmt->execute();
					}
					
					//Kick
					
					if($args[0] == "kick") {
                                            $pp = $this->plugin->getServer()->getPlayer($args[1]);
                                            $ppn = $pp->getName();
                                                if (!($pp instanceof Player)){
                                                    $sender->sendMessage("[CyberFactions] Player Is Not Online or Does Not Exist!");
                                                    return true;
                                                }
                                                if ($args[1] == ""){
                                                    $sender->sendMessage("[CyberFactions] Invalid \n [CyberFactions] /f kick <player>");
                                                    return true;
                                                }
                                                
						if($this->plugin->isInFaction($sender->getName()) == false) {
							$sender->sendMessage("[CyberFaction] You must be in a faction to use this!");
							return true;
						}
						if($this->plugin->isLeader($player) == false) {
							$sender->sendMessage("[CyberFaction] You must be leader to use this");
							return true;
						}
                                                if ($this->plugin->isInFaction($ppn) == false){
                                                    $sender->sendMessage("[CyberFaction] Player Not In Faction!");
                                                }
						if($this->plugin->getPlayerFaction($player) != $this->getPlayerFaction($ppn)) {
							$sender->sendMessage("[CyberFaction] Player is not in this faction!");
							return true;
						}
						$factionName = $this->plugin->getPlayerFaction($player);
						$this->plugin->db->query("DELETE FROM master WHERE player='$ppn';");
						$sender->sendMessage("[CyberFaction] You successfully kicked $ppn!");
						$pp->sendMessage("[CyberFaction] You Have Been Kicked From $factionName!!!");
					}
					
					//Info
					
					if(strtolower($args[0]) == 'info') {
						if(isset($args[1])) {
							if( !(ctype_alnum($args[1])) | !($this->plugin->factionExists($args[1]))) {
								$sender->sendMessage("[CyberFaction] Faction does not exist");
								return true;
							}
							$faction = strtolower($args[1]);
							$leader = $this->plugin->getLeader($faction);
							$numPlayers = $this->plugin->getNumberOfPlayers($faction);
							$sender->sendMessage("-------------------------");
							$sender->sendMessage("$faction");
							$sender->sendMessage("Leader: $leader");
							$sender->sendMessage("# of Players: $numPlayers");
							//$sender->sendMessage("Desc: $description");
							$sender->sendMessage("-------------------------");
						} else {
							$faction = $this->plugin->getPlayerFaction(strtolower($sender->getName()));
							$result = $this->plugin->db->query("SELECT * FROM desc WHERE faction='$faction';");
							//$description = $array["description"];
							$leader = $this->plugin->getLeader($faction);
							$numPlayers = $this->plugin->getNumberOfPlayers($faction);
							$sender->sendMessage("-------------------------");
							$sender->sendMessage("$faction");
							$sender->sendMessage("Leader: $leader");
							$sender->sendMessage("# of Players: $numPlayers");
							//$sender->sendMessage("Desc: $description");
							$sender->sendMessage("-------------------------");
						}
					}
                                        
				}
				if(count($args == 1)) {
                                        if (strtolower($args[0]) == "wartp"){
                                            if (!$this->plugin->isInFaction($player)){
                                                $sender->sendMessage("You must be in faction to use this command");
                                                return true;
                                            }
                                            if (!isset($this->plugin->atwar[$this->plugin->getPlayerFaction($player)])){
                                                $sender->sendMessage("You are Not At War!");
                                                return true;
                                            }
                                            if ($this->plugin->GetRandomTPArea($this->plugin->atwar[$this->plugin->getPlayerFaction($player)], 7)){
                                            $pos = $this->plugin->GetRandomTPArea($this->plugin->atwar[$this->plugin->getPlayerFaction($player)], 7);
                                            $sender->teleport($pos);
                                            $sender->sendMessage("Teleported To War Zone!");
                                            return true;
                                            }
                                            return true;
                                        }
                                        
                                    
					
					//Plot
					
					if(strtolower($args[0]) == "claim") {
						if(!$this->plugin->isInFaction($sender->getName())) {
							$sender->sendMessage("[CyberFaction] You must be in a faction to use this.");
							return true;
						}
                                                $amount = (100) * $this->plugin->prefs->get("PlotPrice");
                                                if (!($this->plugin->api->myMoney($sender->getName()) >= $amount)){
                                                    $sender->sendMessage("You don't have enough Money! Plot Price: $amount");
                                                    return true;
                                                }else{
                                                    $this->plugin->api->reduceMoney($sender->getName(), $amount);
                                                }
                                                    //$this->api->myMoney($sendplayer->getName())
						$x = floor($sender->getX());
						$y = floor($sender->getY());
						$z = floor($sender->getZ());
						$faction = $this->plugin->getPlayerFaction($sender->getPlayer()->getName());
                                                
						$this->plugin->drawPlot($sender, $faction, $x, $y, $z, $sender->getPlayer()->getLevel(), 10);
					}
					
					if(strtolower($args[0]) == "unclaim") {
						if(!$this->plugin->isLeader($sender->getName())) {
							$sender->sendMessage("[CyberFaction] You must be leader to use this.");
							return true;
						}
						$faction = $this->plugin->getPlayerFaction($sender->getName());
						$this->plugin->db->query("DELETE FROM plots WHERE faction='$faction';");
						$sender->sendMessage("[CyberFaction] Plot unclaimed.");
					}
					
					//Description
					
					/*if(strtolower($args[0]) == "desc") {
						if($this->plugin->isInFaction($sender->getName()) == false) {
							$sender->sendMessage("[CyberFaction] You must be in a faction to use this!");
							return true;
						}
						if($this->plugin->isLeader($player) == false) {
							$sender->sendMessage("[CyberFaction] You must be leader to use this");
							return true;
						}
						$sender->sendMessage("[CyberFaction] Type your description in chat. It will not be visible to other players");
						$stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO descRCV (player, timestamp) VALUES (:player, :timestamp);");
						$stmt->bindValue(":player", strtolower($sender->getName()));
						$stmt->bindValue(":timestamp", time());
						$result = $stmt->execute();
					}*/
					
					//Accept
					
					if(strtolower($args[0]) == "accept") {
						$player = $sender->getName();
						$lowercaseName = strtolower($player);
						$result = $this->plugin->db->query("SELECT * FROM confirm WHERE player='$lowercaseName';");
						$array = $result->fetchArray(SQLITE3_ASSOC);
						if(empty($array) == true) {
							$sender->sendMessage("[CyberFaction] You have not been invited to any factions!");
							return true;
						}
						$invitedTime = $array["timestamp"];
						$currentTime = time();
						if( ($currentTime - $invitedTime) <= 60 ) { //This should be configurable
							$faction = $array["faction"];
							$stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
							$stmt->bindValue(":player", strtolower($player));
							$stmt->bindValue(":faction", $faction);
							$stmt->bindValue(":rank", "Member");
							$result = $stmt->execute();
							$this->plugin->db->query("DELETE FROM confirm WHERE player='$lowercaseName';");
							$sender->sendMessage("[CyberFaction] You successfully joined $faction!");
							$this->plugin->getServer()->getPlayerExact($array["invitedby"])->sendMessage("[CyberFaction] $player joined the faction!");
						} else {
							$sender->sendMessage("[CyberFaction] Invite has timed out!");
							$this->plugin->db->query("DELETE * FROM confirm WHERE player='$player';");
						}
					}
					
					//Deny
					
					if(strtolower($args[0]) == "deny") {
						$player = $sender->getName();
						$lowercaseName = strtolower($player);
						$result = $this->plugin->db->query("SELECT * FROM confirm WHERE player='$lowercaseName';");
						$array = $result->fetchArray(SQLITE3_ASSOC);
						if(empty($array) == true) {
							$sender->sendMessage("[CyberFaction] You have not been invited to any factions!");
							return true;
						}
						$invitedTime = $array["timestamp"];
						$currentTime = time();
						if( ($currentTime - $invitedTime) <= 60 ) { //This should be configurable
							$this->plugin->db->query("DELETE * FROM confirm WHERE player='$lowercaseName';");
							$sender->sendMessage("[CyberFaction] Invite declined!");
							$this->plugin->getServer()->getPlayerExact($array["invitedby"])->sendMessage("[CyberFaction] $player declined the invite!");
						} else {
							$sender->sendMessage("[CyberFaction] Invite has timed out!");
							$this->plugin->db->query("DELETE * FROM confirm WHERE player='$lowercaseName';");
						}
					}
					
					//Delete
					
					if(strtolower($args[0]) == "del") {
						if($this->plugin->isInFaction($player) == true) {
							if($this->plugin->isLeader($player)) {
								$faction = $this->plugin->getPlayerFaction($player);
								$this->plugin->db->query("DELETE FROM master WHERE faction='$faction';");
								$sender->sendMessage("[CyberFaction] Faction successfully disbanded!");
							}	 else {
								$sender->sendMessage("[CyberFaction] You are not leader!");
							}
						} else {
							$sender->sendMessage("[CyberFaction] You are not in a faction!");
						}
					}
					
					//Leave
					
					if(strtolower($args[0] == "leave")) {
						if($this->plugin->isLeader($player) == false) {
							$remove = $sender->getPlayer()->getNameTag();
							$faction = $this->plugin->getPlayerFaction($player);
							$name = $sender->getName();
							$this->plugin->db->query("DELETE FROM master WHERE player='$name';");
							$sender->sendMessage("[CyberFaction] You successfully left $faction");
						} else {
							$sender->sendMessage("[CyberFaction] You must delete or give\nleadership first!");
						}
					}
					
					//Home
					
					if(strtolower($args[0] == "sethome")) {
						$factionName = $this->plugin->getPlayerFaction($sender->getName());
						$stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO home (faction, x, y, z) VALUES (:faction, :x, :y, :z);");
						$stmt->bindValue(":faction", $factionName);
						$stmt->bindValue(":x", $sender->getX());
						$stmt->bindValue(":y", $sender->getY());
						$stmt->bindValue(":z", $sender->getZ());
						$result = $stmt->execute();
						$sender->sendMessage("[CyberFaction] Home updated!");
					}
					
					if(strtolower($args[0] == "unsethome")) {
						$faction = $this->plugin->getPlayerFaction($sender->getName());
						$this->plugin->db->query("DELETE FROM home WHERE faction = '$faction';");
						$sender->sendMessage("[CyberFaction] Home unset!");
					}
					
					if(strtolower($args[0] == "home")) {
						$faction = $this->plugin->getPlayerFaction($sender->getName());
						$result = $this->plugin->db->query("SELECT * FROM home WHERE faction = '$faction';");
						$array = $result->fetchArray(SQLITE3_ASSOC);
						if(!empty($array)) {
							$sender->getPlayer()->teleport(new Vector3($array['x'], $array['y'], $array['z']));
							$sender->sendMessage("[CyberFaction] Teleported home.");
							return true;
						} else {
							$sender->sendMessage("[CyberFaction] Home is not set.");
						}
					}
					
					if(strtolower($args[0]) == "help") {
						$sender->sendMessage("FactionsPro Commands\n/f create <name>\n/f del\n/f help\n/f invite <player>\n/f kick <player>\n/f leave\n/f leader <player>\n/f leave\n/f motd\n/f info\n/f chat\n/f claim\n/f unclaim");
					}
				} else {
                                    
					$sender->sendMessage("[CyberFaction] Please use /f help for a list of commands");
				}
			}
		} else {
			$this->plugin->getServer()->getLogger()->info(TextFormat::RED . "[CyberFaction] Please run command in game");
		}
	}
}