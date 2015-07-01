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
use pocketmine\block\Snow;
use pocketmine\math\Vector3;
use pocketmine\level\Level;
use onebone\economyapi\EconomyAPI;
use FactionsPro\War\EndWar;
use FactionsPro\FactionCommands;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\entity\Entity;

class FactionMain extends PluginBase implements Listener {
	
	public $db;
	public $prefs;
	public $work;
        public $api;
	public $tblock;
	public $sneak;
        public $wars;
        public $atwar = array();
        public $chache = array();


        public function onEnable() {
		@mkdir($this->getDataFolder());
		
		$this->getServer()->getPluginManager()->registerEvents(new FactionListener($this), $this);
		$this->fCommand = new FactionCommands($this);
		 $this->api = EconomyAPI::getInstance();
		$this->prefs = new Config($this->getDataFolder() . "Prefs.yml", CONFIG::YAML, array(
                    "MaxFactionNameLength" => 20,
                    "MaxPlayersPerFaction" => 10,
                    "OnlyLeadersAndOfficersCanInvite" => true,
                    "OfficersCanClaim" => true,
                    "PlotPrice" => 100,
                    "OfficerIdentifier" => '*',
                    "LeaderIdentifier" => '**',
		));
                $this->wars = (new Config($this->getDataFolder() . "Wars.yml", CONFIG::YAML, array(
                    "ATTACKS" =>array(),
                    "DEFENDS" => array()
                )))->getAll();
                $this->tblock = (new Config($this->getDataFolder() . "Prefs.yml", CONFIG::YAML, array()))->getAll();
		//$this->db = new \SQLite3($this->getDataFolder() . "FactionsPro.db");
                $db_ip = "0.0.0.0";
                $db_user = "admin_factions";
                $db_pass = "admin_factions";
                $db_databes = "admin_factions";
                $this->db = @mysqli_connect($db_ip, $db_user, $db_pass,$db_databes,3306);
                if(!$this->db){
                    $this->getServer()->getLogger()->error("DATABSE Error!!!".mysqli_connect_error());
                }else{
                    $this->getServer()->getLogger()->notice("DB Successfully Loaded!");
                }
		/*@mysqli_query($this->db,"CREATE TABLE IF NOT EXISTS master (player TEXT PRIMARY KEY COLLATE NOCASE, faction TEXT, rank TEXT);");
		@mysqli_query($this->db,"CREATE TABLE IF NOT EXISTS confirm (player TEXT PRIMARY KEY COLLATE NOCASE, faction TEXT, invitedby TEXT, timestamp INT);");
		@mysqli_query($this->db,"CREATE TABLE IF NOT EXISTS allies (factiona TEXT PRIMARY KEY COLLATE NOCASE, factionb TEXT, timestamp INT);");
		//@mysqli_query($this->db,"CREATE TABLE IF NOT EXISTS descRCV (player TEXT PRIMARY KEY, timestamp INT);");
		//@mysqli_query($this->db,"CREATE TABLE IF NOT EXISTS desc (faction TEXT PRIMARY KEY, description TEXT);");
		@mysqli_query($this->db,"CREATE TABLE IF NOT EXISTS plots(id INTEGER PRIMARY KEY AUTOINCREMENT,faction TEXT, x1 INT, z1 INT, x2 INT, z2 INT);");
		@mysqli_query($this->db,"CREATE TABLE IF NOT EXISTS home(faction TEXT PRIMARY KEY, x INT, y INT, z INT);");
                @mysqli_query($this->db,"CREATE TABLE IF NOT EXISTS war(attacker TEXT PRIMARY KEY, defender TEXT, start INTIGER, active TEXT );");
                @mysqli_query($this->db,"CREATE TABLE IF NOT EXISTS power(faction TEXT PRIMARY KEY, power INTIGER );");*/
	}
		
