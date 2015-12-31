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
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\network\protocol\UpdateAttributesPacket;

class FactionMain extends PluginBase implements Listener {
	
    public $db;
    public $db2;
    public $prefs;
    public $work;
    public $api;
    public $tblock;
    public $sneak;
    public $xp;
    public $level;
    public $wars;
    public $atwar = array();
    public $chache = array();
    public $death = array();
    public $CC;
    public $tempally = array();
    public $pvplog = array();
    public $nt;
    public $saveinv;
    public $block = array();
    public $diamonds = array();
    public $experience = array();
    public $experience_level = array();
    public $restart = 0;
    public $uuid = array();

    public function AddExperience(Player $player, $amount){
            //.01 = 1 * .01
            $namount = $amount * .01;
            $masscal = ((($this->experience_level[$player->getName()] * 1.3)-$this->experience_level[$player->getName()]) + 1);
            $adjamount = ($namount/$masscal);
            //echo "$amount > $namount > $masscal > $adjamount > ".$this->experience[$player->getName()]."\n";
            $this->experience[$player->getName()] += $adjamount;
            if($this->experience[$player->getName()] >= $masscal){
                $this->experience_level[$player->getName()] += 1;
                $diff = $this->experience[$player->getName()] - $masscal;
                $this->experience[$player->getName()] = 0;
                $this->SetExperienceLevel($player, $this->experience_level[$player->getName()]);
                $this->AddExperience($player, $diff);
                return;
            }
            $this->SetExperience($player, $this->experience[$player->getName()]);
        }
        public function SetExperience(Player $player, $amount){
            //0 - 1 Max
                    $pk = new UpdateAttributesPacket();
                    $pk->minValue = 0;
                    $pk->maxValue = 20;
                    $masscal = ((($this->experience_level[$player->getName()] * 1.3)-$this->experience_level[$player->getName()]) + 1);
                    $pk->value = ($amount/$masscal);
                    $pk->name = "player.experience";
                    $player->dataPacket($pk);
            $this->experience[$player->getName()] = $amount;
            new \pocketmine\level\sound\PopSound($player);
        }
        
        public function SetExperienceLevel(Player $player,$amount){
            //18 Max?
                    $pk = new UpdateAttributesPacket();
                    $pk->minValue = 0;
                    $pk->maxValue = 20;
                    $pk->value = $amount;
                    $pk->name = "player.level";
                    $player->dataPacket($pk);
            $this->experience_level[$player->getName()] = $amount;
        }
        
        
        public function GetExperience(Player $player) {
            return $this->experience[$player->getName()];
        }    
    
