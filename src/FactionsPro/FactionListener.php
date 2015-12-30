<?php

namespace FactionsPro;

use pocketmine\event\inventory\InventoryOpenEvent;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\Transaction;
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
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\network\protocol\UpdateAttributesPacket;

class FactionListener implements Listener {
	
	public $plugin;
	private $sneak;
    private $nt;


        public function __construct(FactionMain $pg) {
		$this->plugin = $pg;
	}

	public function playerDeath(PlayerDeathEvent $event) {
            $playern = $event->getEntity()->getName();
            $cause = $event->getEntity()->getLastDamageCause();
            if($cause->getCause() == EntityDamageEvent::CAUSE_ENTITY_ATTACK){
                
                if($cause instanceof EntityDamageByEntityEvent && $event->getEntity() instanceof Player){
                        $e = $cause->getDamager();
                        if($e instanceof Player){
                            $event->getEntity()->addExperience(0);
                            $e->addExperience(.25);
                            $killername = $e->getName();
                            //Killer Bonus
                            $kf = $this->plugin->getPlayerFaction($killername);
                            if($kf !== false)$this->plugin->AddFactioPower($kf, 1);
                            if(!isset($this->plugin->death[$killername])){
                                $this->plugin->death[$killername] = 1;
                            }else{
                                $this->plugin->death[$killername]++;
                            }
                            if($this->plugin->death[$killername] == 5){
                                $this->plugin->getServer ()->broadcastMessage (TextFormat::GREEN."$killername is on a 5 KillStreak!");
                                if($kf !== false)$this->plugin->AddFactioPower($kf, 5);
                            }
                            if($this->plugin->death[$killername] == 8){
                                $this->plugin->getServer ()->broadcastMessage (TextFormat::AQUA."$killername is on a 8 KillStreak!");
                                if($kf !== false)$this->plugin->AddFactioPower($kf, 8);
                            }
                            if($this->plugin->death[$killername] == 10){
                                $this->plugin->getServer ()->broadcastMessage (TextFormat::LIGHT_PURPLE."$killername is on a 10 KillStreak!");
                                if($kf !== false)$this->plugin->AddFactioPower($kf, 10);
                            }
                            if($this->plugin->death[$killername] > 10){
                                $kills = $this->plugin->death[$killername];
                                $this->plugin->getServer ()->broadcastMessage (TextFormat::LIGHT_PURPLE."$killername is on a $kills KillStreak!");
                                if($kf !== false)$this->plugin->AddFactioPower($kf, $kills*2);
                            }
                        }
                }
            }
            //Death Fine
            $df = $this->plugin->getPlayerFaction($playern);
            $this->plugin->death[$event->getEntity()->getName()] = 0;
            if($df !== false)$this->plugin->TakeFactionPower($df, 3);
        }
        
        public function QuitEvent(PlayerQuitEvent $event) {
            if(($fac = $this->plugin->getPlayerFaction($event->getPlayer()->getName())) !== false){
                $this->plugin->MessageFaction($fac, TextFormat::YELLOW.$event->getPlayer()->getName()." Has Left!");
            }
            
            if(isset($this->plugin->pvplog[$event->getPlayer()->getName()])){
                $time = strtotime("now");
                if($this->plugin->pvplog[$event->getPlayer()->getName()] > $time){
                    $dro = $event->getPlayer()->getDrops();
                    foreach($dro as $item){
                        $event->getPlayer()->getLevel()->dropItem($event->getPlayer(), $item);
                    }
                    $event->getPlayer()->getInventory()->clearAll();
                    $event->getPlayer()->teleport($event->getPlayer()->getLevel()->getSafeSpawn());
                }
            }
         }
        
