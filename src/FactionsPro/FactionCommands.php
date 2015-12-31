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
use pocketmine\level\Level;


class FactionCommands {
	
	public $plugin;
	
	public function __construct(FactionMain $pg) {
		$this->plugin = $pg;
	}
	
    public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
            echo $command->getName();
            $player = $sender->getName();
            switch($command->getName()){
                case "claim":
                    $time = strtotime("now");
                    $rank = $this->plugin->GetRank($player);    
                    if($rank == false || $rank == "Guest"){
                        $sender->sendMessage(TextFormat::RED."You don't have a Rank!");
                        $sender->sendMessage(TextFormat::AQUA."You can purchase ranks starting at $5 and Up!");
                        $sender->sendMessage(TextFormat::AQUA."At CyberTechpp.com/MCPE");
                        return true;
                    }
                    $a = @mysqli_fetch_assoc(@mysqli_query( $this->plugin->db2,"SELECT * FROM `ranks` WHERE `name` = '$player'"));
                    if($a['claimed'] > 0){
                        $sender->sendMessage(TextFormat::RED."You Claimed your Perks Already!!!");
                        return true;
                    }
                    
                    if($rank == "HERO" ||$rank == "VIP+" ||$rank == "VIP" ||$rank == "STEVE+" ||$rank == "HERO" ||$rank == "LEGEND"){
                        foreach($this->plugin->prefs["ranks-rewards"][$rank] as $reward){
                            $exp = explode("|",$reward);
                            if($exp[0] == "money"){
                                $this->plugin->api->addMoney($sender->getName(), $exp[1]);
                            }elseif($exp[0] == "xp"){
                                $this->plugin->getServer()->getPlayerExact($player)->addExperience($exp[1]);
                            }else{
                            $item = \pocketmine\item\Item::get($exp[0], $exp[1], $exp[2]);
                            $inv = $this->plugin->getServer()->getPlayerExact($player)->getInventory();
                            $inv->addItem(clone $item);
                            }
                        }
                        $time = strtotime("now");
                        $sender->sendMessage(TextFormat::GREEN."Kit Claimed!");
                        @mysqli_query( $this->plugin->db2,"UPDATE `ranks` SET `claimed` = '$time' WHERE `name` = '$player'");
                    }
                    if(stripos($rank, "MONEY")){
                        $amount = str_replace("MONEY", "" ,$rank);
                        $money = $amount * 10000;
                        $this->plugin->api->addMoney($sender->getName(), $money);
                        $sender->sendMessage(TextFormat::GREEN."Kit Claimed!");
                        @mysqli_query( $this->plugin->db2,"UPDATE `ranks` SET `claimed` = '$time' WHERE `name` = '$player'");
                    }
                    break;
                case "kitclaim":
                    $rank = $this->plugin->GetRank($player);    
                    if($rank == false || $rank == "Guest"){
                        $sender->sendMessage(TextFormat::RED."You don't have a Rank!");
                        $sender->sendMessage(TextFormat::AQUA."You can purchase ranks starting at $5 and Up!");
                        $sender->sendMessage(TextFormat::AQUA."At CyberTechpp.com/MCPE");
                        return true;
                    }
                    $a = @mysqli_fetch_assoc(@mysqli_query( $this->plugin->db2,"SELECT * FROM `ranks` WHERE `name` = '$player'"));
                    if($a['lastclaimed'] > strtotime("-24 Hour")){
                        $sender->sendMessage(TextFormat::RED."You Claimed your Perks for the Day!!!\n Come Back Tomorrow!");
                        return true;
                    }
                    
                    if($rank == "HERO" ||$rank == "VIP+" || $rank == "VIP" ||$rank == "STEVE+" ||$rank == "HERO" ||$rank == "LEGEND"){
                        foreach($this->plugin->prefs["kits"][$rank] as $reward){
                            $exp = explode("|",$reward);
                            if($exp[0] == "money"){
                                $this->plugin->api->addMoney($sender->getName(), $exp[1]);
                                continue;
                            }
                            if($exp[0] == "xp"){
                                $this->plugin->getServer()->getPlayerExact($player)->addExperience($exp[1]);
                                continue;
                            }
                            $item = \pocketmine\item\Item::get($exp[0], $exp[1], $exp[2]);
                            $inv = $this->plugin->getServer()->getPlayerExact($player)->getInventory();
                            $inv->addItem(clone $item);
                        }
                        $sender->sendMessage(TextFormat::GREEN."Kit Claimed!");
                        $time = strtotime("now");
                        @mysqli_query( $this->plugin->db2,"UPDATE `ranks` SET `lastclaimed` = `$time` WHERE `name` = '$player'");
                    }
                    break;
                case "cban":
                    if(!$sender->isOp())return false;
                    if(!$args > 0){
                        break;
                    }
                    $target = $this->plugin->getServer()->getPlayer($args[0]);
                    $sender->getServer()->getCIDBans()->addBan($target->getClientId(), "Banned ".  date(DATE_COOKIE)."", \null, $sender->getName());
                    $target->kick("You are now banned!", true);
                    $sender->sendMessage(TextFormat::GREEN.$target->getName()."'s UUID Is now Banned!");
                    break;
                case "wild": 
                    if(!$sender instanceof Player)return false;
                    $max = 10000;
                    $min = -10000;
                    $v3 = new Vector3(mt_rand($min, $max),76,mt_rand($min, $max));
                    $chunk = $sender->getLevel()->getChunk($v3->getX() >> 4, $v3->getZ() >> 4);
                    if($chunk == null || !$chunk->isGenerated()){
                        //!($chunk instanceof FullChunk) or !$chunk->isGenerated()
                        //$sender->getLevel()->generateChunk($v3->getX() >> 4, $v3->getZ() >> 4);
                        $sender->getLevel()->generateChunk($v3->getX() >> 4 + 1, $v3->getZ() >> 4 + 1);
                        $sender->getLevel()->generateChunk($v3->getX() >> 4 + 1, $v3->getZ() >> 4 + 2);
                        $sender->getLevel()->generateChunk($v3->getX() >> 4 + 1, $v3->getZ() >> 4 - 1);
                        $sender->getLevel()->generateChunk($v3->getX() >> 4 + 1, $v3->getZ() >> 4 - 2);
                        $sender->getLevel()->generateChunk($v3->getX() >> 4 - 1, $v3->getZ() >> 4 + 1);
                        $sender->getLevel()->generateChunk($v3->getX() >> 4 - 1, $v3->getZ() >> 4 + 2);
                        $sender->getLevel()->generateChunk($v3->getX() >> 4 - 1, $v3->getZ() >> 4 - 1);
                        $sender->getLevel()->generateChunk($v3->getX() >> 4 - 1, $v3->getZ() >> 4 - 2);
                        //if(!($sender->getLevel()->getChunk($v3->getX() >> 4, $v3->getZ() >> 4) instanceof FullChunk) || !($sender->getLevel()->getChunk($v3->getX() >> 4, $v3->getZ() >> 4)->isGenerated()))

                        
                        $sender->sendMessage(TextFormat::GRAY."Chunks Are Getting Generated! Please Wait 30 Secs!");
                        $this->plugin->getServer()->getScheduler()->scheduleDelayedTask(new Teleport($this->plugin,$sender,$v3), 20 * 30);
                        return true; 
                    }
                    $pos = $sender->getLevel()->getSafeSpawn($v3);
                    $sender->teleport($pos);
                    return true;
                case "f": 
                    if(!$sender instanceof Player)return false;
                    if(count($args) == 0 || $args[0] == "") {
                            $sender->sendMessage("[CyboticFactions] Please use /f help for a list of commands");
                            return true;
                    }
                    /*
                    if($args[0] == "fly" && $args[1] == "on"){
                        $flags = 0;
                        $flags |= 0x80;
                        $flags |= 0x40;
                        //if($sender->isCreative())$flags |= 0x80;
                        $pk = new \pocketmine\network\protocol\AdventureSettingsPacket();
                        $pk->flags = $flags;
                        $sender->dataPacket($pk->setChannel(\pocketmine\network\Network::CHANNEL_PRIORITY));
                    }
                    
                    if($args[0] == "fly" && $args[1] == "off"){
                        $flags = 0;
                        $flags |= 0x40;
                        //if($sender->isCreative())$flags |= 0x80;
                        $pk = new \pocketmine\network\protocol\AdventureSettingsPacket();
                        $pk->flags = $flags;
                        $sender->dataPacket($pk->setChannel(\pocketmine\network\Network::CHANNEL_PRIORITY));
                    }*/

                    if (isset($args[0]) && (($args[0] == "chat" || $args[0] == "c"))){
                        $faction = $this->plugin->getPlayerFaction($player);
                        if($faction == false)return true;
                        $chat = "";
                        foreach($args as $cc=>$c){
                            if ($cc !== 0){
                                $chat .= $c." ";
                            }
                        }
                        $this->plugin->MessageFaction($faction, TextFormat::YELLOW."**[$player]**: $chat");
                        return true;
                    }
                    
                    if(isset($args[0]) && $args[0] == "cc"){
                        if(!$sender->isOp())return false;
                        $this->plugin->chache = array();
                        foreach ($this->plugin->getServer()->getLevels() as $level) {
                            foreach ($level->getEntities() as $e){
                                if($e instanceof \pocketmine\Player)break;
                                $e->kill();
                            }
                        }
                        $this->plugin->prefs = (new Config($this->plugin->getDataFolder() . "Prefs.yml", CONFIG::YAML, array()))->getAll();
                        return true;
                    }
                    
                    if(isset($args[0]) && $args[0] == "rewards"){
                        if(!($sender instanceof Player))return true;
                        $faction = $this->plugin->getPlayerFaction($sender->getName());
                        if($faction == false) {
                                $sender->sendMessage("[CyboticFactions] You must Be In a faction!");
                                return true;
                        }
                        if(!$this->plugin->isLeader($sender->getName())) {
                            if (!$this->plugin->isOfficer($sender->getName())){
                                $sender->sendMessage("[CyboticFactions] You must Be the Leader Or Officer To Declare War!");
                                return true;
                            }
                        }
                        $a = @mysqli_fetch_assoc(@mysqli_query($this->plugin->db, "SELECT * FROM `settings` WHERE `faction` = '$faction'"));
                        if(!isset($a['rewards'])){
                            $sender->sendMessage(TextFormat::RED."Your Faction is not VIP! \n Please visit ".TextFormat::YELLOW."cybertechpp.com/shop \n and Purchase a VIP Feature!");
                            return true;
                        }
                        $b = explode("|", $a['rewards']);
                        foreach ($b as $key=>$rewards){
                            if(!isset($this->plugin->prefs["kituseage"][$faction][$key]))$this->plugin->prefs["kituseage"][$sender->getName()][$key] = 0;
                            $c = explode(">", $rewards);
                            $d = explode("/", $c[0]);
                            if($this->plugin->prefs["kituseage"][$faction][$key] >= $d[1]){
                                $sender->sendMessage(TextFormat::RED."The Maximum Uses For This Kit Has Been Used! KitID: $key");
                                continue;
                            }
                            $e = explode(":", $d[0]);
                            $item = \pocketmine\item\Item::fromString($e[0]);
                            $item->setCount($e[1]);
                            $sender->getInventory()->addItem(clone $item);
                            $this->plugin->prefs["kituseage"][$faction][$key]++;
                            $sender->sendMessage(TextFormat::GREEN."Kit Claimed!  KitID: $key");
                        }
                    }
                    
                    if (isset($args[0]) && (($args[0] == "allychat" || $args[0] == "ac"))){
                        $faction = $this->plugin->getPlayerFaction($player);
                        if($faction == false)return true;
                        $chat = "";
                        foreach($args as $cc=>$c){
                            if ($cc !== 0){
                                $chat .= $c." ";
                            }
                        }
                        $ally = $this->plugin->GetAllys($faction);
                        $this->plugin->MessageFaction($faction, TextFormat::GOLD."**[$faction][$player]**: $chat");
                        foreach($ally as $fac){
                            if($fac == "")continue;
                            $this->plugin->MessageFaction($fac, TextFormat::GOLD."**[$faction][$player]**: $chat");
                        }
                        
                        return true;
                    }
                    
                    if(count($args) == 2) {

                            //War
                            if($args[0] == "war") {
                                    if(!($sender instanceof Player)){
                                        return true;
                                    }
                                    if(!(ctype_alnum($args[1]))) {
                                            $sender->sendMessage("[CyboticFactions] You may only use letters and numbers!");
                                            return true;
                                    }
                                    if (!$this->plugin->factionPartialName($args[1])){
                                        $sender->sendMessage("Please Make Sure the Faction Name is right!");
                                        return true;
                                    }
                                    $faction = $this->plugin->getPlayerFaction($sender->getName());
                                    if($faction == false) {
                                            $sender->sendMessage("[CyboticFactions] You must Be In a faction!");
                                            return true;
                                    }
                                    if(!$this->plugin->isLeader($sender->getName())) {
                                        if (!$this->plugin->isOfficer($sender->getName())){
                                            $sender->sendMessage("[CyboticFactions] You must Be the Leader Or Officer To Declare War!");
                                            return true;
                                        }
                                    }
                                    $defenders = $this->plugin->factionPartialName($args[1]);
                                    if(!$this->plugin->isFactionOnline($defenders)){
                                       $sender->sendMessage(TextFormat::RED."[CyboticFactions] The Faction $faction is not online!");
                                        return true; 
                                    }
                                    $power = $this->plugin->GetFactionPower($faction);
                                    if($power < 500) {
                                            $sender->sendMessage(TextFormat::RED."[CyboticFactions] You only have $power, and need 500!");
                                            return true;
                                    }
                                    $this->plugin->TakeFactionPower($faction, 500);
                                    $attackers = $faction;
                                    $this->plugin->DeclareWar($attackers, $defenders);


                            }


                            //Create

                            if($args[0] == "create") {
                                    if(!isset($args[1])){
                                        $sender->sendMessage(TextFormat::GRAY."Useage /f create <name>");
                                        return true;
                                    }
                                    if(!(ctype_alnum($args[1]))) {
                                            $sender->sendMessage("[CyboticFactions] You may only use letters and numbers!");
                                            return true;
                                    }
                                    if($this->plugin->factionExists($args[1]) == true ) {
                                            $sender->sendMessage("[CyboticFactions] Faction already exists");
                                            return true;
                                    }
                                    if(strlen($args[1]) > $this->plugin->prefs["MaxFactionNameLength"]) {
                                            $sender->sendMessage("[CyboticFactions] Faction name is too long. Please try again!");
                                            return true;
                                    }
                                    if($this->plugin->isInFaction($sender->getName())) {
                                            $sender->sendMessage("[CyboticFactions] You must leave this faction first");
                                            return true;
                                    } else {
                                            $factionName = $args[1];
                                            $player = strtolower($player);
                                            $faction = $factionName;
                                            $rank = "Leader";
                                            $this->plugin->SetChache($player, "getPlayerFaction", $faction);
                                            $this->plugin->SetChache($player, "isInFaction","yes");
                                            $this->plugin->SetChache($faction, "factionExists","yes");
                                            @mysqli_query($this->plugin->db,"INSERT INTO `master` VALUES ('$player', '$faction', '$rank');");
                                            @mysqli_query($this->plugin->db,"INSERT INTO `power` VALUES ('$faction', '50');");
                                            @mysqli_query($this->plugin->db,"INSERT INTO `settings` VALUES ('$faction', '15','','1','Just Another Cybotic Faction!','','0','','0');");
                                            $sender->sendMessage(TextFormat::GREEN."[CyboticFactions] Faction successfully created!");
                                            $sender->sendMessage(TextFormat::GRAY."[CyboticFactions] Your Faction has 50 power!");
                                            return true;
                                    }
                            }

                            if($args[0] == "privacy"){
                                    $faction = $this->plugin->getPlayerFaction($player);
                                    if($faction == false) {
                                            $sender->sendMessage(TextFormat::RED."[CyboticFactions] Your Not in a faction!");
                                            return true;
                                    }
                                    if($this->plugin->isLeader($player)) {
                                        if($args[1] == "on"){
                                            @mysqli_query($this->plugin->db,"UPDATE `settings` SET `privacy` = '1' WHERE `faction` = '$faction';");
                                            $sender->sendMessage(TextFormat::GREEN."Faction Privacy is Now On!");
                                        }
                                        if($args[1] == "off"){
                                            @mysqli_query($this->plugin->db,"UPDATE `settings` SET `privacy` = '0' WHERE `faction` = '$faction';");
                                            $sender->sendMessage(TextFormat::GREEN."Faction Privacy is Now Off!");
                                        }
                                        return true;
                                    }
                                    $sender->sendMessage(TextFormat::RED."[CyboticFactions] Only your faction leader may use this!");
                            }
                            //Invite
                            if($args[0] == "invite" || $args[0] =="inv") {
                                    $faction = $this->plugin->getPlayerFaction($player);
                                    if($faction == false) {
                                            $sender->sendMessage(TextFormat::RED."[CyboticFactions] Your Not in a faction!");
                                            return true;
                                    }
                                    if( $this->plugin->isFactionFull($this->plugin->getPlayerFaction($player)) ) {
                                            $sender->sendMessage(TextFormat::RED."[CyboticFactions] Faction is full. Please kick players to make room.\n".TextFormat::RED."[CyboticFactions] Or pay to upgrade your faction limit!");
                                            return true;
                                    }
                                    //remove Player Exact
                                    $invited = $this->plugin->getServer()->getPlayer($args[1]);
                                    if(!$invited instanceof Player) {
                                            $sender->sendMessage(TextFormat::RED."[CyboticFactions] No Player By That Name Is Online!");
                                            return true;
                                    }
                                    if($this->plugin->isInFaction($invited) == true) {
                                            $sender->sendMessage(TextFormat::RED."[CyboticFactions] Player is currently in a faction");
                                            return true;
                                    }
                                    if($this->plugin->getFactionPrivacy($faction)& !($this->plugin->isLeader($player))) {
                                        $sender->sendMessage(TextFormat::RED."[CyboticFactions] Only your faction leader may invite!");
                                        return true;
                                    }
                                    if($invited->isOnline() == true) {
                                            $invitee = $invited->getName();
                                            $invitedName = $invited->getName();
                                            $rank = "Member";
                                            $time = time();
                                            @mysqli_query($this->plugin->db,"REPLACE INTO `confirm` VALUES ('$invitee', '$faction', '$player','$time');");
    ;

                                            $sender->sendMessage(TextFormat::GREEN."[CyboticFactions] Successfully invited $invitedName!");
                                            $invited->sendMessage(TextFormat::YELLOW."[CyboticFactions] You have been invited to $faction.\n Type '/f accept' or '/f deny' into chat to accept or deny!");
                                    } else {
                                            $sender->sendMessage(TextFormat::RED."[CyboticFactions] Player not online!");
                                    }
                                    return true;
                            }

                            //Leader

                            if($args[0] == "leader") {
                                    if($this->plugin->isInFaction($sender->getName()) == true) {
                                            if($this->plugin->isLeader($player) == true) {
                                                if(!isset($args[1])){
                                                    $sender->sendMessage(TextFormat::RED."Useage /f leader <player>");
                                                    return true;
                                                }
                                                $pp = $this->plugin->getServer()->getPlayer($args[1]);
                                                if ($pp instanceof Player){
                                                    $ppn = $pp->getName();
                                                    if($this->plugin->getPlayerFaction($player) == $this->plugin->getPlayerFaction($ppn)) {
                                                        $factionName = $this->plugin->getPlayerFaction($player);
                                                        $player = strtolower($player);
                                                        $faction = $factionName;
                                                        $rank = "Member";
                                                        @mysqli_query($this->plugin->db,"REPLACE INTO `master` VALUES ('$player', '$faction', '$rank');");
                                                        $rank = "Leader";
                                                        @mysqli_query($this->plugin->db,"REPLACE INTO `master` VALUES ('$ppn', '$faction', '$rank');");
                                                        $this->plugin->MessageFaction($faction, TextFormat::YELLOW."$ppn Is your New Leader!");
                                                        $sender->sendMessage(TextFormat::YELLOW."[CyboticFactions] You are no longer leader!");
                                                        $pp->sendMessage(TextFormat::YELLOW."[CyboticFactions] You are now leader \nof $factionName!");
                                                    } else {
                                                            $sender->sendMessage(TextFormat::RED."[CyboticFactions] Add player to faction first!");
                                                    }
                                            } else {
                                                $sender->sendMessage(TextFormat::RED."[CyboticFactions] Player Not Online!");
                                            }
                                        } else {
                                                $sender->sendMessage(TextFormat::RED."[CyboticFactions] You must be leader to use this");
                                        }
                                    } else {
                                            $sender->sendMessage(TextFormat::RED."[CyboticFactions] You must be in a faction to use this!");
                                    }
                                }

                            //Promote
                            if($args[0] == "test"){
                                $this->plugin->MessageFaction($this->plugin->getPlayerFaction($sender->getName()), "TETETE as d asd asd as dasdasd as d as");
                                echo $this->plugin->getPlayerFaction($sender->getName())." IS THE FAC";
                            }    
                            
                            if($args[0] == "promote") {
                                    if(!isset($args[1])){
                                        $sender->sendMessage(TextFormat::GRAY."Useage /f promote <player>");
                                        return true;
                                    }
                                    $pp = $this->plugin->getServer()->getPlayer($args[1]);
                                    if (!($pp instanceof Player)){
                                        $sender->sendMessage(TextFormat::RED."[CyboticFactionss] Player Is Not Online Or Does Not Exist!");;
                                        return true;
                                    }
                                    $ppn = $pp->getName();
                                    $factionName = $this->plugin->getPlayerFaction($player);
                                    if($factionName == false) {
                                            $sender->sendMessage(TextFormat::RED."[CyboticFactions] You must be in a faction to use this!");
                                            return true;
                                    }
                                    if($this->plugin->isLeader($player) == false) {
                                            $sender->sendMessage(TextFormat::RED."[CyboticFactions] You must be leader to use this");
                                            return true;
                                    }
                                    if($this->plugin->isInFaction($ppn) == false){
                                        $sender->sendMessage(TextFormat::RED."Target Player Not In Your Faction!");
                                        return true;
                                    }
                                    if($this->plugin->getPlayerFaction($player) != $this->plugin->getPlayerFaction($ppn)) {
                                            $sender->sendMessage(TextFormat::RED."[CyboticFactions] Player is not in this faction!");
                                            return true;
                                    }
                                    if($this->plugin->isOfficer($ppn) == true) {
                                            $sender->sendMessage(TextFormat::RED."[CyboticFactions] Player is already officer");
                                            return true;
                                    }
                                    $player = strtolower($ppn);
                                    $faction = $factionName;
                                    $rank = "Officer";
                                    @mysqli_query($this->plugin->db,"REPLACE INTO `master` VALUES ('$player', '$faction', '$rank');");
                                    $sender->sendMessage(TextFormat::GREEN."[CyboticFactions] You successfully Promoted $ppn!");
                                    $pp->sendMessage(TextFormat::GREEN."[CyboticFactions] You Have Been Promoted To a Officer!!!");
                                    $this->plugin->MessageFaction($faction, TextFormat::AQUA."$ppn Is Now An Officer!");
                            }

                            //Demote

                            if($args[0] == "demote") {

                                    $pp = $this->plugin->getServer()->getPlayer($args[1]);
                                    $ppn = $pp->getName();
                                    $factionName = $this->plugin->getPlayerFaction($player);
                                    if (!($pp instanceof Player)){
                                        $sender->sendMessage(TextFormat::RED."[CyboticFactions] Player Is Not Online Or Does Not Exist!");
                                        return true;
                                    }
                                    if($this->plugin->isInFaction($sender->getName()) == false) {
                                            $sender->sendMessage(TextFormat::RED."[CyboticFactions] You must be in a faction to use this!");
                                            return true;
                                    }
                                    if($this->plugin->isLeader($player) == false) {
                                            $sender->sendMessage(TextFormat::RED."[CyboticFactions] You must be leader to use this");
                                            return true;
                                    }
                                    if($this->plugin->getPlayerFaction($player) != $this->plugin->getPlayerFaction($ppn)) {
                                            $sender->sendMessage(TextFormat::RED."[CyboticFactions] Player is not in this faction!");
                                            return true;
                                    }
                                    if($this->plugin->isOfficer($player) == false) {
                                            $sender->sendMessage(TextFormat::RED."[CyboticFactions] Player is not Officer");
                                            return true;
                                    }
                                    $player = strtolower($ppn);
                                    $faction = $factionName;
                                    $rank = "Member";
                                    @mysqli_query($this->plugin->db,"REPLACE INTO `master` VALUES ('$player', '$faction', '$rank');");
                                    $sender->sendMessage(TextFormat::GREEN."[CyboticFactions] You successfully Demoted $ppn!");
                                    $pp->sendMessage(TextFormat::GREEN."[CyboticFactions] You Have Been Demoted To a Member!!!");
                            }

                            //Kick

                            if($args[0] == "kick") {
                                    if ($args[1] == ""){
                                        $sender->sendMessage(TextFormat::RED."[CyboticFactionss] Invalid \n [CyboticFactionss] /f kick <player>");
                                        return true;
                                    }
                                    $pp = $this->plugin->getServer()->getPlayer($args[1]);
                                    if (!($pp instanceof Player)){
                                        $sender->sendMessage(TextFormat::RED."[CyboticFactionss] Player Is Not Online or Does Not Exist!");
                                        return true;
                                    }
                                    $ppn = $pp->getName();
                                    $faction = $this->plugin->getPlayerFaction($player);
                                    $ofaction = $this->plugin->getPlayerFaction($ppn);
                                    if($faction == false) {
                                            $sender->sendMessage(TextFormat::RED."[CyboticFactions] You must be in a faction to use this!");
                                            return true;
                                    }
                                    if($this->plugin->isLeader($player) == false) {
                                            $sender->sendMessage(TextFormat::RED."[CyboticFactions] You must be leader to use this");
                                            return true;
                                    }
                                    if ($ofaction == false){
                                        $sender->sendMessage(TextFormat::RED."[CyboticFactions] Player Not In Faction!");
                                    }
                                    if($ofaction != $faction) {
                                            $sender->sendMessage(TextFormat::RED."[CyboticFactions] Player is not in this faction!");
                                            return true;
                                    }
                                    $this->plugin->DeleteChache($ppn, "getPlayerFaction");
                                    $this->plugin->DeleteChache($ppn, "isInFaction");
                                    $factionName = $faction;
                                    @mysqli_query($this->plugin->db,"DELETE FROM master WHERE player='$ppn';");
                                    $sender->sendMessage(TextFormat::GREEN."[CyboticFactions] You successfully kicked $ppn!");
                                    $pp->sendMessage(TextFormat::GREEN."[CyboticFactions] You Have Been Kicked From $factionName!!!");
                                    $pp->setNameTag($pp->getName());
                                    $this->plugin->TakeFactionPower($factionName, 60);
                            }
                    }
                    
                    if(strtolower($args[0] == "ally")) {
                            if(!isset($args[1]) || ($args[1] !== "add" && $args[1] !== "remove" && $args[1] !== "accept"  && $args[1] !== "deny" )){
                                $sender->sendMessage(TextFormat::RED."[CyboticFactions] Useage /f ally add|remove|accept|deny <faction>");
                                return true;
                            }
                            if($args[1] == "add" && isset($args[2])){
                                $ofaction = $this->plugin->factionPartialName($args[2]);
                                $faction = $this->plugin->getPlayerFaction($sender->getName());
                                if($faction == false){
                                    $sender->sendMessage(TextFormat::RED."[CyboticFactions] You must be in a faction to use this command!!!");
                                    return true;
                                }
                                if($ofaction == false){
                                    $sender->sendMessage(TextFormat::RED."[CyboticFactions] Their is no factions with the name of `".$args[2]."`!!!");
                                    return true;
                                }
                                if(!$this->plugin->isFactionOnline($ofaction)){
                                    $sender->sendMessage(TextFormat::RED."[CyboticFactions] No one from $ofaction is online!");
                                    return true;
                                }
                                if( $this->plugin->isFactionsAllyed($faction, $ofaction)){
                                    $sender->sendMessage(TextFormat::RED."[CyboticFactions] Silly Human! Your Already Allied!!");
                                    return true;
                                }
                                if(!$this->plugin->isOfficer($player) && !$this->plugin->isLeader($player)){
                                    $sender->sendMessage(TextFormat::RED."[CyboticFactions] Silly Human! Your not an Officer/Leader!!");
                                    return true;
                                }
                                if($this->plugin->GetFactionPower($faction) < 100){
                                    $sender->sendMessage(TextFormat::RED."[CyboticFactions] You Dont Have Enough Power! 100 Faction Power Is Required!");
                                    return true;
                                }else{
                                    $this->plugin->tempally[$ofaction] = $faction;
                                    echo "$faction => $ofaction \n";
                                    $this->plugin->MessageFaction($faction, TextFormat::YELLOW."Ally Invite Was Sent to $ofaction! Please Wait For An Responce!");
                                    $this->plugin->MessageFaction($ofaction, TextFormat::YELLOW."An Ally Invite Was Sent To You From $faction! Please use '/f ally accept' or '/f ally deny'!");
                                    $this->plugin->TakeFactionPower($faction, 100);
                                    return true;
                                }
                            }elseif($args[1] == "accept"){
                                $faction = $this->plugin->getPlayerFaction($sender->getName());
                                if($faction == false){
                                    $sender->sendMessage(TextFormat::RED."[CyboticFactions] You must be in a faction to use this command!!!");
                                    return true;
                                }
                                if(!$this->plugin->isOfficer($player) && !$this->plugin->isLeader($player)){
                                    $sender->sendMessage(TextFormat::RED."[CyboticFactions] Silly Human! Your not an officer!!");
                                    return true;
                                }
                                if(isset($this->plugin->tempally[$faction])){
                                    $allyfac = $this->plugin->tempally[$faction];
                                    $this->plugin->AddAlliance($faction, $allyfac);
                                    $this->plugin->getServer()->broadcastMessage(TextFormat::AQUA."$faction and $allyfac Are now in an alliance!");
                                    $this->plugin->SetChache($allyfac."-".$faction, "isFactionsAllyed", "yes");
                                    $this->plugin->SetChache($faction."-".$allyfac, "isFactionsAllyed", "yes");
                                    return true;
                                }else{
                                    $sender->sendMessage(TextFormat::RED."You Have No Ally Faction Invites!");
                                    return true;
                                }
                            }elseif($args[1] == "deny"){
                                $faction = $this->plugin->getPlayerFaction($sender->getName());
                                if($faction == false){
                                    $sender->sendMessage(TextFormat::RED."[CyboticFactions] You must be in a faction to use this command!!!");
                                    return true;
                                }
                                if(!$this->plugin->isOfficer($player) && !$this->plugin->isLeader($player)){
                                    $sender->sendMessage(TextFormat::RED."[CyboticFactions] Silly Human! Your not an officer!!");
                                    return true;
                                }
                                if(isset($this->plugin->tempally[$faction])){
                                    $allyfac = $this->plugin->tempally[$faction];
                                    unset($this->plugin->tempally[$faction]);
                                    $this->plugin->MessageFaction($allyfac, TextFormat::AQUA."$faction has ".TextFormat::RED.TextFormat::BOLD."Declined".TextFormat::RESET.TextFormat::AQUA." your alliance alliance!");
                                    return true;
                                }else{
                                    $sender->sendMessage(TextFormat::RED."You Have No Ally Faction Invites!");
                                    return true;
                                }
                            }elseif($args[1] == "remove"){
                                $ofaction = $this->plugin->factionPartialName($args[2]);
                                $faction = $this->plugin->getPlayerFaction($sender->getName());
                                if($faction == false){
                                    $sender->sendMessage(TextFormat::RED."[CyboticFactions] You must be in a faction to use this command!!!");
                                    return true;
                                }
                                if($ofaction == false){
                                    $sender->sendMessage(TextFormat::RED."[CyboticFactions] Their is no factions by the name of `".$args[2]."`!!!");
                                    return true;
                                }
                                if(!$this->plugin->isFactionsAllyed($faction, $ofaction)){
                                    $sender->sendMessage(TextFormat::RED."[CyboticFactions] Silly Human! Your Not Allied!!");
                                    return true;
                                }
                                if(!$this->plugin->isOfficer($player) && !$this->plugin->isLeader($player)){
                                    $sender->sendMessage(TextFormat::RED."[CyboticFactions] Silly Human! Your not an officer!!");
                                    return true;
                                }
                                $this->plugin->TakeFactionPower($faction, 50);
                                $this->plugin->RemoveAlliance($faction, $ofaction);
                                $this->plugin->SetChache($ofaction."-".$faction, "isFactionsAllyed", "no");
                                $this->plugin->SetChache($faction."-".$ofaction, "isFactionsAllyed", "no");
                                $this->plugin->getServer()->broadcastMessage(TextFormat::DARK_AQUA."$faction and $ofaction Are nolonger in an alliance!");
                                return true;
                            }
                        }

                    if(strtolower($args[0] == "home")) {
                            if(isset($args[1])){
                                $faction = $this->plugin->getPlayerFaction($sender->getName());
                                $ofaction = $this->plugin->factionPartialName($args[1]);
                                if($ofaction == false){
                                    $sender->sendMessage(TextFormat::RED."[CyboticFactions] No Faction Found By That Name!");
                                    return true;
                                }
                                if($faction == false){
                                    $sender->sendMessage(TextFormat::RED."[CyboticFactions] You must be in a faction!");
                                    return true;
                                }
                                if(!$this->plugin->isFactionsAllyed($faction, $ofaction)){
                                    $sender->sendMessage(TextFormat::RED."[CyboticFactions] You Are Not Allied With That Faction!!!");
                                    return true;
                                }
                                $result = @mysqli_query($this->plugin->db,"SELECT * FROM `home` WHERE `faction` = '$ofaction';");
                                $array = @mysqli_fetch_array($result);
                                $count = @mysqli_num_rows($result);
                                if($count > 0) {
                                        $sender->getPlayer()->teleport(new Vector3($array['x'], $array['y'], $array['z']));
                                        $sender->sendMessage(TextFormat::GREEN."[CyboticFactions] Teleported to $ofaction home.");
                                        return true;
                                } else {
                                        $sender->sendMessage(TextFormat::GOLD."[CyboticFactions] $ofaction's Home is not set.");
                                }
                                return true;
                            }else{
                                $faction = $this->plugin->getPlayerFaction($sender->getName());
                                if($faction == false){
                                    $sender->sendMessage(TextFormat::RED."[CyboticFactions] Your Not In a Faction!");
                                    return true;
                                }
                                $result = @mysqli_query($this->plugin->db,"SELECT * FROM `home` WHERE `faction` = '$faction';");
                                $array = @mysqli_fetch_assoc($result);
                                $count = @mysqli_num_rows($result);
                                if($count > 0) {
                                        $sender->getPlayer()->teleport(new Vector3($array['x'], $array['y'], $array['z']));
                                        $sender->sendMessage(TextFormat::GREEN."[CyboticFactions] Teleported home.");
                                        return true;
                                } else {
                                        $sender->sendMessage(TextFormat::GOLD."[CyboticFactions] Home is not set.");
                                }
                            }
                            return true;
                    }
                    
                    if(strtolower($args[0]) == 'info') {
                            if(isset($args[1])) {
                                    if( !(ctype_alnum($args[1])) | !($this->plugin->factionExists($args[1]))) {
                                            $sender->sendMessage("[CyboticFactions] Faction does not exist");
                                            return true;
                                    }
                                    $faction = strtolower($args[1]);
                                    $leader = $this->plugin->getLeader($faction);
                                    $numPlayers = $this->plugin->getNumberOfPlayers($faction);
                                    $fc = $this->plugin->getFactionColor($faction);
                                    $sender->sendMessage("-------------------------");
                                    $fc !== false ? $faa = $this->plugin->DecodeFactionColor($fc) : $faa = "";
                                    $sender->sendMessage("$faa$faction");
                                    $sender->sendMessage("Leader: $leader");
                                    $sender->sendMessage("# of Players: $numPlayers");
                                    $max = $this->plugin->GetMaxPlayers($faction);
                                    $sender->sendMessage("Max # of Players: $max");
                                    //$sender->sendMessage("Desc: $description");
                                    $sender->sendMessage("-------------------------");
                            } else {
                                    $faction = $this->plugin->getPlayerFaction(strtolower($sender->getName()));
                                    $result = @mysqli_query($this->plugin->db,"SELECT * FROM desc WHERE faction='$faction';");
                                    //$description = $array["description"];
                                    $leader = $this->plugin->getLeader($faction);
                                    $numPlayers = $this->plugin->getNumberOfPlayers($faction);
                                    $fc = $this->plugin->getFactionColor($faction);
                                    $sender->sendMessage("-------------------------");
                                    $fc !== false ? $faa = $this->plugin->DecodeFactionColor($fc) : $faa = "";
                                    $sender->sendMessage("$faa$faction");
                                    $sender->sendMessage("Leader: $leader");
                                    $sender->sendMessage("# of Players: $numPlayers");
                                    $max = $this->plugin->GetMaxPlayers($faction);
                                    $sender->sendMessage("Max # of Players: $max");
                                    //$sender->sendMessage("Desc: $description");
                                    $sender->sendMessage("-------------------------");
                            }
                        }
                    
                    
                    if(count($args == 1)) {
                            if (strtolower($args[0]) == "wartp"){
                                $faction = $this->plugin->getPlayerFaction($player);
                                if ($faction == false){
                                    $sender->sendMessage(TextFormat::RED."You must be in faction to use this command!!!");
                                    return true;
                                }
                                if (!isset($this->plugin->atwar[$faction])){
                                    $sender->sendMessage(TextFormat::RED."You are Not At War or Attacking!!!");
                                    return true;
                                }
                                $pos = $this->plugin->GetRandomTPArea($this->plugin->atwar[$faction], 7);
                                if ($pos !== false){
                                    $sender->teleport($pos);
                                    $sender->sendMessage(TextFormat::GREEN."Teleported To War Zone!");
                                }else{
                                    $ef = $this->plugin->atwar[$faction];
                                    $tp = $this->plugin->GetRandomFactionPlayer($ef);
                                    if(!$tp instanceof Player){
                                        $sender->sendMessage(TextFormat::GRAY."Error With TPing! $ef Has No Plot and No One Is Online!");
                                        return true;
                                    }
                                    $sender->teleport($tp);
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
                            if($args[0] == "xp"){
                                $this->plugin->getServer()->getPlayerExact($sender->getName())->addExperience($args[1]);
                                $sender->sendMessage("XP SENT!");
                            }
                            if($args[0] == "xpl"){
                                $this->plugin->getServer()->getPlayerExact($sender->getName())->addExpLevel($args[1]);
                                $sender->sendMessage("XP SENT!");
                            }
                            if(strtolower($args[0]) == "claim") {
                                    $faction = $this->plugin->getPlayerFaction($sender->getPlayer()->getName());
                                    if($faction == false) {
                                            $sender->sendMessage(TextFormat::RED."[CyboticFactions] You must be in a faction to use this.");
                                            return true;
                                    }
                                    $x = $sender->getX() >> 4;
                                    $z = $sender->getZ() >> 4;
                                    $amount = (10) * $this->plugin->prefs["PlotPrice"];
                                    if (!($this->plugin->api->myMoney($sender->getName()) >= $amount)){
                                        $sender->sendMessage(TextFormat::RED."You don't have enough Money! Plot Price: $amount");
                                        return true;
                                    }else{
                                        if($this->plugin->drawPlot($sender, $faction, $x, $z, $sender->getPlayer()->getLevel())){
                                            $sender->sendMessage(TextFormat::GREEN."Purchase Sucessful! $$amount Withdrawn To Purchase This Chunk!");
                                            $this->plugin->api->reduceMoney($sender->getName(), $amount);
                                            $this->plugin->AddFactioPower($faction, 100);
                                            $this->plugin->MessageFaction($faction, TextFormat::GRAY."Your Faction has gained 100 Power!", true);
                                            return true;
                                        }
                                        return true;
                                    }

                            }

                            if(strtolower($args[0]) == "unclaim") {
                                    if(!$this->plugin->isLeader($sender->getName())) {
                                            $sender->sendMessage(TextFormat::RED."[CyboticFactions] You must be leader to use this.");
                                            return true;
                                    }
                                    $faction = $this->plugin->getPlayerFaction($sender->getName());
                                    @mysqli_query($this->plugin->db,"DELETE FROM `plots` WHERE faction='$faction';");
                                    $sender->sendMessage(TextFormat::RED."[CyboticFactions] ALL Plots unclaimed.");
                                    $this->plugin->MessageFaction($faction, TextFormat::RED."Your Faction has Lost 110 Power!", true);
                                    $this->plugin->TakeFactionPower($faction, 110);
                            }

                            //Description

                            /*if(strtolower($args[0]) == "desc") {
                                    if($this->plugin->isInFaction($sender->getName()) == false) {
                                            $sender->sendMessage("[CyboticFactions] You must be in a faction to use this!");
                                            return true;
                                    }
                                    if($this->plugin->isLeader($player) == false) {
                                            $sender->sendMessage("[CyboticFactions] You must be leader to use this");
                                            return true;
                                    }
                                    $sender->sendMessage("[CyboticFactions] Type your description in chat. It will not be visible to other players");
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
                                    if(count($array) == 0) {
                                            $sender->sendMessage(TextFormat::RED."[CyboticFactions] You have not been invited to any factions!");
                                            return true;
                                    }
                                    $invitedTime = $array["timestamp"];
                                    $currentTime = time();
                                    echo "a";
                                    if( ($currentTime - $invitedTime) <= 600 ) { //This should be configurable
                                            $faction = $array["faction"];
                                            @mysqli_query($this->plugin->db, "INSERT INTO `master` VALUES ('".strtolower($player)."', '$faction', 'Member');");
                                            @mysqli_query($this->plugin->db,"DELETE FROM `confirm` WHERE `player` = '$lowercaseName';");
                                            $this->plugin->AddFactioPower($faction, 10);
                                            $sender->sendMessage(TextFormat::GREEN."[CyboticFactions] You successfully joined $faction!");
                                            $this->plugin->DeleteChache($player, "getPlayerFaction");
                                            $this->plugin->SetChache($player, "getPlayerFaction", $faction);
                                            $this->plugin->SetChache($player, "isInFaction","yes");
                                            $ss = $this->plugin->getServer()->getPlayerExact($array["invitedby"]);
                                            if($ss instanceof Player)$ss->sendMessage(TextFormat::GREEN."[CyboticFactions] $player joined the faction!");
                                            $this->plugin->MessageFaction($faction, TextFormat::GREEN."[CyboticFactions] $player joined the faction!!");
                                    } else {
                                            $sender->sendMessage(TextFormat::RED."[CyboticFactions] Invite has timed out!");
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
                                            $sender->sendMessage(TextFormat::RED."[CyboticFactions] You have not been invited to any factions!");
                                            return true;
                                    }
                                    $invitedTime = $array["timestamp"];
                                    $currentTime = time();
                                    if( ($currentTime - $invitedTime) <= 120 ) { //This should be configurable
                                            @mysqli_query($this->plugin->db,"DELETE * FROM `confirm` WHERE `player`='$lowercaseName';");
                                            $sender->sendMessage(TextFormat::RED."[CyboticFactions] Invite0 declined!");
                                            $this->plugin->getServer()->getPlayerExact($array["invitedby"])->sendMessage(TextFormat::RED."[CyboticFactions] $player declined the invite!");
                                    } else {
                                            $sender->sendMessage(TextFormat::RED."[CyboticFactions] Invite has timed out!");
                                            @mysqli_query($this->plugin->db,"DELETE * FROM `confirm` WHERE `player`='$lowercaseName';");
                                    }
                            }

                            //Delete

                            if(strtolower($args[0]) == "del") {
                                    $faction = $this->plugin->getPlayerFaction($sender->getName());
                                    if($faction == false){
                                        $sender->sendMessage(TextFormat::RED."You Are Not In a Faction!");
                                        return true;
                                    }
                                    if($this->plugin->isLeader($player)) {

                                        @mysqli_query($this->plugin->db,"DELETE FROM `master` WHERE `faction` = '$faction';");
                                        @mysqli_query($this->plugin->db,"DELETE FROM `home` WHERE `faction` = '$faction';");
                                        @mysqli_query($this->plugin->db,"DELETE FROM `plots` WHERE `faction` = '$faction';");
                                        @mysqli_query($this->plugin->db,"DELETE FROM `power` WHERE `faction` = '$faction';");
                                        @mysqli_query($this->plugin->db,"DELETE FROM `settings` WHERE `faction` = '$faction';");
                                        @mysqli_query($this->plugin->db,"DELETE FROM `confirm` WHERE `faction` = '$faction';");
                                        @mysqli_query($this->plugin->db,"DELETE FROM `allies` WHERE `faction` = '$faction';");
                                        $sender->sendMessage(TextFormat::GREEN."[CyboticFactions] Faction successfully disbanded!");
                                        $this->plugin->DeleteChache($faction, "factionExists");
                                        $this->plugin->DeleteChache($player, "getPlayerFaction");
                                        $this->plugin->DeleteChache($player, "isInFaction");
                                    }else {
                                        $sender->sendMessage(TextFormat::RED."[CyboticFactions] You are not leader!");
                                    }
                            }

                            //Leave

                            if(strtolower($args[0] == "leave")) {
                                    $faction = $this->plugin->getPlayerFaction($sender->getName());
                                    if($faction == false){
                                        $sender->sendMessage(TextFormat::RED."You Are Not In a Faction!");
                                        return true;
                                    }
                                    if($this->plugin->isLeader($player) == false) {
                                            $faction = $this->plugin->getPlayerFaction($player);
                                            $this->plugin->DeleteChache($player, "faction");
                                            $name = $sender->getName();
                                            @mysqli_query($this->plugin->db,"DELETE FROM `master` WHERE `player` = '$name';");
                                            $sender->sendMessage(TextFormat::GREEN."[CyboticFactions] You successfully left $faction");
                                            $this->plugin->DeleteChache($player, "getPlayerFaction");
                                            $this->plugin->DeleteChache($player, "isInFaction");
                                            $this->plugin->TakeFactionPower($faction, 20);
                                    } else {
                                            $sender->sendMessage(TextFormat::RED."[CyboticFactions] You must delete or give leadership to someone else first!!!");
                                    }
                            }

                            //Home

                            if(strtolower($args[0] == "sethome")) {
                                    $factionName = $this->plugin->getPlayerFaction($sender->getName());
                                    if($factionName == false){
                                        $sender->sendMessage(TextFormat::RED."You Are Not In a Faction!");
                                        return true;
                                    }
                                    $x = $sender->getX();
                                    $y = $sender->getY();
                                    $z = $sender->getZ();
                                    @mysqli_query($this->plugin->db,"DELETE FROM `home` WHERE `faction` = '$factionName';");
                                    @mysqli_query($this->plugin->db,"INSERT INTO `home` VALUES ('$factionName', '$x', '$y', '$z');");
                                    $sender->sendMessage(TextFormat::GREEN."[CyboticFactions] Home updated!");
                            }

                            if(strtolower($args[0] == "unsethome")) {
                                    $faction = $this->plugin->getPlayerFaction($sender->getName());
                                    if($faction == false){
                                        $sender->sendMessage(TextFormat::RED."You Are Not In a Faction!");
                                        return true;
                                    }
                                    @mysqli_query($this->plugin->db,"DELETE FROM `home` WHERE `faction` = '$faction';");
                                    $sender->sendMessage(TextFormat::GREEN."[CyboticFactions] Home unset!");
                            }

                            if(strtolower($args[0]) == "inv") {
                            $player = $sender;
                            $tinv = $player->getInventory()->getContents();
                            $player->getInventory()->clearAll();
                            $player->getInventory()->setContents($tinv);
                            $player->getInventory()->sendHeldItem($player);
                            $player->getInventory()->sendContents($player);
                            $player->getInventory()->sendSlot(0,$player);
                            $player->getInventory()->sendSlot(1,$player);
                            $player->getInventory()->sendSlot(2,$player);
                            $player->getInventory()->sendSlot(3,$player);
                            $player->getInventory()->sendSlot(4,$player);
                            $player->getInventory()->sendSlot(5,$player);
                            $player->getInventory()->sendSlot(6,$player);
                            $player->getInventory()->sendSlot(7,$player);
                            $player->getInventory()->sendSlot(8,$player);
                            $player->getInventory()->sendSlot(9,$player);
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
                                            . "/f chat or /f c\n"
                                            . "/f allychat or /f ac\n"
                                            . "/f claim\n"
                                            . "/f unclaim\n"
                                            . "/f home [faction]\n"
                                            . "/f sethome\n"
                                            . "/f ally add|remove <faction>"
                                            . "/f rewards"
                                            . "/f privacy on/off");
                            }
                            return true;
                    } else {
                            $sender->sendMessage(TextFormat::GRAY."[CyboticFactions] Please use /f help for a list of commands");
                    }
                break;
            default :
                return false;
            }
        }
    }