        public function onEnable() {
		@mkdir($this->getDataFolder());
		
		$this->getServer()->getPluginManager()->registerEvents(new FactionListener($this), $this);
		$this->fCommand = new FactionCommands($this);
                $this->api = EconomyAPI::getInstance();
                $this->CC = $this->getServer()->getPluginManager()->getPlugin("CC-ChannelChat");
                if($this->api == null)$this->getServer()->getLogger()->alert("Econ Not Loaded!!!");
                $this->experience = (new Config($this->getDataFolder() . "xp.yml", CONFIG::YAML, array()))->getAll();
                $this->experience_level = (new Config($this->getDataFolder() . "xpl.yml", CONFIG::YAML, array()))->getAll();
                $this->level = (new Config($this->getDataFolder() . "level.yml", CONFIG::YAML, array()))->getAll();
                $this->uuid = (new Config($this->getDataFolder() . "uuid.yml", CONFIG::YAML, array()))->getAll();
		$this->prefs = (new Config($this->getDataFolder() . "Prefs.yml", CONFIG::YAML, array()))->getAll();
                $this->getServer()->getScheduler()->scheduleRepeatingTask(new FactionTask($this), 20*60*2);
                $this->wars = (new Config($this->getDataFolder() . "Wars.yml", CONFIG::YAML, array(
                    "ATTACKS" =>array(),
                    "DEFENDS" => array()
                )))->getAll();
            $this->tblock = (new Config($this->getDataFolder() . "Prefs.yml", CONFIG::YAML, array()))->getAll();
            $this->diamonds = (new Config($this->getDataFolder() . "Diamonds.yml", CONFIG::YAML, array()))->getAll();
            //$this->db = new \SQLite3($this->getDataFolder() . "FactionsPro.db");
            $db_ip = "cybertechpp.com";
                $db_user = "admin_fac";
                $db_pass = "admin_fac";
                $db_databes = "admin_fac";
                $this->db = @mysqli_connect($db_ip, $db_user, $db_pass,$db_databes,3306);
                $this->db2 = @mysqli_connect($db_ip, "admin_MiniGames", "admin_MiniGames", "admin_MiniGames",3306);
                if(!$this->db){
                    $this->getServer()->getLogger()->error("DATABSE Error!!!".mysqli_connect_error());
                }else{
                    $this->getServer()->getLogger()->notice("DB Successfully Loaded!");
                }
                if(!$this->db2){
                    $this->getServer()->getLogger()->error("DATABSE2 Error!!!".mysqli_connect_error());
                }else{
                    $this->getServer()->getLogger()->notice("DB Successfully Loaded!");
                }
                $this->getServer()->getScheduler()->scheduleRepeatingTask(new SelectType($this), 15);
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
        
        public function getFactionPrivacy($faction) {
            $a = @mysqli_fetch_assoc(@mysqli_query($this->db, "SELECT * FROM `settings` WHERE `faction` = '$faction'"));
            if($a['privacy'] == "" || $a['privacy'] == "0" )return false;
            return true;
        }
        
	public function isInFaction($player) {
        if($player instanceof Player)$player = $player->getName();
        $player = strtolower($player);
        $a = $this->GetChache($player, "isInFaction");
        if($a !== false){
            if($a == "yes")return true;
            if($a == "no")return false;
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
        public function GetRank($player) {
            if($player instanceof Player)$player = $player->getName();
            $a = $this->GetChache($player, "GetRank");
            if($a !== false){
                if($a == "no")return false;
                if($a == "yes")return true;
                return $a;
            }
            $a = @mysqli_query( $this->db2,"SELECT * FROM `ranks` WHERE `name` = '$player'");
            $b = @mysqli_fetch_assoc($a);
            if(@mysqli_num_rows($a) > 0){
                if($b['expires'] < strtotime("now") && $b['forever'] == 0){
                    $this->SetChache($player, "GetRank", 'Guest');
                    return "Guest";
                }
                $this->SetChache($player, "GetRank", $b['rank']);
                return $b['rank'];
            }
            $this->SetChache($player, "GetRank", "no");
            return false;
        }
        
        public function GetPrefix($player) {
            if($player instanceof Player)$player = $player->getName();
            $a = $this->GetChache($player, "isInFaction");
            if($a !== false){
                if($a == "no")return false;
                return $a;
            }
            $a = @mysqli_query( $this->db2,"SELECT * FROM `ranks` WHERE `name` = '$player'");
            $b = @mysqli_fetch_assoc($a);
            if(@mysqli_num_rows($a) > 0){
                if($b['expires'] < strtotime("now") && $b['expires'] !== 0){
                    $this->SetChache($player, "isInFaction", 'Guest');
                    return "Guest";
                }
                $this->SetChache($player, "isInFaction", $b['prefix']);
                return $b['prefix'];
            }
            $this->SetChache($player, "isInFaction", "no");
            return false;
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
            if($a !== false){
                if($a == "no")return false;
                return $a;
            }
            $faction = @mysqli_query($this->db,"SELECT * FROM `master` WHERE `player`='$player';");
            $factionArray = @mysqli_fetch_assoc($faction);
            $count = @mysqli_num_rows($faction);
            if ($count > 0 && $factionArray["faction"] != ""){
                $fac = $factionArray["faction"];
                $this->SetChache($player, "getPlayerFaction", $fac);
                return $fac;
            }else{
                $this->SetChache($player, "getPlayerFaction", "no");
            }
            return false;
	}

    public function getFactionColor($faction){
        $a = $this->GetChache($faction, "getFactionColor");
        if($a !== false){
            if($a == "no" || $a == "")return false;
            return $a;
        }
        $factionq = @mysqli_query($this->db,"SELECT * FROM `settings` WHERE `faction`='$faction';");
        $count = @mysqli_num_rows($factionq);
        $row = @mysqli_fetch_assoc($factionq);
        if($count > 0 && $row['color'] !== ""){
            $color = $row['color'];
            $this->SetChache($faction, "getFactionColor", $color);
            return $color;
        }
        $this->SetChache($faction, "getFactionColor", "no");
        return false;
    }
    
    public function DecodeFactionColor($color) {
        if($color == FALSE)return false;
        $array = array(
           "BLACK"=>"0",
           "DARK_BLUE"=>"1",
           "DARK_GREEN"=>"2",
           "DARK_AQUA"=>"3",
           "DARK_RED"=>"4",
           "DARK_PURPLE"=>"5",
           "GOLD"=>"6",
           "GRAY"=>"7",
           "DARK_GRAY"=>"8",
           "BLUE"=>"9",
           "GREEN"=>"a",
           "AQUA"=>"b",
           "RED"=>"c",
           "LIGHT_PURPLE"=>"d",
           "YELLOW"=>"e",
           "WHITE"=>"f",
           "OBFUSCATED"=>"k",
           "BOLD"=>"l",
           "STRIKETHROUGH"=>"m",
           "UNDERLINE"=>"n",
           "ITALIC"=>"o",
           "RESET"=>"r"
           );
        $c = explode("|", $color);
        $fc = "";
        foreach($c as $colorcode){
            $fc .= "§".$array[$colorcode];
        }
        return $fc;
    }
        
        public function GetChache($player, $key) {
            if(!isset($this->chache[$key][$player]) || !isset($this->chache[$key]))return false;
            return $this->chache[$key][$player];
        }
        
        /**
         * $this->chache[$key][$player] = $value;
         * @param type $player
         * @param type $key
         * @param type $value
         */
        public function SetChache($player, $key, $value) {
            $this->chache[$key][$player] = $value;
        }
        
        public function DeleteChache($player, $key) {
            unset($this->chache[$key][$player]);
        }
        
	public function getLeader($faction) {
            $leader = @mysqli_query($this->db,"SELECT * FROM `master` WHERE `faction` LIKE '$faction' AND `rank` LIKE 'Leader'");
            $leaderArray = @mysqli_fetch_assoc($leader);
            $count = @mysqli_num_rows($leader);
            if($count == 0){
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
                if($a == "yes")return $a;
                if($a == "no")return false;
            }
                    $result = @mysqli_query($this->db,"SELECT * FROM `master` WHERE `faction` = '$faction';");
            $count = @mysqli_num_rows($result);
            if($count == 0){
                $this->SetChache($faction, "factionExists", "no");
                return false;
            }
            $this->SetChache($faction, "factionExists", "yes");
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
            if(strtolower($faction1) == strtolower($faction2)){return true;}else{return false;}
	}
	public function getNumberOfPlayers($faction) {
            $query = @mysqli_query($this->db,"SELECT * FROM `master` WHERE `faction` = '$faction';");
            $number = @mysqli_num_rows($query);
            return $number;
	}
	public function isFactionFull($faction) {
            return $this->getNumberOfPlayers($faction) >= $this->GetMaxPlayers($faction);
	}
	
        public function GetMaxPlayers($faction) {
            $a = @mysqli_fetch_assoc(@mysqli_query($this->db, "SELECT * FROM `settings` WHERE `faction` = '$faction';"));
            return $a['max'];
        }
        
        public function isFactionOnline($faction) {
            foreach($this->getServer()->getOnlinePlayers() as $p){
                if(strtolower($this->getPlayerFaction($p->getName())) == strtolower($faction))return true;
            }
            return false;
        }
        
        public function newPlot($faction, $x1, $z1) {
            @mysqli_query($this->db, "INSERT INTO `plots` VALUES (NULL ,'$faction', '$x1', '$z1');");
            $this->SetChache($x1."|".$z1, "ChunkClaimed", "yes");
	}
        
        public function GetChunkOwner($x, $z) {
            if(!$this->ChunkClaimed($x, $z))return false;
            $a = $this->GetChache($x."-".$z, "GetChunkOwner");
            if($a !== null){
                if($a == false)return false;
                return $a;
            }
            $result = @mysqli_query($this->db,"SELECT * FROM `plots` WHERE `x` = '$x' AND `z` = '$z';");
            $count = @mysqli_fetch_assoc($result);
            $this->SetChache($x."-".$z, "GetChunkOwner", $count['faction']);
            return $count['faction'];
        }
        
        public function FactionHasPlot($faction){
            $a = $this->GetChache($x."-".$z, "FactionHasPlot");
            if($a !== null){
                if($a == false)return false;
                return $a;
            }
            $stmt = @mysqli_query($this->db,"SELECT * FROM `plots` WHERE `faction` = '$faction';");
            $count = @mysqli_num_rows($stmt);
            if($count > 0) {
                $this->SetChache( "asdsa" , "FactionHasPlot", $count['faction']);
                return true;
            }else{
                $this->SetChache($x."-".$z, "FactionHasPlot", $count['faction']);
                return false;
            }
        }
	public function drawPlot(Player $sender, $faction, $x, $z,Level $level) {
                if($this->ChunkClaimed($x, $z)){
                    $claim = $this->GetChunkOwner($x, $z);
                    $claim == $faction ? $sender->sendMessage(TextFormat::YELLOW."Error, You Own that Chunk already!") : $sender->sendMessage(TextFormat::RED."Error, That Chunk is already claimed by $claim!");
                    return false;
                }
                
                if ($this->ChunkInSpawn($x ,$z , $level)){
                    $sender->sendMessage(TextFormat::GRAY."[CyboticFactions] You Are In Spawn or Too Close To It! Move Away and Try Again!");
                    return false;
                }
                
                $this->newPlot($faction, $x, $z);
                $sender->sendMessage(TextFormat::GRAY."[CyboticFactions] Plot claimed.");
                $nx = $x << 4;
                $nz = $z << 4;
                $block = new \pocketmine\block\Block(\pocketmine\block\Block::SNOW);
                $y  = $sender->getY();
                $level->setBlock($level->getSafeSpawn(new Vector3($nx, $y, $nz)), $block, true, false);
                $level->setBlock($level->getSafeSpawn(new Vector3($nx+15, $y, $nz+15)), $block, true, false);
                $level->setBlock($level->getSafeSpawn(new Vector3($nx, $y, $nz+15)), $block, true, false);
                $level->setBlock($level->getSafeSpawn(new Vector3($nx+15, $y, $nz)), $block, true, false);
                return true;
	}
	
	public function updatePlots() {
	}
	
	public function plotChecker($onlinePlayers) {
		foreach($onlinePlayers as $player) {
			if($this->isInPlot($player) && $player instanceof Player) {
				$player->sendTip(TextFormat::YELLOW."[CyboticFactions] You are in a plot.");
			}
		}
	}
	
	public function isInPlot(Player $player) {
		$x = $player->getFloorX() >> 4;
		$z = $player->getFloorZ() >> 4;
		$result = @mysqli_query($this->db,"SELECT * FROM `plots` WHERE `x` = '$x' AND `z` = '$z';");
		$array = @mysqli_fetch_assoc($result);
		return empty($array) == false;
	}
        
        public function ChunkClaimed($x, $z) {
            $a = $this->GetChache($x."|".$z, "ChunkClaimed");
            if($a !== false){
                if($a == "no")return false;
                if($a == "yes")return true;
            }
            $result = @mysqli_query($this->db,"SELECT * FROM `plots` WHERE `x` = '$x' AND `z` = '$z';");
            $count = @mysqli_num_rows($result);
            if($count == 0){
                $this->SetChache($x."|".$z, "ChunkClaimed", "no");
                return false;
            }
            $this->SetChache($x."|".$z, "ChunkClaimed", "yes");
            return true;
	}
	
        //!!
	public function factionFromPoint($x,$z) {
            return $this->GetChunkOwner($x >> 4, $z >> 4);
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
	
        /**
         * Check to see if chunk is in spawn
         * @param type $x
         * @param type $z
         * @param Level $level
         * @return boolean
         */
        public function ChunkInSpawn($x, $z, Level $level) {
            $sx1 = $level->getSpawnLocation()->getX() >> 4 + 20;//300 Blocks!
            $sz1 = $level->getSpawnLocation()->getZ() >> 4 + 20;
            $sx2 = $level->getSpawnLocation()->getX() >> 4 - 20;
            $sz2 = $level->getSpawnLocation()->getZ() >> 4 - 20;
            $a = false;
            $b = false;
            if ((min($sx1,$sx2) < $x) && ($x < max($sx1,$sx2))){
                 $a = true;   
                }
            if ((min($sz1,$sz2) < $z) && ($z < max($sz1,$sz2))){
                $b = true;
            }
            if($a == true && $b == true)return true;
            return false;
        }
        
        /*@TODO Fix this!!!*/
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
            $this->getServer()->getScheduler()->scheduleDelayedTask(new EndWar($this, $attackers), 20*60*10); //10 Mins
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
            $factionArray = @mysqli_num_rows($stmt);
            if ($factionArray == 0){
                return false;
            }
            $nn = rand(0, ($factionArray - 1));
            $stmt1 = @mysqli_query($this->db,"SELECT * FROM `plots` WHERE `faction` = '$fac' LIMIT $nn,1;");
            $factionArray1 = @mysqli_fetch_assoc($stmt1);
            $x = ($factionArray1['x'] << 4) + rand((-1 * abs($num)) , (1 * abs($num)) );
            $z = ($factionArray1['z'] << 4) + rand((-1 * abs($num)) , (1 * abs($num)) );
            $v3 = $this->GetLoadedLevel()->getSafeSpawn(new Vector3( $x , 0 , $z ));
            return $v3;
        }
        
        public function GetFactionPower($faction) {
            $result = @mysqli_query($this->db,"SELECT * FROM `power` WHERE `faction` = '$faction';");
            $array = @mysqli_fetch_assoc($result);
            return $array['power'];
        }
        
        public function getPowerMultiplier($faction) {
            $chache = $this->GetChache($faction, "getPowerMultiplier");
            if($chache !== false){
                return $chache;
            }
            $a = @mysqli_fetch_assoc(@mysqli_query($this->db,"SELECT * FROM `settings` WHERE `faction` = '$faction';"));
            $this->SetChache($faction, "getPowerMultiplier", $a['powerbonus']);
            return $a['powerbonus'];
        }
        
        public function AddFactioPower($faction , $power) {
            $power1 = $power + $this->GetFactionPower($faction);
            $power1 = $this->getPowerMultiplier($faction) * $power1;
            @mysqli_query($this->db,"UPDATE `power` SET `power` = '$power1' WHERE `faction` = '$faction';");
            $this->MessageFaction($faction, TextFormat::GRAY."$faction has gained $power power!", true);
        }
        
        public function TakeFactionPower($faction, $power) {
            $powera = $this->GetFactionPower($faction);
            $fpower = $powera - $power;
            @mysqli_query($this->db,"UPDATE `power` SET `power` = '$fpower' WHERE `faction` = '$faction';");
            $this->MessageFaction($faction, TextFormat::GRAY."$faction has lost $power power!", true);
        }
        
        public function isFactionsAllyed($faction1, $faction2) {
            $chache = $this->GetChache($faction1."-".$faction2, "isFactionsAllyed");
            if($chache !== false){
                if($chache == "no")return false;
                if($chache == "yes")return true;
            }
            $result = @mysqli_query($this->db,"SELECT * FROM `allies` WHERE `factiona` = '$faction1' OR `factionb` = '$faction1';");
            while($row = @mysqli_fetch_assoc($result)){
                if($row['factiona'] == $faction2){
                    return true;
                    $this->SetChache($faction1."-".$faction2, "isFactionsAllyed", "yes");
                    $this->SetChache($faction2."-".$faction1, "isFactionsAllyed", "yes");
                }
                if($row['factionb'] == $faction2){
                    $this->SetChache($faction1."-".$faction2, "isFactionsAllyed", "yes");
                    $this->SetChache($faction2."-".$faction1, "isFactionsAllyed", "yes");
                }
            }
            $this->SetChache($faction1."-".$faction2, "isFactionsAllyed", "no");
            $this->SetChache($faction2."-".$faction1, "isFactionsAllyed", "no");
            return false;
        }
        
        /**
         * 
         * @param type $faction
         * @return Array
         */
        public function GetAllys($factiona){
            $a = $this->GetChache($factiona, "GetAllys");
            if($a !== false){
                return $a;
            }
            $result = @mysqli_query($this->db,"SELECT * FROM `allies` WHERE `factiona` = '$factiona' OR `factionb` = '$factiona';");
            $faction = array();
            while($row = @mysqli_fetch_assoc($result)){
               if($factiona !== $row['factionb'])$faction[] = $row['factionb'];
               if($factiona !== $row['factiona'])$faction[] = $row['factiona'];
            }
            $this->SetChache($factiona, "GetAllys", $faction);
            return $faction;
        }


        public function AddAlliance($faction1 , $faction2) {
            if($this->isFactionsAllyed($faction1, $faction2))return false;
            $time = strtotime("now");
            @mysqli_query($this->db,"INSERT INTO `allies` VALUES ('$faction1', '$faction2', '$time');");
            $this->SetChache($faction1."-".$faction2, "isFactionsAllyed", "yes");
            $this->SetChache($faction2."-".$faction1, "isFactionsAllyed", "yes");
        }
        
        /**
         * 
         * @param type $faction
         * @return Player
         */
        public function GetRandomFactionPlayer($faction) {
            $players = array();
            foreach ($this->getServer()->getOnlinePlayers() as $player) {
                if($this->getPlayerFaction($player->getName()) == $faction){
                    $players[] = $player;
                }
            }
            if(count($players) == 0)return false;
            $n = mt_rand(0, count($players)-1);
            return $players[$n];
        }
        
        public function RemoveAlliance($faction1, $faction2) {
            if(!$this->isFactionsAllyed($faction1, $faction2))return false;
            $time = strtotime("now");
            @mysqli_query($this->db,"DELETE FROM `allies` WHERE `factiona` = '$faction1' AND `factionb` = '$faction2';");
            @mysqli_query($this->db,"DELETE FROM `allies` WHERE `factiona` = '$faction2' AND `factionb` = '$faction1';");
            $this->SetChache($faction1."-".$faction2, "isFactionsAllyed", "no");
            $this->SetChache($faction2."-".$faction1, "isFactionsAllyed", "no");
        }
        
        public function MessageFaction($faction, $message, $popup = false) {
            if($faction == "")return false;
            foreach($this->getServer()->getOnlinePlayers() as $p){
                $factionb = $this->getPlayerFaction($p->getName());
                if($factionb !== false && strtolower($faction) == strtolower($factionb)){
                    $player = $p;
                    if($popup == false){
                        $player->sendMessage($message);
                    }else{
                        $player->sendPopup($message);
                    }
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
                $c = (new Config($this->getDataFolder() . "xp.yml", CONFIG::YAML));
                $c->setAll($this->experience);
                $c->save();
                $d = (new Config($this->getDataFolder() . "xpl.yml", CONFIG::YAML));
                $d->setAll($this->experience_level);
                $d->save();
                $b = (new Config($this->getDataFolder() . "Diamonds.yml", CONFIG::YAML));
                $b->setAll($this->diamonds);
                $b->save();
                $e = (new Config($this->getDataFolder() . "uuid.yml", CONFIG::YAML));
                $e->setAll($this->uuid);
                $e->save();
		@mysqli_close($this->db);
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
}