        public function EnchantItemInHand(Player $player) {
            $player->getInventory()->sendContents($player);
            $hand = $player->getInventory()->getItemInHand();
            $rank = $this->plugin->GetRank($player);
            if($this->plugin->GetRank($player) == false && !$player->isOp()){
                $player->sendMessage(TextFormat::AQUA."Support the server today! \nBuy Ranks from $5 at www.cyberechpp.com/MCPE");
                $player->sendMessage(TextFormat::AQUA."Ranks activate immediately");
            }
        if($hand->getCount() > 1){
        $player->sendMessage(TextFormat::RED."One Item at a Time Please!");
        }
            if($hand == null)return;
            if($hand->hasEnchantments()){
              $player->sendMessage(TextFormat::RED."[ENCHANT] Your Item is already enchanted!!!");
              return;
            }
            if($hand->getId() == Item::DIAMOND_SWORD || $hand->getId() == Item::IRON_SWORD || $hand->getId() == Item::STONE_SWORD || $hand->getId() == Item::GOLDEN_SWORD || $hand->getId() == Item::WOODEN_SWORD){
                $rand[] = 9;
                $rand[] = 10;
                $rand[] = 11;
                $rand[] = 12;
                $rand[] = 13;
                $rand[] = 17;
                $id = $rand[mt_rand(0, (count($rand)-1))];
                $plevel = mt_rand(1, 8);
                $enchant = \pocketmine\item\enchantment\Enchantment::getEnchantment($id);
                $enchant->setLevel(1);
                if($player->getExpLevel() >= $plevel){
                    $player->takeExpLevel($plevel);
                    $hand->addEnchantment($enchant);
                    if(!$hand->hasEnchantments())$player->sendMessage ("ERROR");
                    $player->sendMessage(TextFormat::GREEN."[ENCHANT] Took ".TextFormat::GOLD.$plevel." Levels".TextFormat::GREEN." to Enchant Your ".TextFormat::AQUA.$hand->getName().TextFormat::GREEN." was given Enchant ".TextFormat::LIGHT_PURPLE.$enchant->getName());
                    $player->getInventory()->setItemInHand($hand);
                    $player->getInventory()->sendContents($player);
                    return;
                }else{
                    $player->sendMessage(TextFormat::RED."[ENCHANT]".TextFormat::AQUA."$plevel Levels of XP".TextFormat::RED." was need in order to give your item ".TextFormat::LIGHT_PURPLE.$enchant->getName()."!");
                    return;
                }
            }
            if(strpos($hand->getName(), "axe") || strpos($hand->getName(), "shovel") ){
                $id = mt_rand(15, 18);
                $plevel = mt_rand(1, 8);
                $enchant = \pocketmine\item\enchantment\Enchantment::getEnchantment($id);
                $enchant->setLevel(1);
                if($player->getExpLevel() >= $plevel){
                    $player->takeExpLevel($plevel);
                    $hand->addEnchantment($enchant);
                    $player->sendMessage(TextFormat::GREEN."[ENCHANT] Your ".TextFormat::AQUA.$hand->getName().TextFormat::GREEN." was given Enchant ".TextFormat::LIGHT_PURPLE.$enchant->getName());
                    $player->getInventory()->setItemInHand($hand);
                    $player->getInventory()->sendContents($player);
                    return;
                }else{
                    $player->sendMessage(TextFormat::RED."[ENCHANT]".TextFormat::AQUA."$plevel Levels of XP".TextFormat::RED." was need in order to give your item ".TextFormat::LIGHT_PURPLE.$enchant->getName()."!");
                    return;
                }
            }
            
            if($hand instanceof \pocketmine\item\Armor){
                if(strpos($hand->getName(), "helment")){
                    $rand[] = 0;
                    $rand[] = 1;
                    $rand[] = 3;
                    $rand[] = 4;
                    $rand[] = 6;
                    $rand[] = 8;
                    $rand[] = 17;
                    $id = $rand[mt_rand(0, (count($rand)-1))];
                    $plevel = mt_rand(1, 8);
                    $enchant = \pocketmine\item\enchantment\Enchantment::getEnchantment($id);
                    $enchant->setLevel(1);
                    if($player->getExpLevel() >= $plevel){
                        $player->takeExpLevel($plevel);
                        $hand->addEnchantment($enchant);
                        $player->sendMessage(TextFormat::GREEN."[ENCHANT] Your ".TextFormat::AQUA.$hand->getName().TextFormat::GREEN." was given Enchant ".TextFormat::LIGHT_PURPLE.$enchant->getName());
                        $player->getInventory()->setItemInHand($hand);
                        $player->getInventory()->sendContents($player);
                        return;
                    }else{
                        $player->sendMessage(TextFormat::RED."[ENCHANT]".TextFormat::AQUA."$plevel Levels of XP".TextFormat::RED." was need in order to give your item ".TextFormat::LIGHT_PURPLE.$enchant->getName()."!");
                        return;
                    }
                }
                if(strpos($hand->getName(), "chestplate")){
                    $rand[] = 0;
                    $rand[] = 1;
                    $rand[] = 3;
                    $rand[] = 4;
                    $rand[] = 5;
                    $rand[] = 17;
                    $id = $rand[mt_rand(0, (count($rand)-1))];
                    $plevel = mt_rand(1, 8);
                    $enchant = \pocketmine\item\enchantment\Enchantment::getEnchantment($id);
                    $enchant->setLevel(1);
                    if($player->getExpLevel() >= $plevel){
                        $player->takeExpLevel($plevel);
                        $hand->addEnchantment($enchant);
                        $player->sendMessage(TextFormat::GREEN."[ENCHANT] Your ".TextFormat::AQUA.$hand->getName().TextFormat::GREEN." was given Enchant ".TextFormat::LIGHT_PURPLE.$enchant->getName());
                        $player->getInventory()->setItemInHand($hand);
                        $player->getInventory()->sendContents($player);
                        return;
                    }else{
                        $player->sendMessage(TextFormat::RED."[ENCHANT]".TextFormat::AQUA."$plevel Levels of XP".TextFormat::RED." was need in order to give your item ".TextFormat::LIGHT_PURPLE.$enchant->getName()."!");
                        return;
                    }
                }
                if(strpos($hand->getName(), "boots")){
                    $rand[] = 0;
                    $rand[] = 1;
                    $rand[] = 2;
                    $rand[] = 3;
                    $rand[] = 4;
                    $rand[] = 7;
                    $rand[] = 17;
                    $id = $rand[mt_rand(0, (count($rand)-1))];
                    $plevel = mt_rand(1, 8);
                    $enchant = \pocketmine\item\enchantment\Enchantment::getEnchantment($id);
                    $enchant->setLevel(1);
                    if($player->getExpLevel() >= $plevel){
                        $player->takeExpLevel($plevel);
                        $hand->addEnchantment($enchant);
                        $player->sendMessage(TextFormat::GREEN."[ENCHANT] Your ".TextFormat::AQUA.$hand->getName().TextFormat::GREEN." was given Enchant ".TextFormat::LIGHT_PURPLE.$enchant->getName());
                        $player->getInventory()->setItemInHand($hand);
                        $player->getInventory()->sendContents($player);
                        return;
                    }else{
                        $player->sendMessage(TextFormat::RED."[ENCHANT]".TextFormat::AQUA."$plevel Levels of XP".TextFormat::RED." was need in order to give your item ".TextFormat::LIGHT_PURPLE.$enchant->getName()."!");
                        return;
                    }
                }
                $rand[] = 0;
                $rand[] = 1;
                $rand[] = 3;
                $rand[] = 4;
                $rand[] = 17;
                $id = $rand[mt_rand(0, (count($rand)-1))];
                $plevel = mt_rand(1, 8);
                $enchant = \pocketmine\item\enchantment\Enchantment::getEnchantment($id);
                $enchant->setLevel(1);
                if($player->getExpLevel() >= $plevel){
                    $player->takeExpLevel($plevel);
                    $hand->addEnchantment($enchant);
                    $player->sendMessage(TextFormat::GREEN."[ENCHANT] Your ".TextFormat::AQUA.$hand->getName().TextFormat::GREEN." was given Enchant ".TextFormat::LIGHT_PURPLE.$enchant->getName());
                    $player->getInventory()->setItemInHand($hand);
                    $player->getInventory()->sendContents($player);
                    return;
                }else{
                    $player->sendMessage(TextFormat::RED."[ENCHANT]".TextFormat::AQUA."$plevel Levels of XP".TextFormat::RED." was need in order to give your item ".TextFormat::LIGHT_PURPLE.$enchant->getName()."!");
                    return;
                }
            }
            if($hand instanceof \pocketmine\item\Bow){
                $rand[] = 19;
                $rand[] = 20;
                $rand[] = 21;
                $rand[] = 22;
                $rand[] = 17;
                $id = $rand[mt_rand(0, (count($rand)-1))];
                $plevel = mt_rand(1, 8);
                $enchant = \pocketmine\item\enchantment\Enchantment::getEnchantment($id);
                $enchant->setLevel(1);
                if($player->getExpLevel() >= $plevel){
                    $player->takeExpLevel($plevel);
                    $hand->addEnchantment($enchant);
                    $player->sendMessage(TextFormat::GREEN."[ENCHANT] Your ".TextFormat::AQUA.$hand->getName().TextFormat::GREEN." was given Enchant ".TextFormat::LIGHT_PURPLE.$enchant->getName());
                    $player->getInventory()->setItemInHand($hand);
                    $player->getInventory()->sendContents($player);
                    return;
                }else{
                    $player->sendMessage(TextFormat::RED."[ENCHANT]".TextFormat::AQUA."$plevel Levels of XP".TextFormat::RED." was need in order to give your item ".TextFormat::LIGHT_PURPLE.$enchant->getName()."!");
                    return;
                }
            }
            if($hand instanceof \pocketmine\item\Book){
                $id = mt_rand(0, 24);
                $plevel = mt_rand(1, 8);
                $enchant = \pocketmine\item\enchantment\Enchantment::getEnchantment($id);
                $enchant->setLevel(1);
                if($player->getExpLevel() >= $plevel){
                    $player->takeExpLevel($plevel);
                    $hand->addEnchantment($enchant);
                    $player->sendMessage(TextFormat::GREEN."[ENCHANT] Your ".TextFormat::AQUA.$hand->getName().TextFormat::GREEN." was given Enchant ".TextFormat::LIGHT_PURPLE.$enchant->getName());
                    $player->getInventory()->setItemInHand($hand);
                    $player->getInventory()->sendContents($player);
                    return;
                }else{
                    $player->sendMessage(TextFormat::RED."[ENCHANT]".TextFormat::AQUA."$plevel Levels of XP".TextFormat::RED." was need in order to give your item ".TextFormat::LIGHT_PURPLE.$enchant->getName()."!");
                    return;
                }
            }
        }
         
