<?php

namespace Bluplayz;

use pocketmine\block\Block;
use pocketmine\Command\Command;
use pocketmine\Command\CommandSender;
use pocketmine\entity\Entity;
use pocketmine\entity\Villager;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\inventory\ChestInventory;
use pocketmine\item\Item;
use pocketmine\level\format\FullChunk;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\PluginTask;
use pocketmine\tile\Chest;
use pocketmine\tile\Sign;
use pocketmine\tile\Tile;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class Bedwars extends PluginBase implements Listener {

    public $prefix = TextFormat::GRAY."[".TextFormat::DARK_AQUA."Bedwars".TextFormat::GRAY."]".TextFormat::WHITE." ";
    public $registerSign = false;
    public $registerSignWHO = "";
    public $registerSignArena = "Arena1";
    public $registerBed = false;
    public $registerBedWHO = "";
    public $registerBedArena = "Arena1";
    public $registerBedTeam = "WHITE";
    public $mode = 0;
    public $arena = "Arena1";
    public $lasthit = array();
    public $pickup = array();
    public $isShopping = array();
    public $breakableblocks = array();

    public function onEnable(){

        //Entity::registerEntity(Villager::class, true);

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getLogger()->info($this->prefix.TextFormat::GREEN."BedWars plugin by NetherTechnology loaded!");
        @mkdir($this->getDataFolder());
        @mkdir($this->getDataFolder()."Arenas");
        @mkdir($this->getDataFolder()."Maps");

        $files = scandir($this->getDataFolder()."Arenas");
        foreach($files as $filename){
            if($filename != "." && $filename != ".."){
                $filename = str_replace(".yml", "", $filename);

                $this->resetArena($filename);

                $this->getServer()->loadLevel($this->getWarteLobby($filename));
            }
        }
        $cfg = new Config($this->getDataFolder()."config.yml", Config::YAML);
        if(empty($cfg->get("LobbyTimer"))){
            $cfg->set("LobbyTimer", 61);
            $cfg->save();
        }
        if(empty($cfg->get("GameTimer"))){
            $cfg->set("GameTimer", 30*60 +1);
            $cfg->save();
        }
        if(empty($cfg->get("EndTimer"))){
            $cfg->set("EndTimer", 16);
            $cfg->save();
        }
        if(empty($cfg->get("BreakableBlocks"))){
            $cfg->set("BreakableBlocks", array(Item::SANDSTONE, Item::GLASS, Item::OBSIDIAN, Item::CAKE, Item::ENCHANTING_TABLE));
            $cfg->save();
        }
        $this->breakableblocks = $cfg->get("BreakableBlocks");
        $shop = new Config($this->getDataFolder()."shop.yml", Config::YAML);

        if ($shop->get("Shop") == null) {
                $shop->set("Shop", array(
                    
                    Item::SANDSTONE,
                    array(
                        array(
                            Item::SANDSTONE, 4, 336, 1
                        ),
                        array(
                            Item::GLASS, 1, 336, 5
                        ),
                        array(
                            Item::OBSIDIAN, 1, 264, 1
                        )
                    ),
					Item::WOODEN_PICKAXE,
                    array(
                        array(
                            Item::WOODEN_PICKAXE, 1, 336, 10
                        ),
                        array(
                            Item::STONE_PICKAXE, 1, 265, 5
                        ),
                        array(
                            Item::IRON_PICKAXE, 1, 266, 10
                        ),
                        array(
                            Item::DIAMOND_PICKAXE, 1, 264, 5
                        )
                    ),
					Item::BREAD,
                    array(
                        array(
                            Item::BREAD, 1, 336, 10
                        ),
                        array(
                            Item::STEAK, 1, 265, 5
                        ),
                        array(
                            Item::CAKE, 1, 266, 10
                        ),
                        array(
                            Item::GOLDEN_APPLE, 1, 264, 5
                        )
                    ),
					Item::ENCHANTING_TABLE,
                    array(
						array(
                            Item::BOOK, 5, 266, 10
                        ),
                        array(
                            Item::ENCHANTING_TABLE, 1, 264, 20
                        )
                    ),
					Item::BOW,
                    array(
                        array(
                            Item::BOW, 1, 264, 1
                        ),
                        array(
                            Item::ARROW, 2, 266, 1
                        )
                    ),
					Item::WOODEN_SWORD,
                    array(
                        array(
                            Item::WOODEN_SWORD, 1, 336, 20
                        ),
                        array(
                            Item::STONE_SWORD, 1, 265, 2
                        ),
                        array(
                            Item::IRON_SWORD, 1, 265, 10
                        ),
                        array(
                            Item::DIAMOND_SWORD, 1, 264, 5
                        )
                    ),
                    Item::IRON_CHESTPLATE,
                    array(
                        array(
                            Item::LEATHER_CAP, 1, 336, 5
                        ),
                        array(
                            Item::LEATHER_PANTS, 1, 336, 10
                        ),
                        array(
                            Item::LEATHER_BOOTS, 1, 336, 5
                        ),
                        array(
                            Item::LEATHER_TUNIC, 1, 336, 10
                        ),
						array(
                            Item::CHAIN_CHESTPLATE, 1, 265, 5
                        ),
						array(
                            Item::IRON_CHESTPLATE, 1, 266, 2
                        ),
                        array(
                            Item::DIAMOND_CHESTPLATE, 1, 264, 5
                        )
                    )
                )
            );
            $shop->save();
        }


        $this->getServer()->getScheduler()->scheduleRepeatingTask(new BWRefreshSigns($this), 20);
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new BWGameSender($this), 20);

    }
    ############################################################################################################
    ############################################################################################################
    ############################################################################################################
    #################################    ===[EIGENE FUNKTIONEN]===     #########################################
    ############################################################################################################
    ############################################################################################################
    ############################################################################################################
    public function getTeams($arena){
        $config = new Config($this->getDataFolder()."Arenas/".$arena.".yml", Config::YAML);
        $array = array();
        foreach($this->getAllTeams() as $team){
            if(!empty($config->getNested("Spawn.".$team))){
                $array[] = $team;
            }
        }

        return $array;
    }
    public function getPlayers($arena){
        $config = new Config($this->getDataFolder()."Arenas/".$arena.".yml", Config::YAML);

        $playersXXX = $config->get("Players");

        $players = array();

        foreach ($playersXXX as $x){
            if($x != "steve steve"){
                $players[] = $x;
            }
        }

        return $players;
    }
    public function getTeam($pn){

        $pn = str_replace("§", "", $pn);
        $pn = str_replace(TextFormat::ESCAPE, "", $pn);
        $color = $pn{0};
        return $this->convertColorToTeam($color);
    }
    public function getAvailableTeams($arena){
        $teams = $this->getTeams($arena);
        $config = new Config($this->getDataFolder()."Arenas/".$arena.".yml", Config::YAML);

        $players = $this->getPlayers($arena);

        $availableTeams = array();

        $ppt = (int) $config->get("PlayersPerTeam");

        $teamcount = 0;
        foreach($teams as $team){

            foreach($players as $pn){
                $p = $this->getServer()->getPlayerExact($pn);
                if($p != null){
                    $pnn = $p->getNameTag();
                    if($this->getTeam($pnn) === $team){
                        $teamcount++;
                    }
                }
            }
            if($teamcount < $ppt){
                $availableTeams[] = $team;
            }
            $teamcount = 0;
        }

        $array = array();
        $teamcount = 0;
        $teamcount2 = 0;
        foreach($availableTeams as $team){

            if(count($array) == 0){
                $array[] = $team;
            } else {
                foreach($players as $pn){
                    $p = $this->getServer()->getPlayerExact($pn);
                    if($p != null){
                        $pnn = $p->getNameTag();
                        if($this->getTeam($pnn) === $team){
                            $teamcount++;
                        }
                    }
                }
                foreach($players as $pn){
                    $p = $this->getServer()->getPlayerExact($pn);
                    if($p != null){
                        $pnn = $p->getNameTag();
                        if($this->getTeam($pnn) === $array[0]){
                            $teamcount2++;
                        }
                    }
                }
                if($teamcount >= $teamcount2){
                    //hinten anhängen
                    array_push($array, $team);
                } else {
                    array_unshift($array, $team);
                }
                $teamcount = 0;
                $teamcount2 = 0;
            }

        }

        return $array;
    }
    public function getAvailableTeam($arena){

        $teams = $this->getAvailableTeams($arena);
        if(isset($teams[0])){
            return $teams[0];
        } else {
            return "WHITE";
        }
    }
    public function getAliveTeams($arena){
        $alive = array();

        $teams = $this->getTeams($arena);
        $players = $this->getPlayers($arena);

        $teamcount = 0;
        foreach($teams as $team){
            foreach($players as $pn){
                $p = $this->getServer()->getPlayerExact($pn);
                if($p != null) {
                    $pnn = $p->getNameTag();
                    if ($this->getTeam($pnn) == $team) {
                        $teamcount++;
                    }
                }
            }
            if($teamcount != 0){
                $alive[] = $team;
            }
            $teamcount = 0;
        }

        return $alive;
    }
    public function convertColorToTeam($color){

        if($color == "9")return "BLUE";
        if($color == "c")return "RED";
        if($color == "a")return "GREEN";
        if($color == "e")return "YELLOW";
        if($color == "5")return "PURPLE";
        if($color == "0")return "BLACK";
        if($color == "7")return "GRAY";
        if($color == "b")return "AQUA";

        return "WHITE";
    }
    public function convertTeamToColor($team){

        if($team == "BLUE")return "9";
        if($team == "RED")return "c";
        if($team == "GREEN")return "a";
        if($team == "YELLOW")return "e";
        if($team == "PURPLE")return "5";
        if($team == "BLACK")return "0";
        if($team == "GRAY")return "7";
        if($team == "AQUA")return "b";

        return "f";
    }
    public function getTeamColor($team){

        if($team == "BLUE")return TextFormat::BLUE;
        if($team == "RED")return TextFormat::RED;
        if($team == "GREEN")return TextFormat::GREEN;
        if($team == "YELLOW")return TextFormat::YELLOW;
        if($team == "PURPLE")return TextFormat::DARK_PURPLE;
        if($team == "BLACK")return TextFormat::BLACK;
        if($team == "GRAY")return TextFormat::GRAY;
        if($team == "AQUA")return TextFormat::AQUA;

        return TextFormat::WHITE;
    }
    public function resetArena($arena, $mapreset = true){
        $config = new Config($this->getDataFolder()."Arenas/".$arena.".yml", Config::YAML);

        $cfg = new Config($this->getDataFolder()."config.yml", Config::YAML);

        if($mapreset === true){
            $this->resetMaps($arena);
        }

        $config->set("LobbyTimer", $cfg->get("LobbyTimer"));
        $config->set("GameTimer", $cfg->get("GameTimer"));
        $config->set("EndTimer", $cfg->get("EndTimer"));
        $config->set("Status", "Lobby");
        $config->set("Players", array("steve steve"));
        $config->save();
        foreach($this->getTeams($arena) as $team){
            $config->setNested("Bed.".$team.".Alive", true);
            $config->save();
        }

        $this->getLogger()->info(TextFormat::GREEN."Arena ".TextFormat::AQUA.$arena.TextFormat::GREEN." Was successfully loaded!");
    }
    public function createArena($arena, $teams, $ppt){
        $config = new Config($this->getDataFolder()."Arenas/".$arena.".yml", Config::YAML);

        $cfg = new Config($this->getDataFolder()."config.yml", Config::YAML);

        $config->set("LobbyTimer", $cfg->get("LobbyTimer"));
        $config->set("GameTimer", $cfg->get("GameTimer"));
        $config->set("EndTimer", $cfg->get("EndTimer"));
        $config->set("Status", "Lobby");
        $config->set("Players", array("steve steve"));
        $config->set("Teams", $teams);
        $config->set("PlayersPerTeam", $ppt);
        $config->save();

        $this->getLogger()->info(TextFormat::GREEN."Arena ".TextFormat::AQUA.$arena.TextFormat::GREEN." Was successfully created!");
    }
    public function resetMaps($arena){
        $levelname = $this->getArenaWorlds($arena);
        if ($this->plugin->getServer()->isLevelLoaded($levelname)) {
                $zip = new \ZipArchive;
                $zip->open($this->plugin->getDataFolder() . 'Maps/' . $levelname . '.zip');
                $zip->extractTo($this->plugin->getServer()->getDataPath() . 'worlds');
                $zip->close();
                unset($zip);
                $this->plugin->getServer()->loadLevel($levelname);
                $this->plugin->getServer()->getLevelByName($levelname)->setAutoSave(false);
            }
    }
    public function getFigthWorld($arena){
        $level = "noWorld";
        $config = new Config($this->getDataFolder()."Arenas/".$arena.".yml", Config::YAML);

        foreach($this->getTeams($arena) as $team){
            $level = $config->getNested("Spawn.".$team.".Welt");
        }

        return $level;
    }
    public function getWarteLobby($arena){
        $levels = array();
        $config = new Config($this->getDataFolder()."Arenas/".$arena.".yml", Config::YAML);
        return $config->getNested("Spawn.Lobby.Welt");
    }
    public function getArenaWorlds($arena){
        $levels = array();
        $config = new Config($this->getDataFolder()."Arenas/".$arena.".yml", Config::YAML);

        foreach($this->getAllTeams() as $team){
            if(!empty($config->getNested("Spawn.".$team.".Welt"))){
                $newlevel = $config->getNested("Spawn.".$team.".Welt");
                if(!in_array($newlevel, $levels)){
                    $levels[] = $newlevel;
                }
            }
        }

        return $levels;
    }
    public function setSpawn($arena, $team, Player $p){
        $config = new Config($this->getDataFolder()."Arenas/".$arena.".yml", Config::YAML);

        $config->setNested("Spawn.".$team.".Welt", $p->getLevel()->getName());
        $config->setNested("Spawn.".$team.".X", $p->getX());
        $config->setNested("Spawn.".$team.".Y", $p->getY());
        $config->setNested("Spawn.".$team.".Z", $p->getZ());
        $config->setNested("Spawn.".$team.".Yaw", $p->getYaw());
        $config->setNested("Spawn.".$team.".Pitch", $p->getPitch());
        $config->save();
    }
    public function setLobby($arena, Player $p){
        $config = new Config($this->getDataFolder()."Arenas/".$arena.".yml", Config::YAML);

        $config->setNested("Spawn.Lobby.Welt", $p->getLevel()->getName());
        $config->setNested("Spawn.Lobby.X", $p->getX());
        $config->setNested("Spawn.Lobby.Y", $p->getY());
        $config->setNested("Spawn.Lobby.Z", $p->getZ());
        $config->setNested("Spawn.Lobby.Yaw", $p->getYaw());
        $config->setNested("Spawn.Lobby.Pitch", $p->getPitch());
        $config->save();
    }
    public function arenaExists($arena){
        $files = scandir($this->getDataFolder()."Arenas");
        foreach($files as $filename){
            if($filename != "." && $filename != ".."){
                $filename = str_replace(".yml", "", $filename);

                if($filename == $arena){
                    return true;
                }
            }
        }
        return false;
    }
    public function TeleportToWaitingLobby($arena, Player $p){

        $p->setHealth(20);
        $p->setFood(20);
        $p->setGamemode(0);
        $p->getInventory()->clearAll();
        $p->setExpLevel(0);

        $config = new Config($this->getDataFolder()."Arenas/".$arena.".yml", Config::YAML);

        $welt = $config->getNested("Spawn.Lobby.Welt");
        $x = $config->getNested("Spawn.Lobby.X");
        $y = $config->getNested("Spawn.Lobby.Y");
        $z = $config->getNested("Spawn.Lobby.Z");
        $yaw = $config->getNested("Spawn.Lobby.Yaw");
        $pitch = $config->getNested("Spawn.Lobby.Pitch");

        $p->teleport($this->getServer()->getLevelByName($welt)->getSafeSpawn(), 0, 0);
        $p->teleport(new Vector3($x, $y, $z), $yaw, $pitch);
    }
    public function getAllTeams(){
        $teams = array(
            "BLUE",//1
            "RED",//2
            "GREEN",//3
            "YELLOW",//4

            "PURPLE",//5
            "BLACK",//6
            "GRAY",//7
            "AQUA"//8
        );
        return $teams;
    }
    public function Debug($debug){
        $this->getLogger()->info($debug);
    }
    public function addPlayerToArena($arena, $name){

        $config = new Config($this->getDataFolder()."Arenas/".$arena.".yml", Config::YAML);

        $players = $this->getPlayers($arena);

        $players[] = $name;

        $config->set("Players", $players);
        $config->save();
        //$this->getLogger()->info("Spieler: ".$name." , wurde in arena -> ".$arena." geschickt");
    }
    public function removePlayerFromArena($arena, $name){
        $config = new Config($this->getDataFolder()."Arenas/".$arena.".yml", Config::YAML);

        $playersXXX = $this->getPlayers($arena);

        $players = array();
        foreach ($playersXXX as $pn){
            if($pn != $name){
                $players[] = $pn;
            }
        }

        $config->set("Players", $players);
        $config->save();
    }
    public function getArena(Player $p){
        $files = scandir($this->getDataFolder()."Arenas");
        foreach($files as $filename){
            if($filename != "." && $filename != ".."){
                $arena = str_replace(".yml", "", $filename);

                $config = new Config($this->getDataFolder()."Arenas/".$arena.".yml", Config::YAML);
                if(in_array($p->getName(), $config->get("Players"))){
                    return $arena;
                }
            }
        }
        return "-";
    }
    public function inArena(Player $p){
        $files = scandir($this->getDataFolder()."Arenas");
        foreach($files as $filename){
            if($filename != "." && $filename != ".."){
                $arena = str_replace(".yml", "", $filename);

                $config = new Config($this->getDataFolder()."Arenas/".$arena.".yml", Config::YAML);
                if(in_array($p->getName(), $config->get("Players"))){
                    return true;
                }
            }
        }
        return false;
    }
    public function TeleportToTeamSpawn(Player $p, $team, $arena){
        $p->setHealth(20);
        $p->setFood(20);
        $p->setGamemode(0);
        $p->getInventory()->clearAll();
        $p->setExpLevel(0);

        $config = new Config($this->getDataFolder()."Arenas/".$arena.".yml", Config::YAML);

        $welt = $config->getNested("Spawn.".$team.".Welt");
        $x = $config->getNested("Spawn.".$team.".X");
        $y = $config->getNested("Spawn.".$team.".Y");
        $z = $config->getNested("Spawn.".$team.".Z");
        $yaw = $config->getNested("Spawn.".$team.".Yaw");
        $pitch = $config->getNested("Spawn.".$team.".Pitch");

        if($p->getLevel() != $this->getServer()->getLevelByName($welt)){
            $p->teleport($this->getServer()->getLevelByName($welt)->getSafeSpawn(), 0, 0);
        }
        $p->teleport(new Vector3($x, $y, $z), $yaw, $pitch);
    }
    public function getTeamByBlockDamage($damage){
        if($damage == 10){
            return "PURPLE";
        }
        if($damage == 9){
            return "AQUA";
        }
        if($damage == 4){
            return "YELLOW";
        }
        if($damage == 5){
            return "GREEN";
        }
        if($damage == 11){
            return "BLUE";
        }
        if($damage == 14){
            return "RED";
        }
        if($damage == 15){
            return "BLACK";
        }
        if($damage == 7){
            return "GRAY";
        }
        return "WHITE";
    }
    public function openShop(Player $player){

        $chestBlock = new \pocketmine\block\Chest();
        $player->getLevel()->setBlock(new Vector3($player->getX(), $player->getY() - 4, $player->getZ()), $chestBlock, true, true);

        $nbt = new CompoundTag("", [
            new ListTag("Items", []),
            new StringTag("id", Tile::CHEST),
            new IntTag("x", $player->getX()),
            new IntTag("y", $player->getY() - 4),
            new IntTag("z", $player->getZ())
        ]);
        $nbt->Items->setTagType(NBT::TAG_Compound);
        $tile = Tile::createTile(Tiles::CHEST, $this->getLevel(), $nbt);
        if($tile instanceof Chest) {

            $config = new Config($this->getDataFolder() . "shop.yml", Config::YAML);
            $all = $config->get("Shop");

            $tile->getInventory()->clearAll();
            for ($i = 0; $i < count($all); $i+=2) {
                $slot = $i / 2;
                $tile->getInventory()->setItem($slot, Item::get($all[$i], 0, 1));
            }
            $tile->getInventory()->setItem($tile->getInventory()->getSize()-1, Item::get(Item::WOOL, 14, 1));
            $player->addWindow($tile->getInventory());
        }
    }
    public function createVillager($x, $y, $z, Level $level){
        $x += 0.5;
        $z += 0.5;

        $nbt = new CompoundTag;
        $nbt->Pos = new ListTag("Pos", [
            new DoubleTag("", $x),
            new DoubleTag("", $y),
            new DoubleTag("", $z)
        ]);

        $nbt->Rotation = new ListTag("Rotation", [
            new FloatTag("", 0),
            new FloatTag("", 0)
        ]);

        $nbt->Health = new ShortTag("Health", 10);
        $nbt->CustomName = new StringTag("CustomName", TextFormat::GOLD."SHOP");
        $nbt->CustomNameVisible = new ByteTag("CustomNameVisible", 1);

        $level->loadChunk($x >> 4, $z >> 4);

        $villager = Entity::createEntity("Villager", $level->getChunk($x >> 4, $y >> 4), $nbt);
        $villager->spawnToAll();
    }
    public function getWoolDamageByTeam($team){
        if($team == "BLUE"){
            return 11;
        }
        if($team == "RED"){
            return 14;
        }
        if($team == "GREEN"){
            return 5;
        }
        if($team == "YELLOW"){
            return 4;
        }
        if($team == "AQUA"){
            return 9;
        }
        if($team == "BLACK"){
            return 15;
        }
        if($team == "PURPLE"){
            return 10;
        }
        if($team == "GRAY"){
            return 7;
        }
        return 0;
    }
    public function setTeamSelectionItems(Player $player, $arena){
        $player->getInventory()->clearAll();

        $player->setNameTag($player->getName());

        $teams = $this->getTeams($arena);

        foreach($teams as $team){
            $teamwool = $this->getWoolDamageByTeam($team);
            $player->getInventory()->addItem(Item::get(Item::WOOL, $teamwool, 1));
        }
    }
    public function getArenaStatus($arena){
        $config = new Config($this->getDataFolder()."Arenas/".$arena.".yml", Config::YAML);
        $status = $config->get("Status");

        return $status;
    }
    public function sendIngameScoreboard(Player $p, $arena){
        $config = new Config($this->getDataFolder()."Arenas/".$arena.".yml", Config::YAML);
        $popup = TextFormat::GRAY." [".TextFormat::GOLD."TeamsAlive".TextFormat::GRAY."]\n";
        $teams = $this->getTeams($arena);

        $teamscount = 0;
        if(count($teams) >= 4){
            foreach($teams as $team) {
                if($teamscount == 4){
                    $popup = $popup."\n";
                }
                if (in_array($team, $this->getAliveTeams($arena))) {
                    $popup = $popup . " " . $this->getTeamColor($team) . $team . TextFormat::GRAY . " [" . TextFormat::GREEN . "+" . TextFormat::GRAY . "]";
                } else {
                    $popup = $popup . " " . $this->getTeamColor($team) . $team . TextFormat::GRAY . " [" . TextFormat::RED . "-" . TextFormat::GRAY . "]";
                }

                $teamscount++;
            }

        } else {
            foreach($teams as $team) {
                if (in_array($team, $this->getAliveTeams($arena))) {
                    $popup = $popup . " " . $this->getTeamColor($team) . $team . TextFormat::GRAY . " [" . TextFormat::GREEN . "x" . TextFormat::GRAY . "]";
                } else {
                    $popup = $popup . " " . $this->getTeamColor($team) . $team . TextFormat::GRAY . " [" . TextFormat::RED . "x" . TextFormat::GRAY . "]";
                }
            }
        }
        $p->sendPopup($popup);
    }
    ############################################################################################################
    ############################################################################################################
    ############################################################################################################
    ###################################    ===[EVENTS]===     ##################################################
    ############################################################################################################
    ############################################################################################################
    ############################################################################################################

    public function onTransaction(InventoryTransactionEvent $event)
    {
        $trans = $event->getTransaction()->getTransactions();
        $inv = $event->getTransaction()->getInventories();

        $player = null;
        $chestBlock = null;

        foreach ($trans as $t) {
            foreach ($inv as $inventory) {
                $chest = $inventory->getHolder();

                if ($chest instanceof Chest) {
                    $chestBlock = $chest->getBlock();
                    $transaction = $t;
                }
                if ($chest instanceof Player) {
                    $player = $chest;
                }
            }
        }
        if ($player != null && $chestBlock != null && isset($transaction)) {

            if($this->inArena($player)) {

                $config = new Config($this->getDataFolder() . "shop.yml", Config::YAML);
                $all = $config->get("Shop");

                /*
                if(in_array($transaction->getTargetItem()->getId(), $all)){
                    $this->isShopping[$player->getName()] = "ja";
                }
                */

                $arena = $this->getArena($player);

                $chestTile = $player->getLevel()->getTile($chestBlock);
                if ($chestTile instanceof Chest) {
                    $TargetItemID = $transaction->getTargetItem()->getId();
                    $TargetItemDamage = $transaction->getTargetItem()->getDamage();
                    $TargetItem = $transaction->getTargetItem();
                    $inventoryTrans = $chestTile->getInventory();


                    if($this->isShopping[$player->getName()] != "ja") {
                        $zahl = 0;
                        for ($i = 0; $i < count($all); $i += 2) {
                            if ($TargetItemID == $all[$i]) {
                                $zahl++;
                            }
                        }
                        if($zahl == count($all)){
                            $this->isShopping[$player->getName()] = "ja";
                        }
                    }
                    if($this->isShopping[$player->getName()] != "ja") {
                        $secondslot = $inventoryTrans->getItem(1)->getId();
                        if ($secondslot == 384) {
                            $this->isShopping[$player->getName()] = "ja";
                        }
                    }

                    if($this->isShopping[$player->getName()] == "ja"){
                        if ($TargetItemID == Item::WOOL && $TargetItemDamage == 14) {
                            $event->setCancelled(true);
                            $config = new Config($this->getDataFolder() . "shop.yml", Config::YAML);
                            $all = $config->get("Shop");
                            $chestTile->getInventory()->clearAll();
                            for ($i = 0; $i < count($all); $i = $i + 2) {
                                $slot = $i / 2;
                                $chestTile->getInventory()->setItem($slot, Item::get($all[$i], 0, 1));
                            }
                        }

                        $TransactionSlot = 0;
                        for ($i = 0; $i < $inventoryTrans->getSize(); $i++) {
                            if ($inventoryTrans->getItem($i)->getId() == $TargetItemID) {
                                $TransactionSlot = $i;
                                break;
                            }
                        }
                        $is = $inventoryTrans->getItem(1)->getId();
                        if ($TransactionSlot % 2 != 0 && ($is == 264 or $is == 265 or $is == 266 or $is == 336)) {
                            $event->setCancelled(true);
                        }
                        if ($TargetItemID == 267 or $TargetItemID == 266 or $TargetItemID == 265 or $TargetItemID == 336) {
                            $event->setCancelled(true);
                        }
                        if ($TransactionSlot % 2 == 0 && ($is == 264 or $is == 265 or $is == 266 or $is == 336)) {
                            $Kosten = $inventoryTrans->getItem($TransactionSlot + 1)->getCount();

                            //$yourmoney = $player->getExpLevel();

                            //if ($yourmoney >= $Kosten) {
                                //$money = $yourmoney - $Kosten;
                                //$player->setExpLevel($money);
                                $player->getInventory()->addItem(Item::get($inventoryTrans->getItem($TransactionSlot)->getId(), $inventoryTrans->getItem($TransactionSlot)->getDamage(), $inventoryTrans->getItem($TransactionSlot)->getCount()));
                            //}
                           // $event->setCancelled(true);
                        } 
                        if ($secondslot != 264 or $secondslot !=265 or $secondslot !=266 or $secondslot !=336) {
                            $event->setCancelled(true);
                            $config = new Config($this->getDataFolder() . "shop.yml", Config::YAML);
                            $all = $config->get("Shop");
                            for ($i = 0; $i < count($all); $i += 2) {
                                if ($TargetItemID == $all[$i]) {
                                    $chestTile->getInventory()->clearAll();
                                    $suball = $all[$i + 1];
                                    $slot = 0;
                                    for ($j = 0; $j < count($suball); $j++) {
                                        $chestTile->getInventory()->setItem($slot, Item::get($suball[$j][0], 0, $suball[$j][1]));
                                        $slot++;
                                        $chestTile->getInventory()->setItem($slot, Item::get($suball[$j][2], 0, $suball[$j][3]));
                                        $slot++;
                                    }
                                    break;
                                }
                            }
                            $chestTile->getInventory()->setItem($chestTile->getInventory()->getSize() - 1, Item::get(Item::WOOL, 14, 1));
                        }
                    }
                }
            }
        }
    }
    public function onItemDrop(PlayerDropItemEvent $event){
        $player = $event->getPlayer();
        $name = $player->getName();
        $item = $event->getItem();

        if($item->getId() == Item::WOOL){
            if($this->inArena($player)){
                $arena = $this->getArena($player);
                $team = $this->getTeamByBlockDamage($item->getDamage());
                $event->setCancelled();

                if($this->getArenaStatus($arena) == "Lobby") {
                    if($team != $this->getTeam($player->getNameTag())){
                        if (in_array($team, $this->getAvailableTeams($arena))) {
                            $player->setNameTag($this->getTeamColor($team) . $name);
                            $player->sendMessage($this->prefix . "You are now in team " . TextFormat::GOLD . $team);
                            $player->getInventory()->removeItem($item);
                            $player->getInventory()->addItem($item);
                        } else {
                            $player->sendMessage($this->prefix . "The team " . TextFormat::GOLD . $team . TextFormat::WHITE . " Is already full!");
                            $player->getInventory()->removeItem($item);
                            $player->getInventory()->addItem($item);
                        }
                    } else {
                        $player->sendMessage($this->prefix . "You're already in the team " . TextFormat::GOLD . $team);
                        $player->getInventory()->removeItem($item);
                        $player->getInventory()->addItem($item);
                    }
                }
            }
        }
    }
    public function onChat(PlayerChatEvent $event){
        $player = $event->getPlayer();
        $name = $player->getName();

        if($this->inArena($player)) {
            $arena = $this->getArena($player);
            $config = new Config($this->getDataFolder()."Arenas/".$arena.".yml", Config::YAML);
            $team = $this->getTeam($player->getNameTag());
            $players = $this->getPlayers($arena);
            $status = $config->get("Status");
            $msg = $event->getMessage();
            $words = explode(" ", $msg);

            if($status == "Lobby"){
                $event->setCancelled();
                foreach($players as $pn){
                    $p = $this->getServer()->getPlayerExact($pn);
                    if($p != null){
                        $p->sendMessage($name." >> ".$msg);
                    }
                }
            } else {
                if ($words[0] === "@a" or $words[0] === "@all") {
                    array_shift($words);
                    $msg = implode(" ", $words);
                    $event->setCancelled();
                    foreach ($players as $pn) {
                        $p = $this->getServer()->getPlayerExact($pn);
                        if ($p != null) {
                            $p->sendMessage(TextFormat::GRAY . "[" . TextFormat::GREEN . "ALL" . TextFormat::GRAY . "] " . $player->getNameTag() . TextFormat::GRAY . " >> " . TextFormat::WHITE . $msg);
                        }
                    }
                } else {
                    $event->setCancelled();
                    foreach ($players as $pn) {
                        $p = $this->getServer()->getPlayerExact($pn);
                        if ($p != null) {
                            if ($this->getTeam($p->getNameTag()) == $this->getTeam($player->getNameTag())) {
                                //teamchat
                                $p->sendMessage(TextFormat::GRAY . "[" . $this->getTeamColor($this->getTeam($player->getNameTag())) . "Team" . TextFormat::GRAY . "] " . $player->getNameTag() . TextFormat::GRAY . " >> " . TextFormat::WHITE . $msg);
                            }
                        }
                    }
                }
            }
        }
    }
    public function onInvClose(InventoryCloseEvent $event){
        $inventory = $event->getInventory();
        if ($inventory instanceof ChestInventory) {
            $config = new Config($this->getDataFolder() . "shop.yml", Config::YAML);
            $all = $config->get("Shop");
            $realChest = $inventory->getHolder();
            $first = $all[0];
            $second = $all[2];
            if (($inventory->getItem(0)->getId() == $first && $inventory->getItem(1)->getId() == $second) || $inventory->getItem(1)->getId() == 384) {
                $event->getPlayer()->getLevel()->setBlock(new Vector3($realChest->getX(), $realChest->getY(), $realChest->getZ()), Block::get(Block::AIR));
                $this->isShopping[$event->getPlayer()->getName()] = "nein";
            }
        }
    }
    public function onJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        $this->lasthit[$player->getName()] = "no";
        $this->isShopping[$player->getName()] = "nein";
        $player->setNameTag($player->getName());
    }
    public function onRespawn(PlayerRespawnEvent $event){
        $player = $event->getPlayer();
        $name = $player->getName();

        if($this->inArena($player)){
            $arena = $this->getArena($player);

            $config = new Config($this->getDataFolder()."Arenas/".$arena.".yml", Config::YAML);
            $team = $this->getTeam($player->getNameTag());

            if($config->getNested("Bed.".$team.".Alive") == true){

                $welt = $config->getNested("Spawn.".$team.".Welt");
                $x = $config->getNested("Spawn.".$team.".X");
                $y = $config->getNested("Spawn.".$team.".Y");
                $z = $config->getNested("Spawn.".$team.".Z");

                $level = $this->getServer()->getLevelByName($welt);

                $event->setRespawnPosition(new Position($x, $y, $z, $level));
            } else {
                $event->setRespawnPosition($this->getServer()->getDefaultLevel()->getSafeSpawn());
                $player->sendMessage($this->prefix.TextFormat::RED."Your bed has been destroyed, you can no longer respawn!");
                $this->removePlayerFromArena($arena, $name);
                $this->lasthit[$player->getName()] = "no";
                $player->setNameTag($player->getName());
            }

        }
    }
    public function onPickup(InventoryPickupItemEvent $event){
        $player = $event->getInventory()->getHolder();

        if($player instanceof Player){
            if($this->inArena($player)){

                if(!in_array($event->getItem()->getId(), $this->pickup)) {
                    if ($event->getItem()->getItem()->getId() == Item::EMERALD) {

                        $event->setCancelled();

                        $player->getLevel()->removeEntity($event->getItem());
                        $this->pickup[] = $event->getItem()->getId();
                        $player->setExpLevel($player->getExpLevel() + 1);
                        $player->sendTip(TextFormat::GOLD . "+" . TextFormat::GREEN . "1 Level!");
                    }
                }
            }
        }
    }
    public function onDeath(PlayerDeathEvent $event){
        $player = $event->getEntity();
        if($player instanceof Player){
            if($this->inArena($player)){
                $event->setDeathMessage("");
                $arena = $this->getArena($player);
                $cause = $player->getLastDamageCause();
                $players = $this->getPlayers($arena);

                if ($cause instanceof EntityDamageByEntityEvent) {
                    $killer = $cause->getDamager();
                    $event->setDrops(array());
                    if ($killer instanceof Player) {
                        foreach ($players as $pn) {
                            $p = $this->getServer()->getPlayerExact($pn);
                            if($p != null) {
                                $p->sendMessage($this->prefix . $killer->getNameTag() . TextFormat::GRAY. " kill " . $player->getNameTag() . TextFormat::GRAY . " ! ");
                            }
                        }
                    } else {
                        foreach ($players as $pn) {
                            $p = $this->getServer()->getPlayerExact($pn);
                            if($p != null) {
                                $p->sendMessage($this->prefix . $player->getNameTag() . TextFormat::GRAY . " died!");
                            }
                        }
                    }
                } else {
                    $event->setDrops(array());
                    foreach ($players as $pn) {
                        $p = $this->getServer()->getPlayerExact($pn);
                        if($p != null) {

                            if($this->lasthit[$player->getName()] != "no"){
                                $p2 = $this->getServer()->getPlayerExact($this->lasthit[$player->getName()]);
                                if($p2 != null){
                                    $p->sendMessage($this->prefix . $p2->getNameTag() . TextFormat::WHITE. " Killed " . $player->getNameTag() . TextFormat::WHITE . "!");
                                    $this->lasthit[$player->getName()] = "no";
                                } else {
                                    $p->sendMessage($this->prefix . $player->getNameTag() . TextFormat::GRAY . " died!");
                                }
                            } else {
                                $p->sendMessage($this->prefix . $player->getNameTag() . TextFormat::GRAY . " died!");
                            }
                        }
                    }
                }
            }
        }
    }
    
    public function onHit(EntityDamageEvent $event){
        $player = $event->getEntity();

        if (!$player instanceof Player) {
            if ($event instanceof EntityDamageByEntityEvent) {
                $damager = $event->getDamager();
                if($damager instanceof Player) {
                    if($this->inArena($damager)) {
                        $event->setCancelled();
                        $this->isShopping[$damager->getName()] = "ja";
                        $this->openShop($damager);
                    }
                }
            }
        } else {
            if($this->inArena($player)) {
                $arena = $this->getArena($player);

                $config = new Config($this->getDataFolder()."Arenas/".$arena.".yml", Config::YAML);

                if($config->get("Status") == "Lobby"){
                    $event->setCancelled();
                }
            }
            if ($event instanceof EntityDamageByEntityEvent) {
                $damager = $event->getDamager();
                if($damager instanceof Player){
                    if($this->inArena($player)) {
                        $arena = $this->getArena($player);

                        $config = new Config($this->getDataFolder()."Arenas/".$arena.".yml", Config::YAML);

                        if($config->get("Status") == "Lobby"){
                            $event->setCancelled();
                        } else {
                            if($this->getTeam($damager->getNameTag()) == $this->getTeam($player->getNameTag())){
                                $event->setCancelled();
                                $damager->sendMessage($this->prefix.TextFormat::RED."This player is in your team!");
                            } else {
                                $this->lasthit[$player->getName()] = $damager->getName();
                            }
                        }
                    }
                }
            }
        }
    }
    public function onMove(PlayerMoveEvent $event){
        $player = $event->getPlayer();
        if($this->inArena($player)){
            $arena = $this->getArena($player);
            $cause = $player->getLastDamageCause();
            $players = $this->getPlayers($arena);

            if($player->getY() <= 4){
                $player->setHealth(0);
            }

        }
    }


    public function onPlace(BlockPlaceEvent $event){
        $player = $event->getPlayer();
        $name = $player->getName();
        $block = $event->getBlock();
        if($this->inArena($player)) {

            $arena = $this->getArena($player);

            $config = new Config($this->getDataFolder() . "Arenas/" . $arena . ".yml", Config::YAML);

            if($config->get("Status") == "Lobby"){
                $event->setCancelled();

                if($block->getId() == Block::WOOL){
                    $item = Item::get($block->getId(), $block->getDamage(), 1);

                    $arena = $this->getArena($player);
                    $team = $this->getTeamByBlockDamage($block->getDamage());
                    $event->setCancelled();
                    if($team != $this->getTeam($player->getNameTag())){
                        if (in_array($team, $this->getAvailableTeams($arena))) {
                            $player->setNameTag($this->getTeamColor($team) . $name);
                            $player->sendMessage($this->prefix . "You are now in team " . TextFormat::GOLD . $team);

                            $player->getInventory()->removeItem($item);
                            $player->getInventory()->addItem($item);
                        } else {
                            $player->sendMessage($this->prefix . "The team " . TextFormat::GOLD . $team . TextFormat::WHITE . " Is already full!");
                            $player->getInventory()->removeItem($item);
                            $player->getInventory()->addItem($item);
                        }
                    } else {
                        $player->sendMessage($this->prefix . "You're already in the team " . TextFormat::GOLD . $team);
                        $player->getInventory()->removeItem($item);
                        $player->getInventory()->addItem($item);
                    }
                }
            } else {
                if (!in_array($block->getId(), $this->breakableblocks)) {
                    $event->setCancelled();
                }
            }
        }
    }
    public function onBreak(BlockBreakEvent $event){
        $player = $event->getPlayer();
        $name = $player->getName();

        $block = $event->getBlock();
        $block2 = $player->getLevel()->getBlock(new Vector3($block->getX(), $block->getY() - 1, $block->getZ()), false);

        if($this->inArena($player)) {

            $arena = $this->getArena($player);

            $config = new Config($this->getDataFolder() . "Arenas/" . $arena . ".yml", Config::YAML);

            $team = $this->getTeamByBlockDamage($block2->getDamage());

            if($config->get("Status") != "Lobby"){

                if($block->getId() == Block::BED_BLOCK) {

                    if ($team != $this->getTeam($player->getNameTag())) {
                        $config->setNested("Bed." . $team . ".Alive", false);
                        $config->save();
                        $event->setDrops(array());

                        $player->sendMessage($this->prefix . "You got the bed of team " . $team . " destroyed!");

                        foreach ($this->getPlayers($arena) as $pn) {
                            $p = $this->getServer()->getPlayerExact($pn);
                            if ($p != null) {
                                if ($team == $this->getTeam($p->getNameTag())) {
                                    $p->sendMessage($this->prefix . TextFormat::RED . "Your team's bed has been destroyed!");
                                } else {
                                    $p->sendMessage($this->prefix . "The bed of team " . TextFormat::GOLD . $team . TextFormat::WHITE . " was destroyed!");
                                }

                            }
                        }
                    } else {
                        $player->sendMessage($this->prefix . "You can not destroy your own bed!");
                        $event->setCancelled();
                    }
                }
                elseif(!in_array($block->getId(), $this->breakableblocks)){
                    $event->setCancelled();
                }
            } else {
                $event->setCancelled();
            }



        }
    }
    public function onInteract(PlayerInteractEvent $event){
        $player = $event->getPlayer();
        $name = $player->getName();
        $block = $event->getBlock();
        $tile = $player->getLevel()->getTile($block);

        if($this->registerBed == true && $this->registerBedWHO == $name){

            $arena = $this->registerBedArena;
            $team = $this->registerBedTeam;

            $this->registerBed = false;

            $config = new Config($this->getDataFolder()."Arenas/".$arena.".yml", Config::YAML);

            $config->setNested("Bed.".$team.".Welt", $block->getLevel()->getName());
            $config->setNested("Bed.".$team.".X", $block->getX());
            $config->setNested("Bed.".$team.".Y", $block->getY());
            $config->setNested("Bed.".$team.".Z", $block->getZ());
            $config->setNested("Bed.".$team.".Alive", true);

            $config->save();

            $player->sendMessage(TextFormat::GREEN . "You have a successful team " . TextFormat::AQUA . $team . TextFormat::GREEN . " For the arena " . TextFormat::AQUA . $arena . TextFormat::GREEN . " registered!");
            $player->sendMessage(TextFormat::GREEN . "Setup -> /bw help");
        }

        if($tile instanceof Sign){
            $text = $tile->getText();


            if($this->registerSign == true && $this->registerSignWHO == $name){

                $arena = $this->registerSignArena;

                $config = new Config($this->getDataFolder()."Arenas/".$arena.".yml", Config::YAML);

                $teams = (int) $config->get("Teams");
                $ppt = (int) $config->get("PlayersPerTeam");

                $maxplayers = $teams * $ppt;


                $tile->setText($this->prefix, $arena." ".$teams."x".$ppt, TextFormat::GREEN."Loading...", TextFormat::YELLOW."0 / ".$maxplayers);
                $this->registerSign = false;

                $player->sendMessage(TextFormat::GREEN . "You have a successful shield for the arena " . TextFormat::AQUA . $arena . TextFormat::GREEN . " registered!");
                $player->sendMessage(TextFormat::GREEN . "Setup -> /bw help");
            }
            elseif($text[0] == $this->prefix){

                if($text[2] == TextFormat::GREEN."Betreten"){

                    $arena = substr($text[1], 0, -4);
                    $config = new Config($this->getDataFolder()."Arenas/".$arena.".yml", Config::YAML);
                    $status = $config->get("Status");
                    $maxplayers = $config->get("PlayersPerTeam") * $config->get("Teams");
                    $players = count($config->get("Players"));

                    if($status == "Lobby"){
                        if($players < $maxplayers) {
                            $this->TeleportToWaitingLobby($arena, $player);
                            $this->setTeamSelectionItems($player, $arena);
                            $this->addPlayerToArena($arena, $name);
                        } else {
                            $player->sendMessage($this->prefix . TextFormat::RED . "You can not enter this match!");
                        }
                    } else {
                        $player->sendMessage($this->prefix.TextFormat::RED."You can not enter this match!");
                    }
                } else {
                    $player->sendMessage($this->prefix.TextFormat::RED."You can not enter this match!");
                }

            }
        }

    }
    ############################################################################################################
    ############################################################################################################
    ############################################################################################################
    ###################################    ===[COMMANDS]===     ################################################
    ############################################################################################################
    ############################################################################################################
    ############################################################################################################

    public function onCommand(CommandSender $sender, Command $cmd, $label, array $args){

        $name = $sender->getName();
        if($cmd->getName() == "Start" && $sender->hasPermission("bw.forcestart")){
            if($sender instanceof Player){
                if($this->inArena($sender)){
                    $arena = $this->getArena($sender);

                    $config = new Config($this->getDataFolder()."Arenas/".$arena.".yml", Config::YAML);

                    $config->set("LobbyTimer", 5);
                    $config->save();
                } else {
                    $sender->sendMessage(TextFormat::RED."You're not in an arena!");
                }
            }
        }
        if($cmd->getName() == "Bedwars" && $sender->isOP()){
            if(!empty($args[0])){
                if(strtolower($args[0]) == "help" && $sender->isOP()){
                    $sender->sendMessage(TextFormat::GRAY."===============");
                    $sender->sendMessage(TextFormat::GRAY."-> ".TextFormat::DARK_AQUA."/bw help ".TextFormat::GRAY."[".TextFormat::RED."Displays all Bedwars Commands".TextFormat::GRAY."]");
                    $sender->sendMessage(TextFormat::GRAY."-> ".TextFormat::DARK_AQUA."/bw regsign <Arena> ".TextFormat::GRAY."[".TextFormat::RED."Register a sign for a arena".TextFormat::GRAY."]");
                    $sender->sendMessage(TextFormat::GRAY."-> ".TextFormat::DARK_AQUA."/bw addarena <ArenaName> <Teams> <SpielerProTeam> ".TextFormat::GRAY."[".TextFormat::RED."Add a new arena".TextFormat::GRAY."]");
                    $sender->sendMessage(TextFormat::GRAY."-> ".TextFormat::DARK_AQUA."/bw setlobby <Arena>".TextFormat::GRAY."[".TextFormat::RED."Set The Arena lobby".TextFormat::GRAY."]");
                    $sender->sendMessage(TextFormat::GRAY."-> ".TextFormat::DARK_AQUA."/bw setspawn <Arena> <Team>".TextFormat::GRAY."[".TextFormat::RED."Sets the team spawns".TextFormat::GRAY."]");
                    $sender->sendMessage(TextFormat::GRAY."-> ".TextFormat::DARK_AQUA."/bw setbed <Arena> <Team>".TextFormat::GRAY."[".TextFormat::RED."Sets the team beds".TextFormat::GRAY."]");
					$sender->sendMessage(TextFormat::GRAY."-> ".TextFormat::DARK_AQUA."/bw shop ".TextFormat::GRAY."[".TextFormat::RED."Spawn the Villager Shop".TextFormat::GRAY."]");
                    $sender->sendMessage(TextFormat::GRAY."===============");
                }
                elseif(strtolower($args[0]) == "regsign" && $sender->isOP()){
                    if(!empty($args[1])) {
                        $arena = $args[1];
                        if($this->arenaExists($arena)) {
                            $this->registerSign = true;
                            $this->registerSignWHO = $name;
                            $this->registerSignArena = $arena;
                            $sender->sendMessage(TextFormat::GREEN . "Now type a sign!");
                        } else {
                            $sender->sendMessage(TextFormat::RED."Arena does not exist!");
                        }
                    } else {
                        $sender->sendMessage(TextFormat::RED."/bw regsign <ArenaName>");
                    }
                }
                elseif(strtolower($args[0]) == "addarena" && $sender->isOP()){
                    if(!empty($args[1]) && !empty($args[2]) && !empty($args[3])) {
                        $arena = $args[1];
                        $teams = (int)$args[2];
                        $ppt = (int)$args[3]; //ppt = PlayersPerTeam

                        if($teams <= 8){
                            $this->createArena($arena, $teams, $ppt);
                            $this->arena = $arena;
                            $sender->sendMessage(TextFormat::GREEN . "You have the arena successfully " . TextFormat::AQUA . $arena . TextFormat::GREEN . "!");
                            $sender->sendMessage(TextFormat::GREEN . "Setup -> /bw help");
                        } else {
                            $sender->sendMessage(TextFormat::RED."You can bet a maximum of 8 teams!");
                        }
                    } else {
                        $sender->sendMessage(TextFormat::RED."/bw addarena <ArenaName> <Teams> <SpielerProTeam>");
                    }
                }
                elseif(strtolower($args[0]) == "setlobby" && $sender->isOP()){
                    if(!empty($args[1])) {
                        $arena = $args[1];
                        if($this->arenaExists($arena)) {

                            $this->setLobby($arena, $sender);

                            $sender->sendMessage(TextFormat::GREEN . "You have successfully the lobby for the arena " . TextFormat::AQUA . $arena . TextFormat::GREEN . " !");
                            $sender->sendMessage(TextFormat::GREEN . "Setup -> /bw help");

                        } else {
                            $sender->sendMessage(TextFormat::RED."Arena does not exist!");
                        }
                    } else {
                        $sender->sendMessage(TextFormat::RED."/bw setlobby <ArenaName>");
                    }
                }
                elseif(strtolower($args[0]) == "setbed" && $sender->isOP()){
                    if(!empty($args[1]) && !empty($args[2])) {
                        $arena = $args[1];
                        $team = $args[2];
                        if($this->arenaExists($arena)) {
                            if (in_array($team, $this->getAllTeams())) {

                                $this->registerBed = true;
                                $this->registerBedWHO = $name;
                                $this->registerBedArena = $arena;
                                $this->registerBedTeam = $team;
                                $sender->sendMessage(TextFormat::GREEN . "Now type in a bed! Please type the lower half as otherwise bugs can come!");

                                $this->resetArena($arena);
                            } else {
                                $alleteams = implode(" ", $this->getAllTeams());

                                $sender->sendMessage(TextFormat::RED . "The team " . TextFormat::GOLD . $team . TextFormat::RED . " does not exist!");
                                $sender->sendMessage(TextFormat::RED . "Teams: " . $alleteams);
                            }
                        } else {
                            $sender->sendMessage(TextFormat::RED."Arena does not exist!");
                        }
                    } else {
                        $sender->sendMessage(TextFormat::RED."/bw setbed <ArenaName> <Team>");
                    }
                }
				elseif(strtolower($args[0]) == "shop" && $sender->isOP()){
					$g = $sender;
					$this->CreateShop($g->x, $g->y, $g->z, $g->yaw, $g->pitch, $g->getLevel(), 1);
				}
                elseif(strtolower($args[0]) == "setspawn" && $sender->isOP()){
                    if(!empty($args[1]) && !empty($args[2])) {
                        $arena = $args[1];
                        $team = $args[2];
                        if($this->arenaExists($arena)) {
                            if (in_array($team, $this->getAllTeams())) {

                                $this->setSpawn($arena, $team, $sender);

                                $sender->sendMessage(TextFormat::GREEN . "You have the spawn of the team ".TextFormat::AQUA . $team . TextFormat::GREEN." for the Arena " . TextFormat::AQUA . $arena . TextFormat::GREEN . " gesetzt!");
                                $sender->sendMessage(TextFormat::GREEN . "Setup -> /bw help");

                                $this->resetArena($arena);
                            } else {
                                $alleteams = implode(" ", $this->getAllTeams());

                                $sender->sendMessage(TextFormat::RED . "The team " . TextFormat::GOLD . $team . TextFormat::RED . " does not exist!");
                                $sender->sendMessage(TextFormat::RED . "Teams: " . $alleteams);
                            }
                        } else {
                            $sender->sendMessage(TextFormat::RED."Arena does not exist!");
                        }
                    } else {
                        $sender->sendMessage(TextFormat::RED."/bw setspawn <ArenaName> <Team>");
                    }
                }
                elseif(strtolower($args[0]) == "test" && $sender->isOP()){
                    $this->createVillager($sender->getX(), $sender->getY(), $sender->getZ(), $sender->getLevel());
                } else {
                    $this->getServer()->dispatchCommand($sender, "bw help");
                }
            } else {
                $this->getServer()->dispatchCommand($sender, "bw help");
            }
        }
    }

	  public function CreateShop($x, $y, $z, $yaw, $pitch, Level $World, $pro){
    $nbt = new CompoundTag;
    $nbt->Pos = new ListTag("Pos", [
      new DoubleTag("", $x),
      new DoubleTag("", $y),
      new DoubleTag("", $z)
    ]);
    $nbt->Rotation = new ListTag("Rotation", [
      new DoubleTag("", $yaw),
      new DoubleTag("", $pitch)
    ]);
    $nbt->Motion = new ListTag("Motion", [
      new DoubleTag("", 0),
      new DoubleTag("", 0)
    ]);
    $nbt->Profession = new ByteTag("Profession", $pro);
    $nbt->Health = new ShortTag("Health", 10);
    $nbt->CustomName = new StringTag("CustomName", "§6BedWars Shop");
    $nbt->CustomNameVisible = new ByteTag("CustomNameVisible", 1);
    $World->loadChunk($x >> 4, $z >> 4);
    $koylu = Entity::createEntity("Villager", $World, $nbt);
    $koylu->setProfession($pro);
    $koylu->spawnToAll();
  }
}
############################################################################################################
############################################################################################################
############################################################################################################
###################################    ===[SCHEDULER]===     ###############################################
############################################################################################################
############################################################################################################
############################################################################################################
class BWRefreshSigns extends PluginTask {

