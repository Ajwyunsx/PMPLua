<?php

namespace LuaLoader;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\item\Item;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\utils\Config;

class LuaPlugin extends PluginBase{
    
    /** @var \Lua */
    private $lua;
    
    /** @var string The original .lua script path */
    public static $pendingScriptPath = null;
    
    /** @var string The plugin folder path */
    public static $pendingPluginFolder = null;
    
    /** @var string */
    private $myScriptPath = null;
    
    /** @var string */
    private $myPluginFolder = null;
    
    /** @var array Loaded modules cache */
    private $loadedModules = [];

    public function onLoad(){
        // Capture the script path during onLoad (called from initPlugin)
        if(self::$pendingScriptPath !== null){
            $this->myScriptPath = self::$pendingScriptPath;
            self::$pendingScriptPath = null;
        }
        if(self::$pendingPluginFolder !== null){
            $this->myPluginFolder = self::$pendingPluginFolder;
            self::$pendingPluginFolder = null;
        }
    }

    public function onEnable(){
        if(class_exists("Lua", false)){
            $this->initLua();

            try {
                // Use our saved script path
                $path = $this->myScriptPath;
                
                if(empty($path)){
                    $path = rtrim($this->getFile(), "/\\");
                }
                
                if(empty($path) || !file_exists($path) || is_dir($path)){
                    $this->getLogger()->error("Invalid script path: " . ($path ?? "null"));
                    return;
                }

                $scriptContent = file_get_contents($path);
                $this->lua->eval($scriptContent);
                
                // Call onEnable hook if exists
                $this->lua->eval("if onEnable then onEnable() end");

            } catch(\Throwable $e){
                $this->getLogger()->error("Error executing Lua script: " . $e->getMessage());
                $this->getServer()->getPluginManager()->disablePlugin($this);
            }
        } else {
            $this->getLogger()->error("Lua extension not found. Cannot run " . $this->getName());
            $this->getServer()->getPluginManager()->disablePlugin($this);
        }
    }
    
    /**
     * Initialize Lua instance and register all APIs
     */
    private function initLua(){
        $this->lua = new \Lua();
        $this->loadedModules = [];
        
        // Register all API functions
        $this->registerLoggerAPI();
        $this->registerServerAPI();
        $this->registerPlayerAPI();
        $this->registerWorldAPI();
        $this->registerItemBlockAPI();
        $this->registerConfigAPI();
        $this->registerUtilAPI();
        $this->registerRequireAPI();
        $this->registerPluginAPI();
    }
    
