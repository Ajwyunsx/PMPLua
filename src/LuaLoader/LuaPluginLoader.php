<?php

namespace LuaLoader;

use pocketmine\plugin\PluginLoader;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;
use pocketmine\plugin\PluginDescription;
use pocketmine\Server;
use pocketmine\utils\PluginException;

class LuaPluginLoader implements PluginLoader{

	private $server;

	public function __construct(Server $server){
		$this->server = $server;
	}

	public function loadPlugin($file){
		$description = $this->getPluginDescription($file);
		if($description instanceof PluginDescription){
			$this->server->getLogger()->info($this->server->getLanguage()->translateString("pocketmine.plugin.load", [$description->getFullName()]));
			
			// Determine data folder based on plugin type
			if(is_dir($file)){
				// Folder-based plugin: dataFolder is inside the plugin folder
				$dataFolder = $file . DIRECTORY_SEPARATOR . "data";
			}else{
				// Single file plugin: dataFolder is beside the .lua file
				$dataFolder = dirname($file) . DIRECTORY_SEPARATOR . $description->getName();
			}
			
			if(file_exists($dataFolder) and !is_dir($dataFolder)){
				throw new \InvalidStateException("Projected dataFolder '" . $dataFolder . "' for " . $description->getName() . " exists and is not a directory");
			}

			// We use a generic LuaPlugin class to wrap the script
			$className = $description->getMain();
			
			if(class_exists($className, true)){
				$plugin = new $className();
				
				// Set the script path BEFORE init so onLoad() can capture it
				if($plugin instanceof LuaPlugin){
					// For folder plugins, the main script is plugin.lua or main.lua inside the folder
					if(is_dir($file)){
						$mainScript = $file . DIRECTORY_SEPARATOR . "plugin.lua";
						if(!file_exists($mainScript)){
							$mainScript = $file . DIRECTORY_SEPARATOR . "main.lua";
						}
						LuaPlugin::$pendingScriptPath = $mainScript;
						LuaPlugin::$pendingPluginFolder = $file;
					}else{
						LuaPlugin::$pendingScriptPath = $file;
						LuaPlugin::$pendingPluginFolder = dirname($file);
					}
				}
				
				$this->initPlugin($plugin, $description, $dataFolder, $file);
				
				return $plugin;
			}else{
				throw new PluginException("Couldn't load plugin " . $description->getName() . ": main class $className not found");
			}
		}

		return null;
	}

	public function getPluginDescription($file){
		$content = null;
		$isFolder = is_dir($file);
		
		if($isFolder){
			// Folder-based plugin: look for plugin.lua or main.lua
			$mainScript = $file . DIRECTORY_SEPARATOR . "plugin.lua";
			if(!file_exists($mainScript)){
				$mainScript = $file . DIRECTORY_SEPARATOR . "main.lua";
			}
			if(file_exists($mainScript)){
				$content = file($mainScript, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
			}
		}else{
			// Single .lua file
			$content = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		}
		
		if($content === null || $content === false){
			return null;
		}
		
		$data = [];
		// Set default main class
		$data["main"] = "LuaLoader\\LuaPlugin";

		foreach($content as $line){
			if(preg_match("/^--[ \t]*@([a-zA-Z]+)([ \t]+(.*))?$/", $line, $matches) > 0){
				$key = $matches[1];
				$value = trim($matches[3] ?? "");
				$data[$key] = $value;
			}
		}

		if(isset($data["name"]) and isset($data["version"])){
			if(!isset($data["api"])){
				$data["api"] = ["2.0.0"]; // Default API
			}
			return new PluginDescription($data);
		}

		return null;
	}

	public function getPluginFilters(){
		// Match .lua files OR directories containing plugin.lua/main.lua
		return "/\\.lua$/i";
	}
	
	/**
	 * Check if a directory is a valid Lua plugin folder
	 */
	public static function isLuaPluginFolder($path){
		if(!is_dir($path)) return false;
		return file_exists($path . DIRECTORY_SEPARATOR . "plugin.lua") 
			|| file_exists($path . DIRECTORY_SEPARATOR . "main.lua");
	}

	private function initPlugin(PluginBase $plugin, PluginDescription $description, $dataFolder, $file){
		$plugin->init($this, $this->server, $description, $dataFolder, $file);
		$plugin->onLoad();
	}

	public function enablePlugin(Plugin $plugin){
		if($plugin instanceof PluginBase and !$plugin->isEnabled()){
			$this->server->getLogger()->info($this->server->getLanguage()->translateString("pocketmine.plugin.enable", [$plugin->getDescription()->getFullName()]));
			$plugin->setEnabled(true);
			$this->server->getPluginManager()->callEvent(new \pocketmine\event\plugin\PluginEnableEvent($plugin));
		}
	}

	public function disablePlugin(Plugin $plugin){
		if($plugin instanceof PluginBase and $plugin->isEnabled()){
			$this->server->getLogger()->info($this->server->getLanguage()->translateString("pocketmine.plugin.disable", [$plugin->getDescription()->getFullName()]));
			$this->server->getPluginManager()->callEvent(new \pocketmine\event\plugin\PluginDisableEvent($plugin));
			$plugin->setEnabled(false);
		}
	}
}