    public $prefix = "";
    public $plugin;

    public function __construct(Bedwars $plugin) {
        $this->plugin = $plugin;
        $this->prefix = $this->plugin->prefix;
        parent::__construct($plugin);
    }

    public function onRun($tick) {
        $levels = $this->plugin->getServer()->getDefaultLevel();
        $tiles = $levels->getTiles();
        foreach ($tiles as $t) {
            if ($t instanceof Sign) {
                $text = $t->getText();
                if ($text[0] == $this->prefix) {
                    $arena = substr($text[1], 0, -4);
                    $config = new Config($this->plugin->getDataFolder()."Arenas/".$arena.".yml", Config::YAML);
                    $players = $this->plugin->getPlayers($arena);
                    $status = $config->get("Status");

                    $welt = $this->plugin->getArenaWorlds($arena)[0];
                    $level = $this->plugin->getServer()->getLevelByName($welt);

                    $arenasign = $text[1];

                    $teams = (int) $config->get("Teams");
                    $ppt = (int) $config->get("PlayersPerTeam");

                    $maxplayers = $teams * $ppt;
                    $ingame = TextFormat::GREEN."Betreten";

                    if ($status != "Lobby") {
                        $ingame = TextFormat::RED . "Ingame";
                    }
                    if (count($players) >= $maxplayers) {
                        $ingame = TextFormat::RED . "Voll";
                    }
                    if ($status == "Ende") {
                        $ingame = TextFormat::RED . "Restart";
                    }
                    $t->setText($this->prefix, $arenasign, $ingame, TextFormat::WHITE . (count($players)) . TextFormat::GRAY . " / ". TextFormat::RED . $maxplayers);
                }
            }
        }
    }
}
class BWGameSender extends PluginTask {

