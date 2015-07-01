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
                            $chat = "";
                            foreach($args as $cc=>$c){
                                if ($cc !== 0){
                                    $chat .= $c." ";
                                }
                            }
                            foreach($this->plugin->getServer()->getOnlinePlayers() as  $p){
                                if ($this->plugin->sameFaction($sender->getName(), $p->getName())){
                                    $p->sendMessage("**[$player]**: $chat");
                                }
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
                        if(!isset($args[1])){
                            $sender->sendMessage(TextFormat::GRAY."Useage /f create <name>");
                            return true;
                        }
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
                                                        $faction = $factionName;
                                                        $rank = "Leader";
                                                        $this->plugin->DeleteChache($player, "getPlayerFaction");
                                                        $this->plugin->SetChache($player, "getPlayerFaction", $faction);
                                                        $this->plugin->SetChache($player, "isInFaction","yes");
							@mysqli_query($this->plugin->db,"REPLACE INTO `master` VALUES ('$player', '$faction', '$rank');");
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
						$invited = $this->plugin->getServer()->getPlayer($args[1]);
						if(!$invited instanceof Player) {
							$sender->sendMessage("[CyberFaction] No Player By That Name Is Online!");
							return true;
						}
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
							$invitee = $invited->getName();
                                                        $invitedName = $invited->getName();
                                                        $faction = $this->plugin->getPlayerFaction($player);
                                                        $rank = "Member";
                                                        $time = time();
                                                        @mysqli_query($this->plugin->db,"REPLACE INTO `confirm` VALUES ('$invitee', '$faction', '$player','$time');");
;
	
							$sender->sendMessage("[CyberFaction] Successfully invited $invitedName!");
							$invited->sendMessage("[CyberFaction] You have been invited to $faction.\n Type '/f accept' or '/f deny' into chat to accept or deny!");
						} else {
							$sender->sendMessage("[CyberFaction] Player not online!");
						}
					}
					
					//Leader
					
					if($args[0] == "leader") {
						if($this->plugin->isInFaction($sender->getName()) == true) {
							if($this->plugin->isLeader($player) == true) {
                                                            if(!isset($args[1])){
                                                                $sender->sendMessage(TextFormat::RED."Useage /f leader <player>");
                                                                return true;
                                                            }
                                                            if ($this->plugin->getServer()->getPlayer($args[1]) instanceof Player){
								if($this->plugin->getPlayerFaction($player) == $this->plugin->getPlayerFaction($args[1])) {
                                                                    $factionName = $this->plugin->getPlayerFaction($player);
                                                                    $player = strtolower($player);
                                                                    $faction = $factionName;
                                                                    $rank = "Member";
                                                                    @mysqli_query($this->plugin->db,"REPLACE INTO `master` VALUES ('$player', '$faction', '$rank');");
                                                                    $player = strtolower($this->plugin->getServer()->getPlayer($args[1])->getName());
                                                                    $faction = $factionName;
                                                                    $rank = "Leader";
                                                                    @mysqli_query($this->plugin->db,"REPLACE INTO `master` VALUES ('$player', '$faction', '$rank');");


                                                                    $sender->sendMessage("[CyberFaction] You are no longer leader!");
                                                                    $this->plugin->getServer()->getPlayerExact($args[1])->sendMessage("[CyberFaction] You are now leader \nof $factionName!");
								} else {
									$sender->sendMessage("[CyberFaction] Add player to faction first!");
								}
							} else {
                                                            $sender->sendMessage("[CyberFaction] Player Not Online!");
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
                                                if(!isset($args[1])){
                                                    $sender->sendMessage(TextFormat::GRAY."Useage /f promote <player>");
                                                    return true;
                                                }
						$pp = $this->plugin->getServer()->getPlayer($args[1]);
                                                if (!($pp instanceof Player)){
                                                    $sender->sendMessage(TextFormat::RED."[CyberFactions] Player Is Not Online Or Does Not Exist!");;
                                                    return true;
                                                }
                                                $ppn = $pp->getName();
						$factionName = $this->plugin->getPlayerFaction($player);
						if($this->plugin->isInFaction($sender->getName()) == false) {
							$sender->sendMessage(TextFormat::RED."[CyberFaction] You must be in a faction to use this!");
							return true;
						}
						if($this->plugin->isLeader($player) == false) {
							$sender->sendMessage(TextFormat::RED."[CyberFaction] You must be leader to use this");
							return true;
						}
                                                if($this->plugin->isInFaction($ppn) == false){
                                                    $sender->sendMessage(TextFormat::RED."Target Player Not In Your Faction!");
                                                    return true;
                                                }
						if($this->plugin->getPlayerFaction($player) != $this->plugin->getPlayerFaction($ppn)) {
							$sender->sendMessage(TextFormat::RED."[CyberFaction] Player is not in this faction!");
							return true;
						}
						if($this->plugin->isOfficer($ppn) == true) {
							$sender->sendMessage(TextFormat::RED."[CyberFaction] Player is already officer");
							return true;
						}
                                                $player = strtolower($ppn);
                                                $faction = $factionName;
                                                $rank = "Officer";
						@mysqli_query($this->plugin->db,"REPLACE INTO `master` VALUES ('$player', '$faction', '$rank');");
                                                $sender->sendMessage(TextFormat::GREEN."[CyberFaction] You successfully Promoted $ppn!");
						$pp->sendMessage(TextFormat::GREEN."[CyberFaction] You Have Been Promoted To a Officer!!!");
					}
					
					//Demote
					
					if($args[0] == "demote") {
					
						$pp = $this->plugin->getServer()->getPlayer($args[1]);
                                                $ppn = $pp->getName();
						$factionName = $this->plugin->getPlayerFaction($player);
						if (!($pp instanceof Player)){
                                                    $sender->sendMessage(TextFormat::RED."[CyberFaction] Player Is Not Online Or Does Not Exist!");
                                                    return true;
                                                }
						if($this->plugin->isInFaction($sender->getName()) == false) {
							$sender->sendMessage(TextFormat::RED."[CyberFaction] You must be in a faction to use this!");
							return true;
						}
						if($this->plugin->isLeader($player) == false) {
							$sender->sendMessage(TextFormat::RED."[CyberFaction] You must be leader to use this");
							return true;
						}
						if($this->plugin->getPlayerFaction($player) != $this->plugin->getPlayerFaction($ppn)) {
							$sender->sendMessage(TextFormat::RED."[CyberFaction] Player is not in this faction!");
							return true;
						}
						if($this->plugin->isOfficer($player) == false) {
							$sender->sendMessage(TextFormat::RED."[CyberFaction] Player is not Officer");
							return true;
						}
                                                $player = strtolower($ppn);
                                                $faction = $factionName;
                                                $rank = "Member";
						@mysqli_query($this->plugin->db,"REPLACE INTO `master` VALUES ('$player', '$faction', '$rank');");
                                                $sender->sendMessage(TextFormat::GREEN."[CyberFaction] You successfully Demoted $ppn!");
						$pp->sendMessage(TextFormat::GREEN."[CyberFaction] You Have Been Demoted To a Member!!!");
					}
					
					//Kick
					
					if($args[0] == "kick") {
                                                if ($args[1] == ""){
                                                    $sender->sendMessage(TextFormat::RED."[CyberFactions] Invalid \n [CyberFactions] /f kick <player>");
                                                    return true;
                                                }
                                                $pp = $this->plugin->getServer()->getPlayer($args[1]);
                                                if (!($pp instanceof Player)){
                                                    $sender->sendMessage(TextFormat::RED."[CyberFactions] Player Is Not Online or Does Not Exist!");
                                                    return true;
                                                }
                                                $ppn = $pp->getName();
                                                if($this->plugin->isInFaction($sender->getName()) == false) {
							$sender->sendMessage(TextFormat::RED."[CyberFaction] You must be in a faction to use this!");
							return true;
						}
						if($this->plugin->isLeader($player) == false) {
							$sender->sendMessage(TextFormat::RED."[CyberFaction] You must be leader to use this");
							return true;
						}
                                                if ($this->plugin->isInFaction($ppn) == false){
                                                    $sender->sendMessage(TextFormat::RED."[CyberFaction] Player Not In Faction!");
                                                }
						if($this->plugin->getPlayerFaction($player) != $this->plugin->getPlayerFaction($ppn)) {
							$sender->sendMessage(TextFormat::RED."[CyberFaction] Player is not in this faction!");
							return true;
						}
                                                $this->plugin->DeleteChache($player, "getPlayerFaction");
                                                $this->plugin->DeleteChache($player, "isInFaction");
						$factionName = $this->plugin->getPlayerFaction($player);
						@mysqli_query($this->plugin->db,"DELETE FROM master WHERE player='$ppn';");
						$sender->sendMessage(TextFormat::GREEN."[CyberFaction] You successfully kicked $ppn!");
						$pp->sendMessage(TextFormat::GREEN."[CyberFaction] You Have Been Kicked From $factionName!!!");
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
							$result = @mysqli_query($this->plugin->db,"SELECT * FROM desc WHERE faction='$faction';");
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
                                        
                                        if(strtolower($args[0] == "ally")) {
                                            if(!isset($args[1]) || !isset($args[2]) || ($args[1] !== "add" && $args[1] !== "remove")){
                                                $sender->sendMessage(TextFormat::RED."[CyberFaction] Useage /f ally add|remove <faction>!!!");
                                                return true;
                                            }
                                            if($args[0] == "add"){
                                                $ofaction = $this->plugin->factionPartialName($args[2]);
                                                $faction = $this->plugin->getPlayerFaction($sender->getName());
                                                if(!$this->plugin->isInFaction($sender->getName())){
                                                    $sender->sendMessage(TextFormat::RED."[CyberFaction] You must be in a faction to use this command!!!");
                                                    return true;
                                                }
                                                if($ofaction == false){
                                                    $sender->sendMessage(TextFormat::RED."[CyberFaction] Their is no factions by the name of `".$args[2]."`!!!");
                                                    return true;
                                                }
                                                if( $this->plugin->isFactionsAllyed($faction, $ofaction)){
                                                    $sender->sendMessage(TextFormat::RED."[CyberFaction] Silly Human! Your Already Allied!!");
                                                    return true;
                                                }
                                                if(!$this->plugin->isOfficer($player) || !$this->plugin->isLeader($player)){
                                                    $sender->sendMessage(TextFormat::RED."[CyberFaction] Silly Human! Your not an officer!!");
                                                    return true;
                                                }
                                                if($this->plugin->GetFactionPower($faction) < 50){
                                                    $sender->sendMessage(TextFormat::RED."[CyberFaction] You Dont Have Enough Power!");
                                                    return true;
                                                }else{
                                                    $this->plugin->TakeFactionPower($faction, 50);
                                                    $this->plugin->AddAlliance($faction, $ofaction);
                                                    $this->plugin->getServer()->broadcastMessage(TextFormat::DARK_AQUA."$faction and $ofaction Are now in an alliance!");
                                                    return true;
                                                }
                                            }
                                            if($args[0] == "remove"){
                                                $ofaction = $this->plugin->factionPartialName($args[2]);
                                                $faction = $this->plugin->getPlayerFaction($sender->getName());
                                                if(!$this->plugin->isInFaction($sender->getName())){
                                                    $sender->sendMessage(TextFormat::RED."[CyberFaction] You must be in a faction to use this command!!!");
                                                    return true;
                                                }
                                                if($ofaction == false){
                                                    $sender->sendMessage(TextFormat::RED."[CyberFaction] Their is no factions by the name of `".$args[2]."`!!!");
                                                    return true;
                                                }
                                                if( $this->plugin->isFactionsAllyed($faction, $ofaction)){
                                                    $sender->sendMessage(TextFormat::RED."[CyberFaction] Silly Human! Your Already Allied!!");
                                                    return true;
                                                }
                                                if(!$this->plugin->isOfficer($player) || !$this->plugin->isLeader($player)){
                                                    $sender->sendMessage(TextFormat::RED."[CyberFaction] Silly Human! Your not an officer!!");
                                                    return true;
                                                }
                                                $this->plugin->TakeFactionPower($faction, 50);
                                                $this->plugin->RemoveAlliance($faction, $ofaction);
                                                $this->plugin->getServer()->broadcastMessage(TextFormat::DARK_AQUA."$faction and $ofaction Are nolonger in an alliance!");
                                                return true;
                                            }
                                        }
                                        
				}
                                
                                 if(strtolower($args[0] == "home")) {
                                        if(isset($args[1])){
                                            $ofaction = $this->plugin->factionPartialName($args[1]);
                                            if($ofaction == false){
                                                $sender->sendMessage(TextFormat::RED."[CyberFaction] No Faction Found By That Name!");
                                                return true;
                                            }
                                            if(!$this->plugin->isFactionsAllyed($this->plugin->getPlayerFaction($sender->getName()), $faction)){
                                                $sender->sendMessage(TextFormat::RED."[CyberFaction] You Are Not Allied With That Faction!!!");
                                                return true;
                                            }
                                            $result = @mysqli_query("SELECT * FROM `home` WHERE `faction` = '$ofaction';");
                                            $array = @mysql_fetch_array($result);
                                            $count = @mysql_num_rows($result);
                                            if($count > 0) {
                                                    $sender->getPlayer()->teleport(new Vector3($array['x'], $array['y'], $array['z']));
                                                    $sender->sendMessage(TextFormat::GREEN."[CyberFaction] Teleported to $ofaction home.");
                                                    return true;
                                            } else {
                                                    $sender->sendMessage(TextFormat::GOLD."[CyberFaction] Home is not set.");
                                            }
                                            return true;
                                        }
                                        $faction = $this->plugin->getPlayerFaction($sender->getName());
                                        $result = @mysqli_query("SELECT * FROM `home` WHERE `faction` = '$faction';");
                                        $array = @mysqli_fetch_assoc($result);
                                        $count = @mysqli_num_rows($result);
                                        if($count > 0) {
                                                $sender->getPlayer()->teleport(new Vector3($array['x'], $array['y'], $array['z']));
                                                $sender->sendMessage(TextFormat::GREEN."[CyberFaction] Teleported home.");
                                                return true;
                                        } else {
                                                $sender->sendMessage(TextFormat::GOLD."[CyberFaction] Home is not set.");
                                        }
                                }
                                
				if(count($args == 1)) {
                                        if (strtolower($args[0]) == "wartp"){
                                            if (!$this->plugin->isInFaction($player)){
                                                $sender->sendMessage(TextFormat::RED."You must be in faction to use this command!!!");
                                                return true;
                                            }
                                            if (!isset($this->plugin->atwar[$this->plugin->getPlayerFaction($player)])){
                                                $sender->sendMessage(TextFormat::RED."You are Not At War or Attacking!!!");
                                                return true;
                                            }
                                            if ($pos = $this->plugin->GetRandomTPArea($this->plugin->atwar[$this->plugin->getPlayerFaction($player)], 7)){
                                            $sender->teleport($pos);
                                            $sender->sendMessage(TextFormat::GREEN."Teleported To War Zone!");
                                            }else{
                                                $tp = $this->plugin->GetRandomFactionPlayer($this->plugin->atwar[$this->plugin->getPlayerFaction($player)]);
                                                $sender->teleport($tp->getPosition());
                                                $sender->sendMessage(TextFormat::GREEN."Teleported To War Zone!");
                                            }
                                            return true;
                                        }
                                        
                                        if (strtolower($args[0]) == "power"){
                                            if (!$this->plugin->isInFaction($player)){
                                                $sender->sendMessage(TextFormat::RED."You must be in faction to use this command!!!");
                                                return true;
                                            }
                                            $sender->sendMessage(TextFormat::LIGHT_PURPLE."Your Faction Has ".$this->plugin->GetFactionPower($this->plugin->getPlayerFaction($player)));
                                            return true;
                                        }
                                        
					//Plot
					
					if(strtolower($args[0]) == "claim") {
						if(!$this->plugin->isInFaction($sender->getName())) {
							$sender->sendMessage(TextFormat::RED."[CyberFaction] You must be in a faction to use this.");
							return true;
						}
                                                $amount = (100) * $this->plugin->prefs->get("PlotPrice");
                                                if (!($this->plugin->api->myMoney($sender->getName()) >= $amount)){
                                                    $sender->sendMessage(TextFormat::RED."You don't have enough Money! Plot Price: $amount");
                                                    return true;
                                                }else{
                                                    $this->plugin->api->reduceMoney($sender->getName(), $amount);
                                                }
                                                
						$x = floor($sender->getX());
						$y = floor($sender->getY());
						$z = floor($sender->getZ());
						$faction = $this->plugin->getPlayerFaction($sender->getPlayer()->getName());
                                                $this->plugin->AddFactioPower($faction, $this->plugin->prefs->get("PlotPrice"));
                                                $this->plugin->MessageFaction($faction, TextFormat::GRAY."Your Faction has gained 10 Power!", true);
                                                $this->plugin->AddFactioPower($faction, 10);
						$this->plugin->drawPlot($sender, $faction, $x, $y, $z, $sender->getPlayer()->getLevel(), 10);
					}
					
					if(strtolower($args[0]) == "unclaim") {
						if(!$this->plugin->isLeader($sender->getName())) {
							$sender->sendMessage("[CyberFaction] You must be leader to use this.");
							return true;
						}
						$faction = $this->plugin->getPlayerFaction($sender->getName());
						@mysqli_query($this->plugin->db,"DELETE FROM plots WHERE faction='$faction';");
						$sender->sendMessage("[CyberFaction] ALL Plots unclaimed.");
                                                $this->plugin->MessageFaction($faction, TextFormat::RED."Your Faction has Lost 100 Power!", true);
                                                $this->plugin->TakeFactionPower($faction, 100);
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
						$stmt = $this->plugin->db->prepare("REPLACE INTO descRCV (player, timestamp) VALUES (:player, :timestamp);");
						$stmt->bindValue(":player", strtolower($sender->getName()));
						$stmt->bindValue(":timestamp", time());
						$result = $stmt->execute();
					}*/
					
					//Accept
					
					if(strtolower($args[0]) == "accept") {
						$player = $sender->getName();
						$lowercaseName = strtolower($player);
						$result = @mysqli_query($this->plugin->db, "SELECT * FROM `confirm` WHERE `player`='$lowercaseName';");
						$array = @mysqli_fetch_assoc($result);
						if(empty($array) == true) {
							$sender->sendMessage(TextFormat::RED."[CyberFaction] You have not been invited to any factions!");
							return true;
						}
						$invitedTime = $array["timestamp"];
						$currentTime = time();
						if( ($currentTime - $invitedTime) <= 180 ) { //This should be configurable
							$faction = $array["faction"];
							@mysqli_query($this->plugin->db, "REPLACE INTO `master` VALUES ('".strtolower($player)."', '$faction', 'Member');");
							@mysqli_query($this->plugin->db,"DELETE FROM `confirm` WHERE `player` = '$lowercaseName';");
							$sender->sendMessage(TextFormat::GREEN."[CyberFaction] You successfully joined $faction!");
                                                        $this->plugin->DeleteChache($player, "getPlayerFaction");
                                                        $this->plugin->SetChache($player, "getPlayerFaction", $faction);
							$this->plugin->getServer()->getPlayerExact($array["invitedby"])->sendMessage(TextFormat::GREEN."[CyberFaction] $player joined the faction!");
                                                        $this->plugin->MessageFaction($faction, TextFormat::GREEN."[CyberFaction] $player joined the faction!!");
                                                        $this->plugin->SetChache($player, "isInFaction","yes");
						} else {
							$sender->sendMessage(TextFormat::RED."[CyberFaction] Invite has timed out!");
							@mysqli_query($this->plugin->db,"DELETE * FROM `confirm` WHERE `player` = '$player';");
						}
                                                
					}
					
					//Deny
					
					if(strtolower($args[0]) == "deny") {
						$player = $sender->getName();
						$lowercaseName = strtolower($player);
						$result = @mysqli_query($this->plugin->db, "SELECT * FROM `confirm` WHERE `player`='$lowercaseName';");
						$array = @mysqli_fetch_assoc($result);
						if(empty($array) == true) {
							$sender->sendMessage(TextFormat::RED."[CyberFaction] You have not been invited to any factions!");
							return true;
						}
						$invitedTime = $array["timestamp"];
						$currentTime = time();
						if( ($currentTime - $invitedTime) <= 120 ) { //This should be configurable
							@mysqli_query($this->plugin->db,"DELETE * FROM `confirm` WHERE `player`='$lowercaseName';");
							$sender->sendMessage(TextFormat::RED."[CyberFaction] Invite0 declined!");
							$this->plugin->getServer()->getPlayerExact($array["invitedby"])->sendMessage(TextFormat::RED."[CyberFaction] $player declined the invite!");
						} else {
							$sender->sendMessage(TextFormat::RED."[CyberFaction] Invite has timed out!");
							@mysqli_query($this->plugin->db,"DELETE * FROM `confirm` WHERE `player`='$lowercaseName';");
						}
					}
					
					//Delete
					
					if(strtolower($args[0]) == "del") {
						if($this->plugin->isInFaction($player) == true) {
							if($this->plugin->isLeader($player)) {
                                                            $faction = $this->plugin->getPlayerFaction($player);
                                                            @mysqli_query($this->plugin->db,"DELETE FROM `master` WHERE `faction` = '$faction';");
                                                            $sender->sendMessage(TextFormat::GREEN."[CyberFaction] Faction successfully disbanded!");
                                                            $this->plugin->DeleteChache($faction, "factionExists");
                                                            $this->plugin->DeleteChache($player, "getPlayerFaction");
                                                            $this->plugin->DeleteChache($player, "isInFaction");
							}else {
                                                            $sender->sendMessage(TextFormat::RED."[CyberFaction] You are not leader!");
							}
						} else {
							$sender->sendMessage(TextFormat::RED."[CyberFaction] You are not in a faction!");
						}
					}
					
					//Leave
					
					if(strtolower($args[0] == "leave")) {
						if($this->plugin->isLeader($player) == false) {
							$faction = $this->plugin->getPlayerFaction($player);
                                                        $this->plugin->DeleteChache($player, "faction");
							$name = $sender->getName();
							@mysqli_query($this->plugin->db,"DELETE FROM `master` WHERE `player` = '$name';");
							$sender->sendMessage(TextFormat::GREEN."[CyberFaction] You successfully left $faction");
                                                        $this->plugin->DeleteChache($player, "getPlayerFaction");
                                                        $this->plugin->DeleteChache($player, "isInFaction");
						} else {
							$sender->sendMessage(TextFormat::RED."[CyberFaction] You must delete or give leadership to someone else first!!!");
						}
					}
					
					//Home
					
					if(strtolower($args[0] == "sethome")) {
						$factionName = $this->plugin->getPlayerFaction($sender->getName());
                                                $x = $sender->getX();
                                                $y = $sender->getY();
                                                $z = $sender->getZ();
                                                @mysqli_query($this->plugin->db,"DELETE FROM `home` WHERE `faction` = '$faction';");
						@mysqli_query($this->plugin->db,"INSERT INTO `home` (`faction`, `x`, `y`, `z`) VALUES ('$factionName', '$x', '$y', '$z');");
						$sender->sendMessage(TextFormat::GREEN."[CyberFaction] Home updated!");
					}
					
					if(strtolower($args[0] == "unsethome")) {
						$faction = $this->plugin->getPlayerFaction($sender->getName());
						@mysqli_query($this->plugin->db,"DELETE FROM `home` WHERE `faction` = '$faction';");
						$sender->sendMessage(TextFormat::GREEN."[CyberFaction] Home unset!");
					}
					
					if(strtolower($args[0]) == "help") {
						$sender->sendMessage(TextFormat::GRAY."FactionsPro Commands\n".TextFormat::AQUA
                                                        . "/f create <name>\n"
                                                        . "/f del\n/f help\n"
                                                        . "/f invite <player>\n"
                                                        . "/f kick <player>\n"
                                                        . "/f leave\n"
                                                        . "/f leader <player>\n"
                                                        . "/f leave\n"
                                                        . "/f motd\n"
                                                        . "/f info\n"
                                                        . "/f chat\n"
                                                        . "/f claim\n"
                                                        . "/f unclaim\n"
                                                        . "/f home [faction]\n"
                                                        . "/f sethome\n"
                                                        . "/f ally add|remove <faction>");
					}
				} else {
                                    
					$sender->sendMessage(TextFormat::GRAY."[CyberFaction] Please use /f help for a list of commands");
				}
			}
		} else {
			$this->plugin->getServer()->getLogger()->info(TextFormat::RED . "[CyberFaction] Please run command in game");
		}
	}
}