         /**
          * 
          * @
          * @param PlayerJoinEvent $event
          * @return boolean
          */
        public function joinEvent(PlayerJoinEvent $event) {
            if($event->getPlayer()->getY() < 10)$event->getPlayer()->teleport($event->getPlayer ()->getLevel ()->getSafeSpawn ($event->getPlayer()));
            $this->plugin->uuid[$event->getPlayer()->getName()][$event->getPlayer()->getClientId()] = date(DATE_COOKIE);
            $this->plugin->death[$event->getPlayer()->getName()] = 0;
            $player = $event->getPlayer()->getName();
            /*$pk = new UpdateAttributesPacket();
            $pk->minValue = 0;
            $pk->maxValue = 20;
            $pk->value = $this->plugin->experience[$player];
            $pk->name = "player.experience";
            $event->getPlayer()->dataPacket($pk);
            $pk = new UpdateAttributesPacket();
            $pk->minValue = 0;
            $pk->maxValue = 20;
            $pk->value = $this->plugin->experience_level[$player];
            $pk->name = "player.level";
            $event->getPlayer()->dataPacket($pk);
            $event->getPlayer()->getLevel();*/
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
            $faction = $this->plugin->getPlayerFaction($player);
            $abcdefg = "";
            if($faction !== false){
                $this->plugin->MessageFaction($faction, TextFormat::GREEN."$player Has Joined!");
                $fc = $this->plugin->DecodeFactionColor($this->plugin->getFactionColor($faction));
                if($fc == false){
                    $fc = TextFormat::GRAY;
                }
                $abcdefg = $fc."[$faction] \n".TextFormat::RESET.$event->getPlayer()->getName();
                $event->getPlayer()->setNameTag($abcdefg);
                $this->nt[$event->getPlayer()->getName()] = $abcdefg;
            }else{
                 $abcdefg = $event->getPlayer()->getName();
            }//AceJace
            
            //if($this->plugin->GetRank($player) == false)return true;
            //if($this->plugin->CC !== null){
            $rank = $this->plugin->GetRank($player);    
            if($rank == false)return true;
            if($rank == "yes"){
                $this->plugin->SetChache($player, "isInFaction", "no");
                return true;
            }
                echo "$player PASSED!!!!!";
                $a = @mysqli_fetch_assoc(@mysqli_query( $this->plugin->db2,"SELECT * FROM `ranks` WHERE `name` = '$player'"));
                if($a['color'] !== ""){
                    $c = explode(";", $a['color']);
                    $color = "";
                    foreach($c as $colorcode){
                        if($colorcode == "")break;
                        $color .= "§".$array[$colorcode];
                    }
                }else{
                    $color = "§a";
                }
                $rankt = $a['prefix'];
                if($rankt == "")$rankt = $rank;
                if($rankt == "")$rankt = "UNKNOWN";
                if($rank !== "Guest")$this->plugin->getServer ()->getScheduler ()->scheduleDelayedTask (new SetNameTeg($this->plugin, $event->getPlayer(), $color.$rankt."\n".$abcdefg), 20);
                if($rank !== "Guest" && $rank !== "OP")$this->plugin->CC->yml["prefixs"][$player] = $color.$rank;
                if($rank !== "Guest")$event->getPlayer()->setNameTag ($color.$rankt."\n".$abcdefg);
                $this->nt[$event->getPlayer()->getName()] = $color.$rankt."\n".$abcdefg;
                echo $color.$rankt."-$rank\n".$abcdefg;
                if($rank !== "Guest" && ($rank == "OP" || $rank == "BUILDER"))$event->getPlayer()->setOp(true);
                //if($rank == "Guest")$this->plugin->CC->yml["prefixs"][$player] = null;
                if($rank == "Guest")$event->getPlayer()->setOp (false);
                //$this->CC->yml["prefixs"][$player] = "§a".$rank;
           // }
        }
        