    public $prefix = "";
    public $plugin;

    public function __construct(Bedwars $plugin) {
        $this->plugin = $plugin;
        $this->prefix = $plugin->prefix;
        parent::__construct($plugin);
    }

    public function onRun($tick) {

        $files = scandir($this->plugin->getDataFolder()."Arenas");
        foreach($files as $filename){
            if($filename != "." && $filename != ".."){
                $arena = str_replace(".yml", "", $filename);
                $config = new Config($this->plugin->getDataFolder()."Arenas/".$arena.".yml", Config::YAML);
                $cfg = new Config($this->plugin->getDataFolder()."config.yml", Config::YAML);
                $players = $this->plugin->getPlayers($arena);
                $status = $config->get("Status");
                $teams = (int) $config->get("Teams");
                $ppt = (int) $config->get("PlayersPerTeam");
                $lobbytimer = (int) $config->get("LobbyTimer");
                $gametimer = (int) $config->get("GameTimer");
                $endtimer = (int) $config->get("EndTimer");
                $maxplayers = (int) $teams * $ppt;
                $welt = $this->plugin->getFigthWorld($arena);
                $level = $this->plugin->getServer()->getLevelByName($welt);

                $aliveTeams = $this->plugin->getAliveTeams($arena);

                $minplayers = $ppt +1;

                /*
                if((Time() % 20) == 0){
                    $this->plugin->Debug(TextFormat::GREEN."== Players Array ==");
                    var_dump($players);
                    $this->plugin->Debug(TextFormat::GREEN."== Players Array ==");
                }
                */
                if($status == "Lobby"){

                    if(count($players) < $minplayers){

                        if((Time() % 10) == 0){
                            $config->set("LobbyTimer", $cfg->get("LobbyTimer"));
                            $config->set("GameTimer", $cfg->get("GameTimer"));
                            $config->set("EndTimer", $cfg->get("EndTimer"));
                            $config->set("Status", "Lobby");
                            $config->save();
                        }


                        foreach($players as $pn){
                            $p = $this->plugin->getServer()->getPlayerExact($pn);
                            if($p != null) {
                                $p->sendPopup(TextFormat::RED . "Wait for ".TextFormat::GOLD.$minplayers.TextFormat::RED." players");
                            } else {
                                $this->plugin->removePlayerFromArena($arena, $pn);
                            }
                        }

                        if((Time() % 20) == 0){
                            foreach($players as $pn){
                                $p = $this->plugin->getServer()->getPlayerExact($pn);
                                if($p != null) {
                                    $p->sendMessage(TextFormat::GOLD . $minplayers . TextFormat::RED ."Other players are missing");
                                } else {
                                    $this->plugin->removePlayerFromArena($arena, $pn);
                                }
                            }
                        }
                    } else {

                        $lobbytimer--;
                        $config->set("LobbyTimer", $lobbytimer);
                        $config->save();

                        if($lobbytimer == 60 ||
                            $lobbytimer == 45 ||
                            $lobbytimer == 30 ||
                            $lobbytimer == 20 ||
                            $lobbytimer == 10
                        ){
                            foreach($players as $pn){
                                $p = $this->plugin->getServer()->getPlayerExact($pn);
                                if($p != null){
                                    $p->sendMessage($this->prefix."Round starts in ".$lobbytimer." Seconds!");
                                }
                            }
                        }
                        if($lobbytimer >= 1 && $lobbytimer <= 5){
                            foreach($players as $pn){
                                $p = $this->plugin->getServer()->getPlayerExact($pn);
                                if($p != null){
                                    $p->sendPopup(TextFormat::YELLOW."Still ".TextFormat::RED.$lobbytimer);
                                } else {
                                    $this->plugin->removePlayerFromArena($arena, $pn);
                                }
                            }
                        }
                        if($lobbytimer == 0){
                            foreach($players as $pn){
                                $p = $this->plugin->getServer()->getPlayerExact($pn);
                                if($p != null){
                                    if($p->getNameTag() == $p->getName()) {
                                        $AT = $this->plugin->getAvailableTeam($arena);

                                        $p->setNameTag($this->plugin->getTeamColor($AT) . $pn);
                                    }
                                    $this->plugin->TeleportToTeamSpawn($p, $this->plugin->getTeam($p->getNameTag()), $arena);
                                } else {
                                    $this->plugin->removePlayerFromArena($arena, $pn);
                                }
                            }
/*
                            $tiles = $level->getTiles();
                            foreach ($tiles as $tile) {
                                if ($tile instanceof Sign) {
                                    $text = $tile->getText();
                                    if ($text[0] == "SHOP" || $text[1] == "SHOP" || $text[2] == "SHOP" || $text[3] == "SHOP") {
                                        //spawn Villager for Shop
                                        $this->plugin->createVillager($tile->getX(), $tile->getY(), $tile->getZ(), $tile->getLevel());
                                        $tile->getLevel()->setBlock(new Vector3($tile->getX(), $tile->getY(), $tile->getZ()), Block::get(Block::AIR));
                                    }
                                }
                            }
                            */

                            $config->set("Status", "Ingame");
                            $config->save();
                        }
                    }

                }
                elseif ($status == "Ingame"){
                    if(count($aliveTeams) <= 1){
                        if(count($aliveTeams) == 1){
                            $winnerteam = $aliveTeams[0];
                            $this->plugin->getServer()->broadcastMessage($this->prefix."Team ".TextFormat::GOLD.$winnerteam.TextFormat::WHITE." Has The Bedwars round in Arena".TextFormat::GOLD.$arena.TextFormat::WHITE." Won!");
                        }
                        $config->set("Status", "Ende");
                        $config->save();
                    } else {

                        if ((Time() % 1) == 0) {
                            $tiles = $level->getTiles();
                            foreach ($tiles as $tile) {
                                if ($tile instanceof Sign) {
                                    $text = $tile->getText();
                                    if (strtolower($text[0]) == "brick" || strtolower($text[1]) == "brick" || strtolower($text[2]) == "brick" || strtolower($text[3]) == "brick") {
                                        $loc = new Vector3($tile->getX() + 0.5, $tile->getY() + 2, $tile->getZ() + 0.5);
                                        $needDrop = false;
                                        foreach ($players as $pn) {
                                            $p = $this->plugin->getServer()->getPlayerExact($pn);
                                            if($p != null){
                                                $dis = $loc->distance($p);
                                                if ($dis <= 10) {
                                                    $needDrop = true;
                                                }
                                            }
                                        }
                                        if ($needDrop === true) {
                                            $level->dropItem(new Vector3($tile->getX() + 0.5, $tile->getY() + 2, $tile->getZ() + 0.5), Item::get(Item::BRICK, 0, 1));
                                            $level->dropItem(new Vector3($tile->getX() + 0.5, $tile->getY() + 2, $tile->getZ() + 0.5), Item::get(Item::BRICK, 0, 1));
                                        }
                                    }
                                }
                            }
                        }
                        if ((Time() % 8) == 0) {
                            $tiles = $level->getTiles();
                            foreach ($tiles as $tile) {
                                if ($tile instanceof Sign) {
                                    $text = $tile->getText();
                                    if (strtolower($text[0]) == "iron" || strtolower($text[1]) == "iron" || strtolower($text[2]) == "iron" || strtolower($text[3]) == "iron") {
                                        $level->dropItem(new Vector3($tile->getX() + 0.5, $tile->getY() + 2, $tile->getZ() + 0.5), Item::get(Item::IRON_INGOT, 0, 1));
                                    }
                                }
                            }
                        }
						if ((Time() % 60) == 0) {
                            $tiles = $level->getTiles();
                            foreach ($tiles as $tile) {
                                if ($tile instanceof Sign) {
                                    $text = $tile->getText();
                                    if (strtolower($text[0]) == "diamond" || strtolower($text[1]) == "diamond" || strtolower($text[2]) == "diamond" || strtolower($text[3]) == "diamond") {
                                        $level->dropItem(new Vector3($tile->getX() + 0.5, $tile->getY() + 2, $tile->getZ() + 0.5), Item::get(Item::DIAMOND, 0, 1));
                                    }
                                }
                            }
                        }
						if ((Time() % 60) == 0) {
                            $tiles = $level->getTiles();
                            foreach ($tiles as $tile) {
                                if ($tile instanceof Sign) {
                                    $text = $tile->getText();
                                    if (strtolower($text[0]) == "Exp" || strtolower($text[1]) == "Exp" || strtolower($text[2]) == "Exp" || strtolower($text[3]) == "Exp") {
                                        $level->dropItem(new Vector3($tile->getX() + 0.5, $tile->getY() + 2, $tile->getZ() + 0.5), Item::get(Item::EMERALD, 0, 1));
                                    }
                                }
                            }
                        }
                        if ((Time() % 30) == 0) {
                            $tiles = $level->getTiles();
                            foreach ($tiles as $tile) {
                                if ($tile instanceof Sign) {
                                    $text = $tile->getText();
                                    if (strtolower($text[0]) == "gold" || strtolower($text[1]) == "gold" || strtolower($text[2]) == "gold" || strtolower($text[3]) == "gold") {
                                        $level->dropItem(new Vector3($tile->getX() + 0.5, $tile->getY() + 2, $tile->getZ() + 0.5), Item::get(Item::GOLD_INGOT, 0, 1));
                                    }
                                }
                            }
                        }


                        foreach($players as $pn){
                            $p = $this->plugin->getServer()->getPlayerExact($pn);
                            if($p != null){
                                $this->plugin->sendIngameScoreboard($p, $arena);
                            } else {
                                $this->plugin->removePlayerFromArena($arena, $pn);
                            }
                        }

                        $gametimer--;
                        $config->set("GameTimer", $gametimer);
                        $config->save();

                        if($gametimer==900||$gametimer==600|| $gametimer==300|| $gametimer==240 || $gametimer==180){
                            foreach($players as $pn){
                                $p = $this->plugin->getServer()->getPlayerExact($pn);
                                if($p != null){
                                    $p->sendMessage($this->plugin->prefix.$gametimer/60 . " Minutes left");
                                } else {
                                    $this->plugin->removePlayerFromArena($arena, $pn);
                                }
                            }
                        }
                        elseif($gametimer == 2||$gametimer == 3|| $gametimer==4||$gametimer==5||$gametimer==15||$gametimer==30||$gametimer==60){
                            foreach($players as $pn){
                                $p = $this->plugin->getServer()->getPlayerExact($pn);
                                if($p != null){
                                    $p->sendMessage($this->plugin->prefix.$gametimer . " seconds left");
                                } else {
                                    $this->plugin->removePlayerFromArena($arena, $pn);
                                }
                            }
                        }
                        elseif($gametimer == 1){
                            foreach($players as $pn){
                                $p = $this->plugin->getServer()->getPlayerExact($pn);
                                if($p != null){
                                    $p->sendMessage($this->plugin->prefix."1 second left");
                                } else {
                                    $this->plugin->removePlayerFromArena($arena, $pn);
                                }
                            }
                        }
                        elseif($gametimer==0){
                            foreach($players as $pn){
                                $p = $this->plugin->getServer()->getPlayerExact($pn);
                                if($p != null){
                                    $p->sendMessage($this->plugin->prefix."Deathmatch started!");

                                    $p->sendMessage($this->plugin->prefix."There was no winner!");
                                    $config->set($arena."Status", "Ende");
                                    $config->save();
                                } else {
                                    $this->plugin->removePlayerFromArena($arena, $pn);
                                }
                            }
                        }
                    }
                }
                elseif($status == "Ende"){

                    if($endtimer >= 0){
                        $endtimer--;
                        $config->set("EndTimer", $endtimer);
                        $config->save();

                        if($endtimer == 15 ||
                            $endtimer == 10 ||
                            $endtimer == 5 ||
                            $endtimer == 4 ||
                            $endtimer == 3 ||
                            $endtimer == 2 ||
                            $endtimer == 1){

                            foreach($players as $pn){
                                $p = $this->plugin->getServer()->getPlayerExact($pn);
                                if($p != null){
                                    $p->sendMessage($this->plugin->prefix."Arena restarted in ".$endtimer." seconds !");
                                } else {
                                    $this->plugin->removePlayerFromArena($arena, $pn);
                                }
                            }
                        }
                        if($endtimer == 0){
                            foreach ($players as $pn) {
                                $p = $this->plugin->getServer()->getPlayerExact($pn);
                                if($p != null){
                                    $p->teleport($this->plugin->getServer()->getDefaultLevel()->getSafeSpawn());
                                    $p->setFood(20);
                                    $p->setHealth(20);
                                    $p->getInventory()->clearAll();
                                    $p->removeAllEffects();
                                    $p->setExpLevel(0);
                                    $p->setNameTag($p->getName());
                                }
                            }
                            $this->plugin->resetArena($arena, true);
                        }
                    }
                }
            }
        }
    }

}