    /**
     * Reload the Lua script
     */
    public function reloadScript(){
        $this->getLogger()->info("Reloading Lua script...");
        
        // Call onDisable if exists
        if($this->lua !== null){
            try {
                $this->lua->eval("if onReload then onReload() end");
            } catch(\Throwable $e){}
        }
        
        // Reinitialize Lua
        $this->initLua();
        
        try {
            $path = $this->myScriptPath;
            if(empty($path) || !file_exists($path)){
                $this->getLogger()->error("Cannot reload: script not found");
                return false;
            }
            
            $scriptContent = file_get_contents($path);
            $this->lua->eval($scriptContent);
            $this->lua->eval("if onEnable then onEnable() end");
            $this->getLogger()->info("Script reloaded successfully!");
            return true;
        } catch(\Throwable $e){
            $this->getLogger()->error("Error reloading script: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Register Plugin management API
     */
    private function registerPluginAPI(){
        // Reload this plugin's script
        $this->lua->registerCallback("reloadScript", function() {
            return $this->reloadScript();
        });
        
        // Get plugin folder (without trailing slash)
        $this->lua->registerCallback("getPluginFolder", function() {
            return rtrim($this->myPluginFolder ?? dirname($this->myScriptPath), "/\\");
        });
        
        // Get script path
        $this->lua->registerCallback("getScriptPath", function() {
            return $this->myScriptPath;
        });
    }
    
    /**
     * Register Lua require() support for loading modules
     */
    private function registerRequireAPI(){
        $plugin = $this;
        $scriptDir = dirname($this->myScriptPath);
        $pluginFolder = $this->myPluginFolder ?? $scriptDir;
        
        // Custom require function that loads .lua files
        $this->lua->registerCallback("require", function($moduleName) use ($plugin, $scriptDir, $pluginFolder) {
            // Check if already loaded
            if(isset($plugin->loadedModules[$moduleName])){
                return $plugin->loadedModules[$moduleName];
            }
            
            // Convert module name with dots to path (e.g., "lib.utils" -> "lib/utils.lua")
            $moduleFile = str_replace(".", DIRECTORY_SEPARATOR, $moduleName);
            
            // Build possible paths
            $paths = [
                $scriptDir . DIRECTORY_SEPARATOR . $moduleFile . ".lua",
                $scriptDir . DIRECTORY_SEPARATOR . $moduleName . ".lua",
                $pluginFolder . DIRECTORY_SEPARATOR . $moduleFile . ".lua",
                $pluginFolder . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR . $moduleName . ".lua",
                $pluginFolder . DIRECTORY_SEPARATOR . "modules" . DIRECTORY_SEPARATOR . $moduleName . ".lua",
                rtrim($plugin->getDataFolder(), "/\\") . DIRECTORY_SEPARATOR . $moduleName . ".lua",
            ];
            
            // Try each path
            foreach($paths as $path){
                if(file_exists($path) && !is_dir($path)){
                    try {
                        $content = file_get_contents($path);
                        $result = $plugin->lua->eval($content);
                        $plugin->loadedModules[$moduleName] = $result ?? true;
                        return $plugin->loadedModules[$moduleName];
                    } catch(\Throwable $e){
                        $plugin->getLogger()->error("Error loading module '$moduleName': " . $e->getMessage());
                        return null;
                    }
                }
            }
            
            $plugin->getLogger()->warning("Module not found: " . $moduleName);
            return null;
        });
        
        // dofile - execute a Lua file
        $this->lua->registerCallback("dofile", function($filename) use ($plugin, $scriptDir, $pluginFolder) {
            $paths = [$filename, $scriptDir . DIRECTORY_SEPARATOR . $filename, $pluginFolder . DIRECTORY_SEPARATOR . $filename];
            foreach($paths as $path){
                if(file_exists($path) && !is_dir($path)){
                    return $plugin->lua->eval(file_get_contents($path));
                }
            }
            $plugin->getLogger()->error("dofile: File not found: " . $filename);
            return null;
        });
        
        // loadfile - load but don't execute
        $this->lua->registerCallback("loadfile", function($filename) use ($plugin, $scriptDir, $pluginFolder) {
            $paths = [$filename, $scriptDir . DIRECTORY_SEPARATOR . $filename, $pluginFolder . DIRECTORY_SEPARATOR . $filename];
            foreach($paths as $path){
                if(file_exists($path) && !is_dir($path)){
                    return file_get_contents($path);
                }
            }
            return null;
        });
    }
    
    /**
     * Logger API
     */
    private function registerLoggerAPI(){
        $this->lua->registerCallback("logInfo", function($msg) { 
            $this->getLogger()->info($msg); 
        });
        $this->lua->registerCallback("logWarning", function($msg) { 
            $this->getLogger()->warning($msg); 
        });
        $this->lua->registerCallback("logError", function($msg) { 
            $this->getLogger()->error($msg); 
        });
        $this->lua->registerCallback("logDebug", function($msg) { 
            $this->getLogger()->debug($msg); 
        });
    }
    
    /**
     * Server API
     */
    private function registerServerAPI(){
        $this->lua->registerCallback("getServerName", function() { 
            return $this->getServer()->getName(); 
        });
        $this->lua->registerCallback("getServerMotd", function() { 
            return $this->getServer()->getMotd(); 
        });
        $this->lua->registerCallback("setServerMotd", function($motd) { 
            $this->getServer()->getNetwork()->setName($motd);
        });
        $this->lua->registerCallback("broadcastMessage", function($msg) { 
            $this->getServer()->broadcastMessage($msg); 
        });
        $this->lua->registerCallback("broadcastTip", function($msg) { 
            foreach($this->getServer()->getOnlinePlayers() as $p){
                $p->sendTip($msg);
            }
        });
        $this->lua->registerCallback("broadcastPopup", function($msg) { 
            foreach($this->getServer()->getOnlinePlayers() as $p){
                $p->sendPopup($msg);
            }
        });
        $this->lua->registerCallback("getMaxPlayers", function() { 
            return $this->getServer()->getMaxPlayers(); 
        });
        $this->lua->registerCallback("getOnlineCount", function() { 
            return count($this->getServer()->getOnlinePlayers()); 
        });
        $this->lua->registerCallback("getTPS", function() { 
            return $this->getServer()->getTicksPerSecond(); 
        });
        $this->lua->registerCallback("getTickUsage", function() { 
            return $this->getServer()->getTickUsage(); 
        });
        $this->lua->registerCallback("dispatchCommand", function($cmd) { 
            return $this->getServer()->dispatchCommand($this->getServer()->getConsoleSender(), $cmd); 
        });
    }
    
    /**
     * Player API
     */
    private function registerPlayerAPI(){
        $this->lua->registerCallback("getPluginName", function() { 
            return $this->getName(); 
        });
        
        // Data folder WITHOUT trailing slash
        $this->lua->registerCallback("getDataFolder", function() { 
            return rtrim($this->getDataFolder(), "/\\"); 
        });
        
        // Get all online players
        $this->lua->registerCallback("getOnlinePlayers", function() {
            $players = [];
            foreach($this->getServer()->getOnlinePlayers() as $p){
                $players[] = $p->getName();
            }
            return $players;
        });
        
        // Get player by name
        $this->lua->registerCallback("getPlayer", function($name) {
            return $this->getServer()->getPlayer($name);
        });
        
        // Get exact player by name
        $this->lua->registerCallback("getPlayerExact", function($name) {
            return $this->getServer()->getPlayerExact($name);
        });
        
        // Send message to player
        $this->lua->registerCallback("sendMessage", function($player, $msg) {
            if($player instanceof CommandSender){
                $player->sendMessage($msg);
            } elseif(is_string($player)){
                $p = $this->getServer()->getPlayer($player);
                if($p !== null) $p->sendMessage($msg);
            }
        });
        
        // Send tip to player
        $this->lua->registerCallback("sendTip", function($player, $msg) {
            if($player instanceof Player){
                $player->sendTip($msg);
            } elseif(is_string($player)){
                $p = $this->getServer()->getPlayer($player);
                if($p instanceof Player) $p->sendTip($msg);
            }
        });
        
        // Send popup to player
        $this->lua->registerCallback("sendPopup", function($player, $msg) {
            if($player instanceof Player){
                $player->sendPopup($msg);
            } elseif(is_string($player)){
                $p = $this->getServer()->getPlayer($player);
                if($p instanceof Player) $p->sendPopup($msg);
            }
        });
        
        // Kick player
        $this->lua->registerCallback("kickPlayer", function($player, $reason = "Kicked") {
            if($player instanceof Player){
                $player->kick($reason);
            } elseif(is_string($player)){
                $p = $this->getServer()->getPlayer($player);
                if($p instanceof Player) $p->kick($reason);
            }
        });
        
        // Get player position
        $this->lua->registerCallback("getPlayerPosition", function($player) {
            $p = ($player instanceof Player) ? $player : $this->getServer()->getPlayer($player);
            if($p instanceof Player){
                return [
                    "x" => $p->getX(),
                    "y" => $p->getY(),
                    "z" => $p->getZ(),
                    "level" => $p->getLevel()->getName()
                ];
            }
            return null;
        });
        
        // Teleport player
        $this->lua->registerCallback("teleportPlayer", function($player, $x, $y, $z, $levelName = null) {
            $p = ($player instanceof Player) ? $player : $this->getServer()->getPlayer($player);
            if($p instanceof Player){
                $level = $levelName ? $this->getServer()->getLevelByName($levelName) : $p->getLevel();
                if($level instanceof Level){
                    $p->teleport(new Position($x, $y, $z, $level));
                    return true;
                }
            }
            return false;
        });
        
        // Get player health
        $this->lua->registerCallback("getPlayerHealth", function($player) {
            $p = ($player instanceof Player) ? $player : $this->getServer()->getPlayer($player);
            return $p instanceof Player ? $p->getHealth() : null;
        });
        
        // Set player health
        $this->lua->registerCallback("setPlayerHealth", function($player, $health) {
            $p = ($player instanceof Player) ? $player : $this->getServer()->getPlayer($player);
            if($p instanceof Player){
                $p->setHealth($health);
                return true;
            }
            return false;
        });
        
        // Get player gamemode
        $this->lua->registerCallback("getPlayerGamemode", function($player) {
            $p = ($player instanceof Player) ? $player : $this->getServer()->getPlayer($player);
            return $p instanceof Player ? $p->getGamemode() : null;
        });
        
        // Set player gamemode
        $this->lua->registerCallback("setPlayerGamemode", function($player, $mode) {
            $p = ($player instanceof Player) ? $player : $this->getServer()->getPlayer($player);
            if($p instanceof Player){
                $p->setGamemode($mode);
                return true;
            }
            return false;
        });
        
        // Give item to player
        $this->lua->registerCallback("giveItem", function($player, $itemId, $count = 1, $meta = 0) {
            $p = ($player instanceof Player) ? $player : $this->getServer()->getPlayer($player);
            if($p instanceof Player){
                $item = Item::get($itemId, $meta, $count);
                $p->getInventory()->addItem($item);
                return true;
            }
            return false;
        });
        
        // Check if player is op
        $this->lua->registerCallback("isOp", function($player) {
            $p = ($player instanceof Player) ? $player : $this->getServer()->getPlayer($player);
            return $p instanceof Player ? $p->isOp() : false;
        });
    }
    
    /**
     * World/Level API
     */
    private function registerWorldAPI(){
        // Get all levels
        $this->lua->registerCallback("getLevels", function() {
            $levels = [];
            foreach($this->getServer()->getLevels() as $level){
                $levels[] = $level->getName();
            }
            return $levels;
        });
        
        // Get default level
        $this->lua->registerCallback("getDefaultLevel", function() {
            return $this->getServer()->getDefaultLevel()->getName();
        });
        
        // Load level
        $this->lua->registerCallback("loadLevel", function($name) {
            return $this->getServer()->loadLevel($name);
        });
        
        // Unload level
        $this->lua->registerCallback("unloadLevel", function($name) {
            $level = $this->getServer()->getLevelByName($name);
            if($level instanceof Level){
                return $this->getServer()->unloadLevel($level);
            }
            return false;
        });
        
        // Get block at position
        $this->lua->registerCallback("getBlock", function($levelName, $x, $y, $z) {
            $level = $this->getServer()->getLevelByName($levelName);
            if($level instanceof Level){
                $block = $level->getBlock(new Vector3($x, $y, $z));
                return [
                    "id" => $block->getId(),
                    "meta" => $block->getDamage(),
                    "name" => $block->getName()
                ];
            }
            return null;
        });
        
        // Set block at position
        $this->lua->registerCallback("setBlock", function($levelName, $x, $y, $z, $blockId, $meta = 0) {
            $level = $this->getServer()->getLevelByName($levelName);
            if($level instanceof Level){
                $block = Block::get($blockId, $meta);
                $level->setBlock(new Vector3($x, $y, $z), $block);
                return true;
            }
            return false;
        });
        
        // Get level time
        $this->lua->registerCallback("getLevelTime", function($levelName) {
            $level = $this->getServer()->getLevelByName($levelName);
            return $level instanceof Level ? $level->getTime() : null;
        });
        
        // Set level time
        $this->lua->registerCallback("setLevelTime", function($levelName, $time) {
            $level = $this->getServer()->getLevelByName($levelName);
            if($level instanceof Level){
                $level->setTime($time);
                return true;
            }
            return false;
        });
    }
    
    /**
     * Item/Block API
     */
    private function registerItemBlockAPI(){
        // Get item info
        $this->lua->registerCallback("getItemInfo", function($itemId, $meta = 0) {
            $item = Item::get($itemId, $meta);
            return [
                "id" => $item->getId(),
                "meta" => $item->getDamage(),
                "name" => $item->getName(),
                "maxStack" => $item->getMaxStackSize()
            ];
        });
        
        // Get block info
        $this->lua->registerCallback("getBlockInfo", function($blockId, $meta = 0) {
            $block = Block::get($blockId, $meta);
            return [
                "id" => $block->getId(),
                "meta" => $block->getDamage(),
                "name" => $block->getName(),
                "hardness" => $block->getHardness()
            ];
        });
    }
    
    /**
     * Config/File API
     */
    private function registerConfigAPI(){
        // Create data folder if not exists
        $this->lua->registerCallback("createDataFolder", function() {
            @mkdir(rtrim($this->getDataFolder(), "/\\"), 0777, true);
            return is_dir($this->getDataFolder());
        });
        
        // Read file
        $this->lua->registerCallback("readFile", function($filename) {
            $path = rtrim($this->getDataFolder(), "/\\") . DIRECTORY_SEPARATOR . $filename;
            if(file_exists($path)){
                return file_get_contents($path);
            }
            return null;
        });
        
        // Write file
        $this->lua->registerCallback("writeFile", function($filename, $content) {
            @mkdir(rtrim($this->getDataFolder(), "/\\"), 0777, true);
            $path = rtrim($this->getDataFolder(), "/\\") . DIRECTORY_SEPARATOR . $filename;
            return file_put_contents($path, $content) !== false;
        });
        
        // File exists
        $this->lua->registerCallback("fileExists", function($filename) {
            return file_exists(rtrim($this->getDataFolder(), "/\\") . DIRECTORY_SEPARATOR . $filename);
        });
        
        // Delete file
        $this->lua->registerCallback("deleteFile", function($filename) {
            $path = rtrim($this->getDataFolder(), "/\\") . DIRECTORY_SEPARATOR . $filename;
            if(file_exists($path)){
                return unlink($path);
            }
            return false;
        });
        
        // Load YAML config
        $this->lua->registerCallback("loadConfig", function($filename = "config.yml") {
            $path = rtrim($this->getDataFolder(), "/\\") . DIRECTORY_SEPARATOR . $filename;
            @mkdir(rtrim($this->getDataFolder(), "/\\"), 0777, true);
            if(file_exists($path)){
                $config = new Config($path, Config::YAML);
                return $config->getAll();
            }
            return [];
        });
        
        // Save YAML config
        $this->lua->registerCallback("saveConfig", function($filename, $data) {
            @mkdir(rtrim($this->getDataFolder(), "/\\"), 0777, true);
            $path = rtrim($this->getDataFolder(), "/\\") . DIRECTORY_SEPARATOR . $filename;
            $config = new Config($path, Config::YAML);
            $config->setAll($data);
            $config->save();
            return true;
        });
        
        // Load JSON
        $this->lua->registerCallback("loadJson", function($filename) {
            $path = rtrim($this->getDataFolder(), "/\\") . DIRECTORY_SEPARATOR . $filename;
            if(file_exists($path)){
                return json_decode(file_get_contents($path), true);
            }
            return null;
        });
        
        // Save JSON
        $this->lua->registerCallback("saveJson", function($filename, $data) {
            @mkdir(rtrim($this->getDataFolder(), "/\\"), 0777, true);
            $path = rtrim($this->getDataFolder(), "/\\") . DIRECTORY_SEPARATOR . $filename;
            return file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT)) !== false;
        });
    }
    
    /**
     * Utility API
     */
    private function registerUtilAPI(){
        // Get current timestamp
        $this->lua->registerCallback("time", function() {
            return time();
        });
        
        // Get microtime
        $this->lua->registerCallback("microtime", function() {
            return microtime(true);
        });
        
        // Date format
        $this->lua->registerCallback("date", function($format = "Y-m-d H:i:s", $timestamp = null) {
            return date($format, $timestamp ?? time());
        });
        
        // Random number
        $this->lua->registerCallback("random", function($min = 0, $max = PHP_INT_MAX) {
            return mt_rand($min, $max);
        });
        
        // Sleep (in milliseconds) - use with caution!
        $this->lua->registerCallback("sleep", function($ms) {
            usleep($ms * 1000);
        });
        
        // JSON encode
        $this->lua->registerCallback("jsonEncode", function($data) {
            return json_encode($data);
        });
        
        // JSON decode
        $this->lua->registerCallback("jsonDecode", function($json) {
            return json_decode($json, true);
        });
        
        // String functions
        $this->lua->registerCallback("strContains", function($haystack, $needle) {
            return strpos($haystack, $needle) !== false;
        });
        
        $this->lua->registerCallback("strReplace", function($search, $replace, $subject) {
            return str_replace($search, $replace, $subject);
        });
        
        $this->lua->registerCallback("strSplit", function($str, $delimiter = " ") {
            return explode($delimiter, $str);
        });
        
        // Table/Array helpers
        $this->lua->registerCallback("tableContains", function($table, $value) {
            return in_array($value, $table);
        });
        
        $this->lua->registerCallback("tableCount", function($table) {
            return count($table);
        });
        
        // Get OS type
        $this->lua->registerCallback("getOS", function() {
            if(stripos(PHP_OS, 'WIN') === 0) return "windows";
            if(stripos(PHP_OS, 'DARWIN') === 0) return "macos";
            return "linux";
        });
    }

    public function onDisable(){
        if($this->lua !== null){
            try {
                $this->lua->eval("if onDisable then onDisable() end");
            } catch(\Throwable $e){
                $this->getLogger()->error("Error in Lua onDisable: " . $e->getMessage());
            }
        }
    }

    public function onCommand(CommandSender $sender, Command $command, $label, array $args){
        if($this->lua !== null){
             try {
                 $this->lua->assign("_cmd_sender", $sender);
                 $this->lua->assign("_cmd_command_name", $command->getName());
                 $this->lua->assign("_cmd_label", $label);
                 $this->lua->assign("_cmd_args", $args);
                 
                 return $this->lua->eval("if onCommand then return onCommand(_cmd_sender, _cmd_command_name, _cmd_label, _cmd_args) else return false end");
             } catch(\Throwable $e){
                 $this->getLogger()->error("Error in Lua onCommand: " . $e->getMessage());
             }
        }
        return false;
    }
    
    /**
     * Get the Lua instance for external access
     */
    public function getLua(){
        return $this->lua;
    }
}