        public function BucketEmpty(\pocketmine\event\player\PlayerBucketEmptyEvent $ev) {
            if($ev->getPlayer()->isOp())return;
            $spawn = $ev->getPlayer()->getLevel()->getSpawnLocation();
            $sx = $spawn->getX(); 
            $sz = $spawn->getZ();
            $px = $ev->getPlayer()->getX();
            $pz = $ev->getPlayer()->getZ();
            $x = false;
            $z = false;
            if(($sx - 200) < $px && $px < ($sx + 200)){
                $x = true;
            }
            if(($sz - 200) < $pz && $pz < ($sz + 200)){
                $z = true;
            }
            if($z && $x){
                $ev->getPlayer()->sendMessage(TextFormat::RED."No Usgin Buckets 200 Block close to spawn");
                $ev->setCancelled();
            }
        }
        
        public function BucketFill(\pocketmine\event\player\PlayerBucketFillEvent $ev) {
            if($ev->getPlayer()->isOp())return;
            $spawn = $ev->getPlayer()->getLevel()->getSpawnLocation();
            $sx = $spawn->getX(); 
            $sz = $spawn->getZ();
            $px = $ev->getPlayer()->getX();
            $pz = $ev->getPlayer()->getZ();
            $x = false;
            $z = false;
            if(($sx - 200) < $px && $px < ($sx + 200)){
                $x = true;
            }
            if(($sz - 200) < $pz && $pz < ($sz + 200)){
                $z = true;
            }
            if($z && $x){
                $ev->getPlayer()->sendMessage(TextFormat::RED."No Usgin Buckets 200 Block close to spawn");
                $ev->setCancelled();
            }
        }
        
