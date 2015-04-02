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

class FactionMain extends PluginBase implements Listener {
	
	public $db;
	public $prefs;
	public $work;
        public $api;
	public $tblock;
        public $wars;
        public $atwar = array();


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
		$this->db = new \SQLite3($this->getDataFolder() . "FactionsPro.db");
		$this->db->exec("CREATE TABLE IF NOT EXISTS master (player TEXT PRIMARY KEY COLLATE NOCASE, faction TEXT, rank TEXT);");
		$this->db->exec("CREATE TABLE IF NOT EXISTS confirm (player TEXT PRIMARY KEY COLLATE NOCASE, faction TEXT, invitedby TEXT, timestamp INT);");
		//$this->db->exec("CREATE TABLE IF NOT EXISTS descRCV (player TEXT PRIMARY KEY, timestamp INT);");
		//$this->db->exec("CREATE TABLE IF NOT EXISTS desc (faction TEXT PRIMARY KEY, description TEXT);");
		$this->db->exec("CREATE TABLE IF NOT EXISTS plots(id INTEGER PRIMARY KEY AUTOINCREMENT,faction TEXT, x1 INT, z1 INT, x2 INT, z2 INT);");
		$this->db->exec("CREATE TABLE IF NOT EXISTS home(faction TEXT PRIMARY KEY, x INT, y INT, z INT);");
                $this->db->exec("CREATE TABLE IF NOT EXISTS war(attacker TEXT PRIMARY KEY, defender TEXT, start INTIGER, active TEXT );");
	}
		
	public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
		$this->fCommand->onCommand($sender, $command, $label, $args);
	}
	public function isInFaction($player) {
		$player = strtolower($player);
		$result = $this->db->query("SELECT * FROM master WHERE player='$player';");
		$array = $result->fetchArray(SQLITE3_ASSOC);
		return empty($array) == false;
	}
	public function isLeader($player) {
		$faction = $this->db->query("SELECT * FROM master WHERE player='$player';");
		$factionArray = $faction->fetchArray(SQLITE3_ASSOC);
		if(empty($factionArray)) {
			return false;
		}
		return $factionArray["rank"] == "Leader";
	}
	public function isOfficer($player) {
		$faction = $this->db->query("SELECT * FROM master WHERE player='$player';");
		$factionArray = $faction->fetchArray(SQLITE3_ASSOC);
		if(empty($factionArray)) {
			return false;
		}
		return $factionArray["rank"] == "Officer";
	}
	public function isMember($player) {
		$faction = $this->db->query("SELECT * FROM master WHERE player='$player';");
		$factionArray = $faction->fetchArray(SQLITE3_ASSOC);
		return $factionArray["rank"] == "Member";
	}
	public function getPlayerFaction($player) {
		$faction = $this->db->query("SELECT * FROM master WHERE player='$player';");
		$factionArray = $faction->fetchArray(SQLITE3_ASSOC);
                if (!empty($factionArray["faction"]) && $factionArray["faction"] != ""){
                    return $factionArray["faction"];
                }
		return false;
	}
	public function getLeader($faction) {
		$leader = $this->db->query("SELECT * FROM master WHERE faction='$faction' AND rank='Leader';");
		$leaderArray = $leader->fetchArray(SQLITE3_ASSOC);
		return $leaderArray['player'];
	}
	public function factionExists($faction) {
		$result = $this->db->query("SELECT * FROM master WHERE faction='$faction';");
		$array = $result->fetchArray(SQLITE3_ASSOC);
		return empty($array) == false;
	}
        public function factionPartialName($faction) {
            $result = $this->db->query("SELECT * FROM master WHERE `faction` LIKE '$faction%';");
		$array = $result->fetchArray(SQLITE3_ASSOC);
		if (empty($array)){
                    return FALSE;
                }else{
                    return $array['faction'];
                }
        }
	public function sameFaction($player1, $player2) {
		$faction = $this->db->query("SELECT * FROM master WHERE player='$player1';");
		$player1Faction = $faction->fetchArray(SQLITE3_ASSOC);
		$faction = $this->db->query("SELECT * FROM master WHERE player='$player2';");
		$player2Faction = $faction->fetchArray(SQLITE3_ASSOC);
		return $player1Faction["faction"] == $player2Faction["faction"];
	}
	public function getNumberOfPlayers($faction) {
		$query = $this->db->query("SELECT COUNT(*) as count FROM master WHERE faction='$faction';");
		$number = $query->fetchArray();
		return $number['count'];
	}
	public function isFactionFull($faction) {
		return $this->getNumberOfPlayers($faction) >= $this->prefs->get("MaxPlayersPerFaction");
	}
	
            public function newPlot($faction, $x1, $z1, $x2, $z2) {
		$stmt = $this->db->prepare("INSERT OR REPLACE INTO plots (faction, x1, z1, x2, z2, id) VALUES (:faction, :x1, :z1, :x2, :z2, NULL);");
		$stmt->bindValue(":faction", $faction);
		$stmt->bindValue(":x1", $x1);
		$stmt->bindValue(":z1", $z1);
		$stmt->bindValue(":x2", $x2);
		$stmt->bindValue(":z2", $z2);
		$result = $stmt->execute();
	}
        
        public function FactionHasPlot($faction){
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM plots WHERE faction = '$faction';");
            //$n = mysql_num_rows($result);
            $factionArray = $stmt->fetchArray();
		if($factionArray['count'] > 0) {
                    return true;
                }else{
                    return false;
                }
            return true;
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
                    $sender->sendMessage("You Can Not Claim In Spawn!!!");
                    return true;
                }
                if ($this->pointIsInSpawn($x - $arm,$z + $arm)){
                    $sender->sendMessage("You Can Not Claim In Spawn!!!");
                    return true;
                }
                if ($this->pointIsInSpawn($x + $arm,$z - $arm)){
                    $sender->sendMessage("You Can Not Claim In Spawn!!!");
                    return true;
                }
                if ($this->pointIsInSpawn($x - $arm,$z - $arm)){
                    $sender->sendMessage("You Can Not Claim In Spawn!!!");
                    return true;
                }
                
                if ( in_array(false, $a)){
                    $sender->sendMessage("Uh Oh! Someones Plot is There \nPlease Make Sure that Your Plot is Valid!");
                    return true;
                }
                
                if ($this->FactionHasPlot($faction)){
                    if ($b){
                        $this->newPlot($faction, $x + $arm, $z + $arm, $x - $arm, $z - $arm);
                        $sender->sendMessage("[FactionsPro] Plot claimed.");
                        $level->setBlock(new Vector3($x + $arm, $y, $z + $arm), $block);
                        $level->setBlock(new Vector3($x - $arm, $y, $z - $arm), $block);
                        $level->setBlock(new Vector3($x, $y-1, $z), $block);
                        return true;
                    }else{
                        $sender->sendMessage("Please Make Sure that your Plots are Connected!");
                        return true;
                    }
                }
                
		$this->newPlot($faction, $x + $arm, $z + $arm, $x - $arm, $z - $arm);
		$sender->sendMessage("[FactionsPro] Plot claimed.");
                $level->setBlock(new Vector3($x + $arm, $y, $z + $arm), $block);
		$level->setBlock(new Vector3($x - $arm, $y, $z - $arm), $block);
		$level->setBlock(new Vector3($x, $y-1, $z), $block);
                return true;
	}
	
	public function updatePlots() {
	}
	
	public function plotChecker($onlinePlayers) {
		foreach($onlinePlayers as $player) {
			if($this->isInPlot($player)) {
				$player->sendMessage("[FactionsPro] You are in a plot.");
			}
		}
	}
	
	public function isInPlot($player) {
		$x = $player->getFloorX();
		$z = $player->getFloorZ();
		$result = $this->db->query("SELECT * FROM plots WHERE $x <= x1 AND $x >= x2 AND $z <= z1 AND $z >= z2;");
		$array = $result->fetchArray(SQLITE3_ASSOC);
		return empty($array) == false;
	}
	
	public function factionFromPoint($x,$z) {
		$result = $this->db->query("SELECT * FROM plots WHERE $x <= x1 AND $x >= x2 AND $z <= z1 AND $z >= z2;");
		$array = $result->fetchArray(SQLITE3_ASSOC);
		return $array["faction"];
	}
	
	public function inOwnPlot($player) {
		$playerName = $player->getName();
		$x = $player->getFloorX();
		$z = $player->getFloorZ();
		$result = $this->db->query("SELECT * FROM plots WHERE $x <= x1 AND $x >= x2 AND $z <= z1 AND $z >= z2;");
		$array = $result->fetchArray(SQLITE3_ASSOC);
		return $this->getPlayerFaction($playerName) == $array['faction'];
	}
	
	public function pointIsInPlot($x,$z) {
		$result = $this->db->query("SELECT * FROM plots WHERE $x <= x1 AND $x >= x2 AND $z <= z1 AND $z >= z2;");
		$array = $result->fetchArray(SQLITE3_ASSOC);
		return !empty($array);
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
        
        public function FacQualify($fac, $type) {
            if ($type == "d" && isset($this->wars["DEFENDS"][$fac])){
                if ($this->wars["DEFENDS"][$fac] > strtotime("now")){
                    return false;
                }
            }
            if ($type == "a" && isset($this->wars["ATTACKS"][$fac])){
                if ($this->wars["ATTACKS"][$fac] > strtotime("now")){
                    return false;
                }
            }
            return true;
        }
        
        public function NotifyOfficers($fac, $message) {
            if(!$this->factionExists($fac)){
                return false;
            }
            foreach ($this->getServer()->getOnlinePlayers() as $p){
                if ($this->isInFaction($p->getName())){
                    if ($this->getPlayerFaction($p->getName()) == $fac){
                        if ($this->isOfficer($p->getName()) || $this->isLeader($p->getName())){
                         $p->sendMessage($message);   
                         return true;
                        }
                    }
                }
            }
        }
        
        public function DeclareWar($attackers , $defenders) {
            if (! $this->FacQualify($attackers, "a")){
                $this->NotifyOfficers($attackers, "You Dont Qualify To Attack!");
                return true;   
            }
            
            if (! $this->FacQualify($defenders, "d")){
                $this->NotifyOfficers($attackers, "$defenders Dont Qualify To Defend!");
                return true;
            }
            $this->wars["ATTACKS"][$attackers] = strtotime("+6 Hours");
            $this->wars["DEFENDS"][$defenders] = strtotime("+6 Hours");
            $this->atwar[$attackers] = $defenders;
            $this->getServer()->getScheduler()->scheduleDelayedTask(new EndWar($this, $attackers), 20*60*30); //30 Mins
            $this->getServer()->broadcastMessage("$attackers's Faction Has Just Declared War on $defenders's Faction!");
            $this->NotifyWar($attackers);
            $this->NotifyWar($defenders);
            
        }
        
        public function AtWar($fac, $fac2) {
            if ($fac instanceof Player){
                $fac = $this->getPlayerFaction($fac);
            }
            if ($fac2 instanceof Player){
                $fac2 = $this->getPlayerFaction($fac2);
            }
            if (isset($this->atwar[$fac]) && $this->atwar[$fac] == $fac2){
                return true;
            }
                return false;
        }
        
        public function NotifyWar($fac) {
            foreach($this->getServer()->getOnlinePlayers() as $p){
                if ($this->getPlayerFaction($p->getName()) == $fac){
                    $p->sendMessage("Your Faction Is Now At WAR!");
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
            
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM plots WHERE faction = '$fac';");
            //$n = mysql_num_rows($result);
            $factionArray = $stmt->fetchArray();
            if ($factionArray['count'] == 0){
                return false;
            }
            $nn = rand(0, ($factionArray['count'] - 1));
            $stmt1 = $this->db->query("SELECT * FROM plots WHERE faction = '$fac' LIMIT $nn,1;");
            //$n = mysql_num_rows($result);
            $factionArray1 = $stmt1->fetchArray(SQLITE3_ASSOC);
            $x = $factionArray1['x1'] + rand((-1 * abs($num)) , (1 * abs($num)) );
            $z = $factionArray1['z1'] + rand((-1 * abs($num)) , (1 * abs($num)) );
            $v3 = $this->GetLoadedLevel()->getSafeSpawn(new Vector3( $x , 0 , $z ));
            $v = new Vector3($v3->getX(), $v3->getY(), $v3->getZ());
            return $v;
        }
        
        public function GetLoadedLevel() {
            foreach ($this->getServer()->getLevels() as $l){
                if ($l instanceof Level){
                    return $l;
                }
            }
        }

        public function onDisable() {
		$this->db->close();
	}
}