	public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
		$this->fCommand->onCommand($sender, $command, $label, $args);
	}
	public function isInFaction($player) {
        if($player instanceof Player)$player = $player->getName();
        $player = strtolower($player);
        $a = $this->GetChache($player, "isInFaction");
        if($a !== false){
            if($a == "yes")return true;
            if($a == "no")return false;
            return false;
        }
		$result = @mysqli_query($this->db,"SELECT * FROM `master` WHERE `player`='$player';");
		$array = @mysqli_num_rows($result);
        if($array > 0){
            $this->SetChache($player, "isInFaction", "yes");
            return true;
        }else{
            $this->SetChache($player, "isInFaction", "no");
            return false;
        }
	}
	public function isLeader($player) {
		$faction = @mysqli_query($this->db,"SELECT * FROM `master` WHERE `player`='$player';");
		$factionArray = @mysqli_fetch_assoc($faction);
                $count = @mysqli_num_rows($faction);
		if($count <= 0) {
			return false;
		}
		return $factionArray["rank"] == "Leader";
	}
	public function isOfficer($player) {
		$faction = @mysqli_query($this->db,"SELECT * FROM `master` WHERE `player`='$player';");
		$factionArray = @mysqli_fetch_assoc($faction);
                $count = @mysqli_num_rows($faction);
		if($count <= 0) {
			return false;
		}
		return $factionArray["rank"] == "Officer";
	}
	public function isMember($player) {
		$faction = @mysqli_query($this->db,"SELECT * FROM `master` WHERE `player`='$player';");
		$factionArray = @mysqli_fetch_assoc($faction);
                $count = @mysqli_num_rows($faction);
                if($count <= 0){
                    return false;
                }
                return $factionArray["rank"] == "Member";
	}
	public function getPlayerFaction($player) {
        $a = $this->GetChache($player, "getPlayerFaction");
        if($a !== false)return $a;
        $faction = @mysqli_query($this->db,"SELECT * FROM `master` WHERE `player`='$player';");
        $factionArray = @mysqli_fetch_assoc($faction);
        $count = @mysqli_num_rows($faction);
        if ($count > 0 && $factionArray["faction"] != "" && $factionArray["faction"] != 1){
            $this->SetChache($player, "getPlayerFaction", $factionArray["faction"]);
            if($color == true)return $factionArray["faction-color"];
            return $factionArray["faction"];
        }
		return false;
	}

    public function getFactionColor($faction){
        $a = $this->GetChache($faction, "getFactionColor");
        if($a !== false){
            if($a == "yes")return true;
            if($a == "no")return false;
            return false;
        }
        $faction = @mysqli_query($this->db,"SELECT * FROM `settings` WHERE `faction`='$faction';");
        $count = @mysqli_num_rows($faction);
        $row = @mysqli_fetch_assoc($faction);
        if($count > 0){
            if($row['color'] == ""){
                $this->SetChache($faction, "getFactionColor", "no");
                return false;
            }
            $this->SetChache($faction, "getFactionColor", "yes");
            return $row['color'];
        }
        $this->SetChache($faction, "getFactionColor", "no");
        return false;
    }
        
        public function GetChache($player, $key) {
            if(!isset($this->chache[$key][$player]) || !isset($this->chache[$key]))return false;
            return $this->chache[$key][$player];
        }
        
        public function SetChache($player, $key, $value) {
            $this->chache[$key][$player] = $value;
        }
        
        public function DeleteChache($player, $key) {
            if(isset($this->chache[$key][$player]))unset($this->chache[$key][$player]);
        }
        
	public function getLeader($faction) {
		$leader = @mysqli_query($this->db,"SELECT * FROM `master` WHERE `faction`='$faction' AND `rank`='Leader';");
		$leaderArray = @mysqli_fetch_assoc($leader);
                $count = @mysqli_num_rows($leaderArray);
                if($count <= 0){
                    return false;
                }
		return $leaderArray['player'];
	}
        /**
         * Check if the faction exists
         * @param string $faction Faction in question
         * @return Boolean if Faction exists
         */
	public function factionExists($faction) {
                $a = $this->GetChache($faction, "factionExists");
                if($a !== false){
                    if($a == true)return $a;
                    if($a == "no")return false;
                }
		        $result = @mysqli_query($this->db,"SELECT * FROM `master` WHERE `faction` = '$faction';");
                $count = @mysqli_num_rows($result);
                if($count == 0){
                    $this->SetChache($faction, "factionExists", "no");
                    return false;
                }
                $this->SetChache($faction, "factionExists", true);
                return true;
                
                    
	}
        public function factionPartialName($faction) {
            $result = @mysqli_query($this->db,"SELECT * FROM `master` WHERE `faction` LIKE '$faction%';");
		$array = @mysqli_fetch_assoc($result);
		if (empty($array)){
                    return false;
                }else{
                    return $array['faction'];
                }
        }
	public function sameFaction($player1, $player2) {
            $faction1 = $this->getPlayerFaction($player1);
            $faction2 = $this->getPlayerFaction($player2);
            if($faction1 == false)return false;
            if($faction2 == false)return false;
            if($faction1 == $faction2){return true;}else{return false;}
	}
	public function getNumberOfPlayers($faction) {
            $query = @mysqli_query($this->db,"SELECT * FROM `master` WHERE `faction` = '$faction';");
            $number = @mysqli_num_rows($query);
            return $number;
	}
	public function isFactionFull($faction) {
            return $this->getNumberOfPlayers($faction) >= $this->prefs->get("MaxPlayersPerFaction");
	}
	
        public function newPlot($faction, $x1, $z1, $x2, $z2) {
            @mysqli_query($this->db, "REPLACE INTO `plots` (`faction`, `x1`, `z1`, `x2`, `z2`, `id`) VALUES ('$faction', '$x1', '$z1', '$x2', '$z2', NULL);");
	}
        
        public function FactionHasPlot($faction){
            $stmt = @mysqli_query($this->db,"SELECT * FROM `plots` WHERE `faction` = '$faction';");
            $count = @mysqli_num_rows($stmt);
            if($count > 0) {
                return true;
            }else{
                return false;
            }
        }
	public function drawPlot($sender, $faction, $x, $y, $z, $level, $size) {
                $a[1] = true;
                $a[2] = true;
                $a[3] = true;
                $a[4] = true;
                $b = FALSE;
		$arm = ($size - 1) / 2;
		$block = new Snow();
                if ($this->pointIsInPlot($x + $arm,$z + $arm)){
                    if ($faction !== $this->factionFromPoint($x + $arm,$z + $arm)){
                        $a[1] = false;
                    }else{
                        $b = true;
                    }
                }
                if ($this->pointIsInPlot($x + $arm,$z - $arm)){
                    if ($faction !== $this->factionFromPoint($x + $arm,$z - $arm)){
                        $a[2] = false;
                    }else{
                        $b = true;
                    }
                }
                if ($this->pointIsInPlot($x - $arm,$z - $arm)){
                    if ($faction !== $this->factionFromPoint($x - $arm,$z - $arm)){
                        $a[3] = false;
                    }else{
                        $b = true;
                    }
                }
                if ($this->pointIsInPlot($x - $arm,$z + $arm)){
                    if ($faction !== $this->factionFromPoint($x - $arm,$z + $arm)){
                        $a[4] = false;
                    }else{
                        $b = true;
                    }
                }
                
                if ($this->pointIsInSpawn($x + $arm,$z + $arm)){
                    $sender->sendMessage(TextFormat::GRAY."You Can Not Claim In Spawn!!!");
                    return true;
                }
                if ($this->pointIsInSpawn($x - $arm,$z + $arm)){
                    $sender->sendMessage(TextFormat::GRAY."You Can Not Claim In Spawn!!!");
                    return true;
                }
                if ($this->pointIsInSpawn($x + $arm,$z - $arm)){
                    $sender->sendMessage(TextFormat::GRAY."You Can Not Claim In Spawn!!!");
                    return true;
                }
                if ($this->pointIsInSpawn($x - $arm,$z - $arm)){
                    $sender->sendMessage(TextFormat::GRAY."You Can Not Claim In Spawn!!!");
                    return true;
                }
                
                if ( in_array(false, $a)){
                    $sender->sendMessage(TextFormat::GRAY."Uh Oh! Someones Plot is There \nPlease Make Sure that Your Plot is Valid!");
                    return true;
                }
                
                if ($this->FactionHasPlot($faction)){
                    if ($b){
                        $this->newPlot($faction, $x + $arm, $z + $arm, $x - $arm, $z - $arm);
                        $sender->sendMessage(TextFormat::GRAY."[CyberFaction] Plot claimed.");
                        $level->setBlock(new Vector3($x + $arm, $y, $z + $arm), $block);
                        $level->setBlock(new Vector3($x - $arm, $y, $z - $arm), $block);
                        $level->setBlock(new Vector3($x, $y-1, $z), $block);
                        return true;
                    }else{
                        $sender->sendMessage(TextFormat::GRAY."Please Make Sure that your Plots are Connected!");
                        return true;
                    }
                }
                
		$this->newPlot($faction, $x + $arm, $z + $arm, $x - $arm, $z - $arm);
		$sender->sendMessage(TextFormat::GRAY."[CyberFaction] Plot claimed.");
                $level->setBlock(new Vector3($x + $arm, $y, $z + $arm), $block);
		$level->setBlock(new Vector3($x - $arm, $y, $z - $arm), $block);
		$level->setBlock(new Vector3($x, $y-1, $z), $block);
                return true;
	}
	
	public function updatePlots() {
	}
	
	public function plotChecker($onlinePlayers) {
		foreach($onlinePlayers as $player) {
			if($this->isInPlot($player) && $player instanceof Player) {
				$player->sendTip(TextFormat::YELLOW."[CyberFaction] You are in a plot.");
			}
		}
	}
	
	public function isInPlot($player) {
		$x = $player->getFloorX();
		$z = $player->getFloorZ();
		$result = @mysqli_query($this->db,"SELECT * FROM `plots` WHERE '$x' <= `x1` AND '$x' >= `x2` AND '$z' <= `z1` AND '$z' >= `z2`;");
		$array = @mysqli_fetch_assoc($result);
		return empty($array) == false;
	}
	
	public function factionFromPoint($x,$z) {
                if($a = $this->GetChache("$x|$z", "factionFromPoint") !== false)return $a;
		$result = @mysqli_query($this->db,"SELECT * FROM `plots` WHERE '$x' <= `x1` AND '$x' >= `x2` AND '$z' <= `z1` AND '$z' >= `z2`;");
		$array = @mysqli_fetch_assoc($result);
                $count = @mysqli_num_rows($result);
		if($count == 0)return false;
                $this->SetChache("$x|$z", "factionFromPoint", $array["faction"]);
                return $array["faction"];
	}
	
	public function inOwnPlot($player) {
		$playerName = $player->getName();
		$x = $player->getFloorX();
		$z = $player->getFloorZ();
		$result = @mysqli_query($this->db,"SELECT * FROM `plots` WHERE '$x' <= `x1` AND '$x' >= `x2` AND '$z' <= `z1` AND '$z' >= `z2`;");
		$array = @mysqli_fetch_assoc($result);
                $count = @mysqli_num_rows($result);
                if($count == 0)return true;
		return $this->getPlayerFaction($playerName) == $array['faction'];
	}
	
	public function pointIsInPlot($x,$z) {
		$result = @mysqli_query($this->db,"SELECT * FROM `plots` WHERE '$x' <= `x1` AND '$x' >= `x2` AND '$z' <= `z1` AND '$z' >= `z2`;");
		$count = @mysqli_num_rows($result);
		if ($count == 0)return false;
		return true;
	}
	
        public function pointIsInSpawn($x , $z) {
            if ((-19.973501 < $x) && ($x < -128.724579)){
                 return true;   
                }
            if ((-721.343689 < $z) && ($x < -843.869751)){
                return true;
            }
            return false;
        }
        
	public function cornerIsInPlot($x1, $z1, $x2, $z2) {
		return($this->pointIsInPlot($x1, $z1) || $this->pointIsInPlot($x1, $z2) || $this->pointIsInPlot($x2, $z1) || $this->pointIsInPlot($x2, $z2));
	}
        
        /**
         * Checks if faction is eleigble to declare war or defend!
         * 
         *
         */
        public function FacQualify($attackers,$defenders) {
            if (isset($this->wars["DEFENDS"][$defenders])){
                if ($this->wars["DEFENDS"][$defenders] > strtotime("now")){
                    $timeLeft = $this->wars["DEFENDS"][$defenders] - time();
                    $left = gmdate("H:i:s", $timeLeft);
                    $this->NotifyOfficers($attackers, TextFormat::RED."You need to wait $left before this faction can be attacked again!!!");
                    return false;
                }
            }
            if (isset($this->wars["ATTACKS"][$attackers])){
                if ($this->wars["ATTACKS"][$attackers] > strtotime("now")){
                    $timeLeft = $this->wars["ATTACKS"][$attackers] - time();
                    $left = gmdate("H:i:s", $timeLeft);
                    $this->NotifyOfficers($attackers, TextFormat::RED."You need to wait $left before you can attack another faction!!!");
                }
                if($this->GetFactionPower($attackers) < 50){
                    $this->NotifyOfficers($attackers, TextFormat::RED."You Dont Have Enough Power To Attack!!!");
                    return false;
                }
            }
            return true;
        }
        
        /**
         * Notify Officers of the faction of a message!
         * 
         * @param string $fac Notify this Faction's Officers!
         * @param type $message Message to Notify Them
         * @param boolean $popup Should the Message be a Pop Up?
         * @return boolean
         */
        public function NotifyOfficers($fac, $message, $popup = false) {
            if(!$this->factionExists($fac)){
                return false;
            }
            foreach ($this->getServer()->getOnlinePlayers() as $p){
                if ($this->isInFaction($p->getName())){
                    if ($this->getPlayerFaction($p->getName()) == $fac){
                        if ($this->isOfficer($p->getName()) || $this->isLeader($p->getName())){
                            if($popup == false)$p->sendMessage(TextFormat::GRAY.$message);   
                            if($popup == true)$p->sendPopup(TextFormat::GRAY.$message);   
                            return true;
                        }
                    }
                }
            }
        }
        
        /**
         * 1st Part To Declaer War on A Faction!
         * 
         * @param string $attackers Attacking Faction
         * @param strign $defenders Defending Faction
         * @return boolean
         * 
         */
        public function DeclareWar($attackers , $defenders) {
            if (! $this->FacQualify($attackers, $defenders))return true;
            $this->wars["ATTACKS"][$attackers] = strtotime("+1 Hours");
            $this->wars["DEFENDS"][$defenders] = strtotime("+3 Hours");
            $this->atwar[$attackers] = $defenders;
            $this->getServer()->getScheduler()->scheduleDelayedTask(new EndWar($this, $attackers), 20*60*30); //30 Mins
            $this->getServer()->broadcastMessage(TextFormat::LIGHT_PURPLE."$attackers's Faction Has Just Declared War on $defenders's Faction!");
            $this->NotifyWar($attackers);
            $this->NotifyWar($defenders);
            
        }
        
        public function AtWar($fac, $fac2) {
            if ($fac instanceof Player)$fac = $this->getPlayerFaction($fac);
            if ($fac2 instanceof Player)$fac2 = $this->getPlayerFaction($fac2);
            if (isset($this->atwar[$fac]) && ($this->atwar[$fac] == $fac2 || $this->atwar[$fac2] == $fac))return true;
            return false;
        }
        
        public function NotifyWar($fac) {
            foreach($this->getServer()->getOnlinePlayers() as $p){
                if ($this->getPlayerFaction($p->getName()) == $fac){
                    $p->sendMessage(TextFormat::RED."Your Faction Is Now At WAR!");
                }
            }
        }
        
        /*
         * Get War Time acceptible TP
         * 
         * @param $fac Facton To TP to
         * @param $num Spawn Radius To Atempt to TP them to.
         */
        public function GetRandomTPArea($fac, $num){
            if (!$this->factionExists($fac)){
                return false;
            }
            
            $stmt = @mysqli_query($this->db,"SELECT * FROM `plots` WHERE `faction` = '$fac';");
            //$n = mysql_num_rows($result);
            $factionArray = $stmt->fetchArray();
            if ($factionArray['count'] == 0){
                return false;
            }
            $nn = rand(0, ($factionArray['count'] - 1));
            $stmt1 = @mysqli_query($this->db,"SELECT * FROM `plots` WHERE `faction` = '$fac' LIMIT $nn,1;");
            //$n = mysql_num_rows($result);
            $factionArray1 = $stmt1->fetchArray(SQLITE3_ASSOC);
            $x = $factionArray1['x1'] + rand((-1 * abs($num)) , (1 * abs($num)) );
            $z = $factionArray1['z1'] + rand((-1 * abs($num)) , (1 * abs($num)) );
            $v3 = $this->GetLoadedLevel()->getSafeSpawn(new Vector3( $x , 0 , $z ));
            $v = new Vector3($v3->getX(), $v3->getY(), $v3->getZ());
            return $v;
        }
        
        public function GetFactionPower($faction) {
            $result = @mysqli_query($this->db,"SELECT * FROM `power` WHERE `faction` = '$faction';");
            $array = @mysqli_fetch_assoc($result);
            return $array['power'];
        }
        
        public function AddFactioPower($faction , $power) {
            $power += $this->GetFactionPower($faction);
            @mysqli_query($this->db,"UPDATE `power` SET `power` = '$power' WHERE `faction` = '$faction';");
        }
        
        public function TakeFactionPower($faction, $power) {
            $powera = $this->GetFactionPower($faction);
            $fpower = $power - $powera;
            @mysqli_query($this->db,"UPDATE `power` SET `power` = '$fpower' WHERE `faction` = '$faction';");
        }
        
        public function isFactionsAllyed($faction1, $faction2) {
            $result = @mysqli_query($this->db,"SELECT * FROM `ally` WHERE `factiona` = '$faction1' OR `factionb` = '$faction1';");
            while($row = @mysqli_fetch_assoc($result)){
                if($row['factiona'] == $faction2)return true;
                if($row['factionb'] == $faction2)return true;
            }
            return false;
        }
        
        public function AddAlliance($faction1 , $faction2) {
            if($this->isFactionsAllyed($faction1, $faction2))return false;
            $time = strtotime("now");
            @mysqli_query($this->db,"INSERT INTO `ally` VALUES ('$faction1', '$faction2', '$time');");
        }
        
        /**
         * 
         * @param type $faction
         * @return Player
         */
        public function GetRandomFactionPlayer($faction) {
            foreach ($this->getServer()->getOnlinePlayers() as $player) {
                if($this->getPlayerFaction($player) == $faction){
                    $players[] = $player;
                }
            }
            $n = mt_rand(0, count($players)-1);
            return $players[$n];
        }
        
        public function RemoveAlliance($faction1, $faction2) {
            if(!$this->isFactionsAllyed($faction1, $faction2))return false;
            $time = strtotime("now");
            @mysqli_query($this->db,"DELETE FROM `ally` WHERE `factiona` = '$faction1' AND `factionb` = '$faction2';");
            @mysqli_query($this->db,"DELETE FROM `ally` WHERE `factiona` = '$faction2' AND `factionb` = '$faction1';");
        }
        
        public function MessageFaction($faction, $message, $popup = false) {
            foreach($this->getServer()->getOnlinePlayers() as $p){
                $factionb = $this->getPlayerFaction($p->getName());
                if($factionb == false || $factionb == null && strtolower($faction) == strtolower($factionb))break;
                $player = $p;
                if($popup == false){
                    $player->sendMessage($message);
                }else{
                    $player->sendPopup($message);
                }
            }
        }
        
        public function GetLoadedLevel() {
            foreach ($this->getServer()->getLevels() as $l){
                if ($l instanceof Level){
                    return $l;
                }
            }
        }

        public function onDisable() {
		if($this->db)$this->db->close();
	}
        
        public function ToggleSneak($player) {
            if($player instanceof Player)$player = $player->getName();
            if(isset($this->sneak[$player])){
                if($this->sneak[$player] == true){
                    $player->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_SNEAKING, false);
                    $this->sneak[$player] = false;
                }elseif($this->sneak[$player] == false){
                    $player->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_SNEAKING, true);
                    $this->sneak[$player] = true;
                }
            }else{
                $player->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_SNEAKING, true);
                $this->sneak[$player] = true;
            }
        }
        
        public function PlayerInteractEvent(PlayerInteractEvent $event) {
            if($event->getPlayer()->getInventory()->getItemInHand() == 347)$this->ToggleSneak($event->getPlayer());
            if($event->getPlayer()->getInventory()->getItemInHand() == 345){
                $this->getServer()->dispatchCommand($event->getPlayer(), "home 1");
            }
            if($event->getPlayer()->getInventory()->getItemInHand() == 120){
                $this->getServer()->dispatchCommand($event->getPlayer(), "f home");
            }
        }
}