	public function factionPVP(EntityDamageEvent $factionDamage) {
                if($factionDamage->isCancelled())return true;
		if($factionDamage instanceof EntityDamageByEntityEvent) {
			if(!($factionDamage->getEntity() instanceof Player) || !($factionDamage->getDamager() instanceof Player))return true;
                        $player1 = $factionDamage->getEntity()->getPlayer()->getName();
                        $player2 = $factionDamage->getDamager()->getPlayer()->getName();
                        $factionDamage->getDamager()->getPlayer()->sendPopup($player1."'s Health: ".$factionDamage->getEntity()->getPlayer()->getHealth()."/".$factionDamage->getEntity()->getPlayer()->getMaxHealth());
                        $faction1 = $this->plugin->getPlayerFaction($player1);
                        $faction2 = $this->plugin->getPlayerFaction($player2);
			if($faction1 == false || $faction2 == false ) return true;
                        if($faction1 == $faction2) {
                                $factionDamage->setCancelled(true);
                                return true;
                        }
                        if($this->plugin->isFactionsAllyed($faction1, $faction2)){
                            $factionDamage->setCancelled(true);
                            return true;
                        }else{
                            $t = strtotime("+ 10 Secs");
                            $this->plugin->pvplog[$player1] = $t;
                            $this->plugin->pvplog[$player2] = $t;
                        }
                        return true;
		}
	}
	public function factionBlockBreakProtect(BlockBreakEvent $event) {
            if ($event->getPlayer() instanceof Player){
                if($event->getPlayer()->isOp()){
                    $this->XPforbrokenblock($event->getPlayer(), $event->getBlock()->getId());
                    return true;
                }
            }
            if($event->isCancelled())return true;
            $chunkfaction = $this->plugin->GetChunkOwner($event->getBlock()->getX() >> 4, $event->getBlock()->getZ() >> 4);
            if($chunkfaction !== false) {
                if(($pf = $this->plugin->getPlayerFaction($event->getPlayer()->getName())) !== false) {
                    if($pf == $chunkfaction)return true;
                    if($this->plugin->AtWar($pf,$chunkfaction))return true;
                    $event->setCancelled(true);
                    $event->getPlayer()->sendMessage("[CyberFaction] This area is claimed by $chunkfaction");
                    return true;    
                }
                $event->setCancelled(true);        
            }
            $this->XPforbrokenblock($event->getPlayer(), $event->getBlock()->getId());
            $event->setCancelled(false);
	}
	
        public function XPforbrokenblock(Player $player, $id){
            $var[1]=.02;
            $var[2]=.01;
            $var[3]=.01;
            $var[4]=.015;
            $var[13]=.03;$var[14]=.06;
            $var[15]=.75;$var[16]=.55;
            $var[17]=.10;$var[21]=.50;$var[56]=1.00;$var[73]=7.5;$var[74]=.25;$var[153]=.10;$var[129]=1.50;$var[115]=1.25;$var[246]=.10;
            if(isset($var[$id]))$player->addExperience(($var[$id]/3));
        }


        public function factionBlockPlaceProtect(BlockPlaceEvent $event) {
            if($event->getPlayer()->getInventory()->getItemInHand()->getId() == 120){
                $event->setCancelled(true);
                return true;
            }
            if ($event->getPlayer() instanceof Player){
                if($event->getPlayer()->isOp()){
                    return true;
                }
            }
            if($event->isCancelled())return true;
            if($event->getBlock()->getId() == Item::EMERALD_BLOCK){
                $event->setCancelled();
                return true;
            }
            
            if($event->getBlock()->getId() == Item::GLOWING_OBSIDIAN){
               $eblock = $event->getBlock();
               $x = $eblock->getX() >> 4;
               $z = $eblock->getZ() >> 4;
               if($co = $this->plugin->GetChunkOwner($x, $z) !== false){
                   $this->plugin->SetChache($x1."|".$z1, "ChunkClaimed", false);
                   $a = @mysqli_fetch_assoc(@mysqli_query($this->db, "SELECT * FROM `plots` WHERE `x` = '$x' AND `z` = '$z';"));
                   $id  =  $a['id'];
                   @mysqli_query($this->db, "DELETE FROM `plots` WHERE `id` = '$id;");
               }
               $event->getPlayer()->sendMessage("CyberFaction] That chunk has been unclaimed!");
               $event->getPlayer()->getLevel()->setBlock($eblock, new \pocketmine\block\Air(),  true, false);
               return true;
            }
            
            $chunkfaction = $this->plugin->GetChunkOwner($event->getBlock()->getX() >> 4, $event->getBlock()->getZ() >> 4);
            if($chunkfaction !== false) {
                if(($pf = $this->plugin->getPlayerFaction($event->getPlayer()->getName())) !== false) {
                    if($pf == $chunkfaction)return true;
                    if($this->plugin->AtWar($pf,$chunkfaction))return true;
                    $event->setCancelled(true);
                    $event->getPlayer()->sendMessage("[CyberFaction] This area is claimed by $chunkfaction");
                    return true;    
                }
                $event->setCancelled(true);
                return true;
            }
	}
        
        /*Add Soon
         * public function PlayerItemHeldEvent(\pocketmine\event\player\PlayerItemHeldEvent $event) {
            $player = $event->getPlayer();
            $playern = $player->getName();
            if(in_array($playern, $this->plugin->block, true)){
                $block = $event->getItem();
                $bid = $block->getId();
                if($bid == Item::DIAMOND_BLOCK){
                    //Use Diamond Chest
                    $player->getInventory()->clearAll();
                    $player->getInventory()->setContents($this->plugin->saveinv[$playern]);
                    $this->OpenDiamondChest($player, $player->getLevel());
                }
                if($bid == Item::EMERALD_BLOCK){
                    $player->getInventory()->clearAll();
                    $player->getInventory()->setContents($this->plugin->saveinv[$playern]);
                    $this->OpenEmeraldChest($player, $player->getLevel());
                    //Use Emerald Chest
                }
                if($bid == Item::IRON_BLOCK){
                    $player->getInventory()->clearAll();
                    //Use Iron Chest
                }
                if($bid == Item::GOLD_BLOCK){
                    $player->getInventory()->clearAll();
                    //Use Gold Chest
                }
                if($bid == Item::COAL_BLOCK){
                    $player->getInventory()->clearAll();
                    //Use Coal Chest
                }
                
            }
        }*/
/*        
        public function OpenEmeraldChest(Player $player, \pocketmine\level\Level $level) {
            $playern = $player->getName();
            //Number Of Winneings
            $rand = mt_rand(0, 100);
            $numofwins = 2;
            if($rand >= 75)$numofwins = mt_rand(2, 10);
            for($x = 0;$x <= $numofwins; $x++){
                $rand = mt_rand(0, 100);
                //Diamond
                if($rand > 90){
                    $item = new Item(264, 0, 0);
                    $rand = mt_rand(0, 100);
                    if($rand > 90){
                        $rand = mt_rand(0, 100);
                        if($rand > 90){
                            $rand = mt_rand(1, 25);
                            $item->setCount($rand);
                        }
                        $rand = mt_rand(1, 10);
                        $item->setCount($rand);
                    }
                    $rand = mt_rand(1, 5);
                    $item->setCount($rand);
                    $player->getInventory()->addItem(clone $item);
                    $won[] = "$rand Diamonds!";
                    continue;
                }
                //TNT
                if($rand > 80){
                    $item = new Item(Item::TNT, 0, 0);
                    $rand = mt_rand(0, 100);
                    if($rand > 90){
                        $rand = mt_rand(0, 100);
                        if($rand > 90){
                            $rand = mt_rand(1, 25);
                            $item->setCount($rand);
                        }
                        $rand = mt_rand(1, 10);
                        $item->setCount($rand);
                    }
                    $rand = mt_rand(1, 5);
                    $item->setCount($rand);
                    $player->getInventory()->addItem(clone $item);
                    $won[] = "$rand TNT!";
                    continue;
                }
                //Iron
                if($rand > 70){
                    $item = new Item(Item::IRON_INGOT, 0, 0);
                    $rand = mt_rand(0, 100);
                    if($rand > 90){
                        $rand = mt_rand(0, 100);
                        if($rand > 90){
                            $rand = mt_rand(1, 25);
                            $item->setCount($rand);
                        }
                        $rand = mt_rand(1, 10);
                        $item->setCount($rand);
                    }
                    $rand = mt_rand(1, 5);
                    $item->setCount($rand);
                    $player->getInventory()->addItem(clone $item);
                    $won[] = "$rand Iron Ingots!";
                    continue;
                }
                
                //Gold
                if($rand > 60){
                    $item = new Item(Item::GOLD_INGOT, 0, 0);
                    $rand = mt_rand(0, 100);
                    if($rand > 90){
                        $rand = mt_rand(0, 100);
                        if($rand > 90){
                            $rand = mt_rand(1, 25);
                            $item->setCount($rand);
                        }
                        $rand = mt_rand(1, 10);
                        $item->setCount($rand);
                    }
                    $rand = mt_rand(1, 5);
                    $item->setCount($rand);
                    $player->getInventory()->addItem(clone $item);
                    $won[] = "$rand Gold Ingots!";
                    continue;
                }
                
                //Coal
                if($rand > 50){
                    $item = new Item(Item::COAL, 0, 0);
                    $rand = mt_rand(0, 100);
                    if($rand > 90){
                        $rand = mt_rand(0, 100);
                        if($rand > 90){
                            $rand = mt_rand(1, 25);
                            $item->setCount($rand);
                        }
                        $rand = mt_rand(1, 10);
                        $item->setCount($rand);
                    }
                    $rand = mt_rand(1, 5);
                    $item->setCount($rand);
                    $player->getInventory()->setContents($this->plugin->saveinv[$playern]);
                    $player->getInventory()->addItem(clone $item);
                    $won[] = "$rand Coal!";
                    continue;
                }
                
                 //Obsidian
                if($rand > 40){
                    $item = new Item(Item::OBSIDIAN, 0, 0);
                    $rand = mt_rand(0, 100);
                    if($rand > 90){
                        $rand = mt_rand(0, 100);
                        if($rand > 90){
                            $rand = mt_rand(1, 25);
                            $item->setCount($rand);
                        }
                        $rand = mt_rand(1, 10);
                        $item->setCount($rand);
                    }
                    $rand = mt_rand(1, 5);
                    $item->setCount($rand);
                    $player->getInventory()->setContents($this->plugin->saveinv[$playern]);
                    $player->getInventory()->addItem(clone $item);
                    $won[] = "$rand Obsidian!";
                    continue;
                }
                
                 //Netherrack
                if($rand > 30){
                    $item = new Item(Item::NETHERRACK, 0, 0);
                    $rand = mt_rand(0, 100);
                    if($rand > 90){
                        $rand = mt_rand(0, 100);
                        if($rand > 90){
                            $rand = mt_rand(1, 25);
                            $item->setCount($rand);
                        }
                        $rand = mt_rand(1, 10);
                        $item->setCount($rand);
                    }
                    $rand = mt_rand(1, 5);
                    $item->setCount($rand);
                    $player->getInventory()->setContents($this->plugin->saveinv[$playern]);
                    $player->getInventory()->addItem(clone $item);
                    $won[] = "$rand NetherRack!";
                    continue;
                }
                
                 //NETHER_QUARTZ
                if($rand > 20){
                    $item = new Item(Item::NETHER_QUARTZ, 0, 0);
                    $rand = mt_rand(0, 100);
                    if($rand > 90){
                        $rand = mt_rand(0, 100);
                        if($rand > 90){
                            $rand = mt_rand(1, 25);
                            $item->setCount($rand);
                        }
                        $rand = mt_rand(1, 10);
                        $item->setCount($rand);
                    }
                    $rand = mt_rand(1, 5);
                    $item->setCount($rand);
                    $player->getInventory()->setContents($this->plugin->saveinv[$playern]);
                    $player->getInventory()->addItem(clone $item);
                    $won[] = "$rand Nether Quartz!";
                    continue;
                }
            }
            $message = TextFormat::GREEN."Congradulations!!!! You Won: \n";
            foreach($won as $w)$message .= TextFormat::AQUA." > ".$w."!!! \n";
            $message .= TextFormat::GREEN."Wow You Won A Lot!";
            $player->sendMessage($message);
            $n = array_search($player->getName(),$this->plugin->block);
            unset($this->plugin->block[$n]);
        }
        public function OpenDiamondChest(Player $player, \pocketmine\level\Level $level) {
            $playern = $player->getName();
            //Number Of Winneings
            $rand = mt_rand(0, 100);
            $numofwins = 1;
            if($rand >= 75)$numofwins = mt_rand(0, 5);
            for($x = 0;$x <= $numofwins; $x++){
                $rand = mt_rand(0, 100);
                //Diamond
                if($rand > 90){
                    $item = new Item(264, 0, 0);
                    $rand = mt_rand(0, 100);
                    if($rand > 90){
                        $rand = mt_rand(0, 100);
                        if($rand > 90){
                            $rand = mt_rand(1, 25);
                            $item->setCount($rand);
                        }
                        $rand = mt_rand(1, 10);
                        $item->setCount($rand);
                    }
                    $rand = mt_rand(1, 5);
                    $item->setCount($rand);
                    $player->getInventory()->addItem(clone $item);
                    $won[] = "$rand Diamonds!";
                    continue;
                }
                //TNT
                if($rand > 75){
                    $item = new Item(Item::TNT, 0, 0);
                    $rand = mt_rand(0, 100);
                    if($rand > 90){
                        $rand = mt_rand(0, 100);
                        if($rand > 90){
                            $rand = mt_rand(1, 25);
                            $item->setCount($rand);
                        }
                        $rand = mt_rand(1, 10);
                        $item->setCount($rand);
                    }
                    $rand = mt_rand(1, 5);
                    $item->setCount($rand);
                    $player->getInventory()->addItem(clone $item);
                    $won[] = "$rand TNT!";
                    continue;
                }
                //Iron
                if($rand > 50){
                    $item = new Item(Item::IRON_INGOT, 0, 0);
                    $rand = mt_rand(0, 100);
                    if($rand > 90){
                        $rand = mt_rand(0, 100);
                        if($rand > 90){
                            $rand = mt_rand(1, 25);
                            $item->setCount($rand);
                        }
                        $rand = mt_rand(1, 10);
                        $item->setCount($rand);
                    }
                    $rand = mt_rand(1, 5);
                    $item->setCount($rand);
                    $player->getInventory()->addItem(clone $item);
                    $won[] = "$rand Iron Ingots!";
                    continue;
                }
                
                //Gold
                if($rand > 25){
                    $item = new Item(Item::GOLD_INGOT, 0, 0);
                    $rand = mt_rand(0, 100);
                    if($rand > 90){
                        $rand = mt_rand(0, 100);
                        if($rand > 90){
                            $rand = mt_rand(1, 25);
                            $item->setCount($rand);
                        }
                        $rand = mt_rand(1, 10);
                        $item->setCount($rand);
                    }
                    $rand = mt_rand(1, 5);
                    $item->setCount($rand);
                    $player->getInventory()->addItem(clone $item);
                    $won[] = "$rand Gold Ingots!";
                    continue;
                }
                
                //Coal
                $item = new Item(Item::COAL, 0, 0);
                $rand = mt_rand(0, 100);
                if($rand > 90){
                    $rand = mt_rand(0, 100);
                    if($rand > 90){
                        $rand = mt_rand(1, 25);
                        $item->setCount($rand);
                    }
                    $rand = mt_rand(1, 10);
                    $item->setCount($rand);
                }
                $rand = mt_rand(1, 5);
                $item->setCount($rand);
                $player->getInventory()->setContents($this->plugin->saveinv[$playern]);
                $player->getInventory()->addItem(clone $item);
                $won[] = "$rand Coal!";
                continue;
            }
            $message = TextFormat::GREEN."Congradulations!!!! You Won: \n";
            foreach($won as $w)$message .= TextFormat::AQUA." > ".$w."!!! \n";
            $message .= TextFormat::GREEN."Wow You Won A Lot!";
            $player->sendMessage($message);
            $n = array_search($player->getName(),$this->plugin->block);
            unset($this->plugin->block[$n]);
        }
*/

        public function PlayerInteractEvent(PlayerInteractEvent $event) {
            $player = $event->getPlayer();
            $playern = $event->getPlayer()->getName();
            if($event->getBlock()->getId() == 116){
                $event->setCancelled();
                $this->EnchantItemInHand($event->getPlayer());
                return true;
            }
            /*
             * if($player->getInventory()->getItemInHand()->getId() == \pocketmine\item\Item::EMERALD_BLOCK){
                //New Random Chest!
                //Save inv
                $this->plugin->saveinv[$playern] = $player->getInventory()->getContents();
                $db2 = $this->plugin->db2;
                $this->plugin->block[] = $playern;
                $a1 = @mysqli_query($db2, "SELECT * FROM `Chests` WHERE `name` = '$playern'");
                $a2 = @mysqli_fetch_assoc($a1);
                $diamond = $a2['diamond'];
                $emerald = $a2['emerald'];
                $iron = $a2['iron'];
                $gold = $a2['gold'];
                $contents[] = new Item(Item::DIAMOND_BLOCK, 0, $diamond);
                $contents[] = new Item(Item::EMERALD_BLOCK, 0, $emerald);
                $contents[] = new Item(Item::TNT, 0, $iron);
                $contents[] = new Item(Item::GOLD_BLOCK, 0, $gold);
                $player->getInventory()->setContents($contents);
                
                //$player->getInventory()->setHotbarSlotIndex($index, $slot);
                $player->getInventory()->setHotbarSlotIndex(0, 4);
                $player->getInventory()->setHotbarSlotIndex(1, 0);
                $player->getInventory()->setHotbarSlotIndex(2, 1);
                $player->getInventory()->setHotbarSlotIndex(3, 2);
                $player->getInventory()->setHotbarSlotIndex(4, 3);
                $player->getInventory()->sendHeldItem($player);
                $player->getInventory()->sendContents($player);
                $player->getInventory()->sendSlot(0,$player);
                $player->getInventory()->sendSlot(1,$player);
                $player->getInventory()->sendSlot(2,$player);
                $player->getInventory()->sendSlot(3,$player);
                $player->getInventory()->sendSlot(4,$player);
                $player->despawnFromAll();
                $player->sendMessage(TextFormat::GREEN."Please Choose a Chest Type!");
                //$this->OpenDiamondChest($player);
            }
            */
            if($player->getInventory()->getItemInHand()->getId() == Item::COMPASS){
                $this->plugin->getServer()->dispatchCommand($player, "home 1");
            }
            if($player->getInventory()->getItemInHand()->getId() == Item::END_PORTAL){
                $this->plugin->getServer()->dispatchCommand($player, "f home");
            }
        }
	
}
