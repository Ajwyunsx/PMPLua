<?php

namespace LuaLoader;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class Main extends PluginBase{
	
	/** @var string Path to the libs folder */
	private $libsPath;
	
	/** @var array Download URLs for php_lua by PHP version */
	private static $downloadUrls = [
		// Windows DLLs - These are example URLs, should be updated with actual hosting
		"windows" => [
			"7.0" => [
				"x64" => [
					"ts" => "https://github.com/aspect/php-lua/releases/download/v2.0.7/php_lua-2.0.7-7.0-ts-vc14-x64.zip",
					"nts" => "https://github.com/aspect/php-lua/releases/download/v2.0.7/php_lua-2.0.7-7.0-nts-vc14-x64.zip"
				],
				"x86" => [
					"ts" => "https://github.com/aspect/php-lua/releases/download/v2.0.7/php_lua-2.0.7-7.0-ts-vc14-x86.zip",
					"nts" => "https://github.com/aspect/php-lua/releases/download/v2.0.7/php_lua-2.0.7-7.0-nts-vc14-x86.zip"
				]
			],
			"7.2" => [
				"x64" => [
					"ts" => "https://github.com/aspect/php-lua/releases/download/v2.0.7/php_lua-2.0.7-7.2-ts-vc15-x64.zip",
					"nts" => "https://github.com/aspect/php-lua/releases/download/v2.0.7/php_lua-2.0.7-7.2-nts-vc15-x64.zip"
				]
			],
			"7.4" => [
				"x64" => [
					"ts" => "https://github.com/aspect/php-lua/releases/download/v2.0.7/php_lua-2.0.7-7.4-ts-vc15-x64.zip",
					"nts" => "https://github.com/aspect/php-lua/releases/download/v2.0.7/php_lua-2.0.7-7.4-nts-vc15-x64.zip"
				]
			]
		]
	];

	public function onLoad(){
		// Determine libs path based on plugin location
		$this->libsPath = dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . "libs";
		@mkdir($this->libsPath, 0777, true);
		
		// Try to load the Lua extension dynamically if it's not loaded
		if(!extension_loaded("lua")){
			$this->getLogger()->info("Lua extension not found. Checking for libraries...");
			
			// Check if DLL exists, if not try to download
			if(!$this->checkLibraryExists()){
				$this->getLogger()->info("Libraries not found. Attempting auto-download...");
				$this->autoDownloadLibrary();
			}
			
			$this->loadLuaExtension();
		}else{
			$this->getLogger()->info("Lua extension is already loaded.");
		}
	}
	
	/**
	 * Check if library files exist
	 */
	private function checkLibraryExists(){
		$os = $this->getOS();
		$pluginDir = dirname(dirname(dirname(__FILE__)));
		
		$extensionNames = [
			"windows" => ["php_lua.dll"],
			"linux" => ["lua.so", "php_lua.so"],
			"macos" => ["lua.so"]
		];
		
		$searchPaths = [
			$pluginDir,
			$pluginDir . DIRECTORY_SEPARATOR . "libs",
			$pluginDir . DIRECTORY_SEPARATOR . "libs" . DIRECTORY_SEPARATOR . $os,
		];
		
		foreach($searchPaths as $searchPath){
			if(!is_dir($searchPath)) continue;
			foreach($extensionNames[$os] ?? [] as $extName){
				if(file_exists($searchPath . DIRECTORY_SEPARATOR . $extName)){
					return true;
				}
			}
		}
		
		return false;
	}
	
	/**
	 * Auto-download php_lua library based on PHP version
	 */
	private function autoDownloadLibrary(){
		$os = $this->getOS();
		$phpVersion = PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;
		$arch = PHP_INT_SIZE === 8 ? "x64" : "x86";
		$ts = PHP_ZTS ? "ts" : "nts";
		
		$this->getLogger()->info("PHP Version: " . $phpVersion);
		$this->getLogger()->info("Architecture: " . $arch);
		$this->getLogger()->info("Thread Safety: " . $ts);
		$this->getLogger()->info("OS: " . $os);
		
		// Get download URL
		$url = $this->getDownloadUrl($os, $phpVersion, $arch, $ts);
		
		if($url === null){
			$this->getLogger()->warning("No pre-built library available for PHP $phpVersion ($arch, $ts)");
			$this->showManualDownloadInstructions($os, $phpVersion);
			return false;
		}
		
		$this->getLogger()->info("Downloading from: " . $url);
		
		// Download the library
		$targetDir = $this->libsPath . DIRECTORY_SEPARATOR . $os;
		@mkdir($targetDir, 0777, true);
		
		try {
			$result = $this->downloadFile($url, $targetDir);
			if($result){
				$this->getLogger()->info("Successfully downloaded Lua library!");
				return true;
			}
		} catch(\Throwable $e){
			$this->getLogger()->error("Download failed: " . $e->getMessage());
		}
		
		$this->showManualDownloadInstructions($os, $phpVersion);
		return false;
	}
	
	/**
	 * Get download URL for the current PHP configuration
	 */
	private function getDownloadUrl($os, $phpVersion, $arch, $ts){
		// Try exact version match
		if(isset(self::$downloadUrls[$os][$phpVersion][$arch][$ts])){
			return self::$downloadUrls[$os][$phpVersion][$arch][$ts];
		}
		
		// Try major version match
		$majorVersion = explode(".", $phpVersion)[0] . ".0";
		if(isset(self::$downloadUrls[$os][$majorVersion][$arch][$ts])){
			return self::$downloadUrls[$os][$majorVersion][$arch][$ts];
		}
		
		return null;
	}
	
	/**
	 * Download a file from URL
	 */
	private function downloadFile($url, $targetDir){
		$context = stream_context_create([
			"http" => [
				"method" => "GET",
				"header" => "User-Agent: LuaLoader/1.0\r\n",
				"follow_location" => true,
				"timeout" => 30
			],
			"ssl" => [
				"verify_peer" => false,
				"verify_peer_name" => false
			]
		]);
		
		$content = @file_get_contents($url, false, $context);
		
		if($content === false){
			$this->getLogger()->error("Failed to download file from: " . $url);
			return false;
		}
		
		// Check if it's a ZIP file
		if(substr($url, -4) === ".zip"){
			$tempFile = $targetDir . DIRECTORY_SEPARATOR . "temp_download.zip";
			file_put_contents($tempFile, $content);
			
			// Extract ZIP
			if(class_exists("ZipArchive")){
				$zip = new \ZipArchive();
				if($zip->open($tempFile) === true){
					$zip->extractTo($targetDir);
					$zip->close();
					unlink($tempFile);
					$this->getLogger()->info("Extracted library to: " . $targetDir);
					return true;
				}
			}else{
				$this->getLogger()->warning("ZipArchive not available. Please manually extract: " . $tempFile);
			}
		}else{
			// Direct DLL/SO file
			$filename = basename(parse_url($url, PHP_URL_PATH));
			file_put_contents($targetDir . DIRECTORY_SEPARATOR . $filename, $content);
			$this->getLogger()->info("Downloaded: " . $filename);
			return true;
		}
		
		return false;
	}
	
	/**
	 * Show manual download instructions
	 */
	private function showManualDownloadInstructions($os, $phpVersion){
		$this->getLogger()->info("=== Manual Download Instructions ===");
		
		switch($os){
			case "windows":
				$this->getLogger()->info("1. Visit: https://pecl.php.net/package/lua");
				$this->getLogger()->info("2. Download DLL for PHP $phpVersion");
				$this->getLogger()->info("3. Place php_lua.dll in plugins/LuaLoader/libs/windows/");
				$this->getLogger()->info("4. Download liblua.dll from lua.org");
				$this->getLogger()->info("Alternative: Add 'extension=php_lua.dll' to php.ini");
				break;
			case "linux":
				$this->getLogger()->info("Run: sudo pecl install lua");
				$this->getLogger()->info("Then add 'extension=lua.so' to php.ini");
				break;
			case "macos":
				$this->getLogger()->info("Run: brew install lua && pecl install lua");
				$this->getLogger()->info("Then add 'extension=lua.so' to php.ini");
				break;
		}
	}
	
	/**
	 * Attempt to load the Lua extension for the current platform
	 */
	private function loadLuaExtension(){
		$os = $this->getOS();
		$pluginDir = dirname(dirname(dirname(__FILE__)));
		
		$extensionNames = [
			"windows" => ["php_lua.dll"],
			"linux" => ["lua.so", "php_lua.so"],
			"macos" => ["lua.so", "php_lua.so"]
		];
		
		$searchPaths = [
			$pluginDir,
			$pluginDir . DIRECTORY_SEPARATOR . "libs",
			$pluginDir . DIRECTORY_SEPARATOR . "libs" . DIRECTORY_SEPARATOR . $os,
			$this->libsPath,
			$this->libsPath . DIRECTORY_SEPARATOR . $os,
		];
		
		$foundExt = null;
		foreach($searchPaths as $searchPath){
			if(!is_dir($searchPath)) continue;
			foreach($extensionNames[$os] ?? [] as $extName){
				$extPath = $searchPath . DIRECTORY_SEPARATOR . $extName;
				if(file_exists($extPath)){
					$foundExt = $extPath;
					break 2;
				}
			}
		}
		
		if($foundExt !== null){
			$this->getLogger()->info("Found library at: " . $foundExt);
		}
		
		// Check if dl() is available
		if(!function_exists("dl")){
			$this->getLogger()->warning("The 'dl()' function is not available.");
			return;
		}

		try{
			$loaded = false;
			foreach($extensionNames[$os] ?? [] as $extName){
				if(@dl($extName)){
					$this->getLogger()->info("Loaded Lua extension: " . $extName);
					$loaded = true;
					break;
				}
			}
			
			if(!$loaded && $foundExt !== null){
				if(@dl($foundExt)){
					$this->getLogger()->info("Loaded from: " . $foundExt);
					$loaded = true;
				}
			}
			
			if(!$loaded){
				$this->getLogger()->warning("Failed to load Lua extension dynamically.");
			}
		}catch(\Throwable $e){
			$this->getLogger()->error("Error loading: " . $e->getMessage());
		}
	}
	
	/**
	 * Get current OS type
	 */
	private function getOS(){
		if(stripos(PHP_OS, 'WIN') === 0) return "windows";
		if(stripos(PHP_OS, 'DARWIN') === 0) return "macos";
		return "linux";
	}

	public function onEnable(){
		if(!class_exists("Lua", false)){
			$this->getLogger()->warning("Lua extension NOT loaded. Lua plugins will not work.");
			return;
		}
		
		$this->getServer()->getPluginManager()->registerInterface(LuaPluginLoader::class);
		$this->getLogger()->info("LuaPluginLoader registered.");

		// Load .lua file plugins
		$plugins = $this->getServer()->getPluginManager()->loadPlugins($this->getServer()->getPluginPath(), [LuaPluginLoader::class]);
		
		// Scan for folder-based Lua plugins
		$folderPlugins = $this->scanFolderPlugins($this->getServer()->getPluginPath());
		
		$totalLoaded = count($plugins) + count($folderPlugins);
		
		if($totalLoaded > 0){
			$this->getLogger()->info("Loaded " . $totalLoaded . " Lua plugins.");
			
			foreach($plugins as $plugin){
				if(!$plugin->isEnabled()){
					$this->getServer()->getPluginManager()->enablePlugin($plugin);
				}
			}
			
			foreach($folderPlugins as $plugin){
				if(!$plugin->isEnabled()){
					$this->getServer()->getPluginManager()->enablePlugin($plugin);
				}
			}
		}
	}
	
	/**
	 * Scan for folder-based Lua plugins
	 */
	private function scanFolderPlugins($path){
		$plugins = [];
		$loader = new LuaPluginLoader($this->getServer());
		
		foreach(scandir($path) as $file){
			if($file === "." || $file === "..") continue;
			
			$fullPath = $path . DIRECTORY_SEPARATOR . $file;
			
			if(is_dir($fullPath) && LuaPluginLoader::isLuaPluginFolder($fullPath)){
				try {
					$plugin = $loader->loadPlugin($fullPath);
					if($plugin !== null){
						$plugins[] = $plugin;
					}
				} catch(\Throwable $e){
					$this->getLogger()->error("Failed to load '$file': " . $e->getMessage());
				}
			}
		}
		
		return $plugins;
	}
	
	public function onCommand(CommandSender $sender, Command $command, $label, array $args){
		if($command->getName() === "luareload"){
			if(!$sender->hasPermission("lualoader.reload")){
				$sender->sendMessage("§cNo permission.");
				return true;
			}
			
			if(empty($args)){
				$sender->sendMessage("§eUsage: /luareload <plugin|all>");
				return true;
			}
			
			$target = $args[0];
			
			if(strtolower($target) === "all"){
				$count = 0;
				foreach($this->getServer()->getPluginManager()->getPlugins() as $plugin){
					if($plugin instanceof LuaPlugin){
						$plugin->reloadScript();
						$count++;
					}
				}
				$sender->sendMessage("§aReloaded $count Lua plugin(s).");
			}else{
				$plugin = $this->getServer()->getPluginManager()->getPlugin($target);
				if($plugin instanceof LuaPlugin){
					$plugin->reloadScript();
					$sender->sendMessage("§aReloaded: " . $plugin->getName());
				}else{
					$sender->sendMessage("§cNot found: " . $target);
				}
			}
			
			return true;
		}
		
		return false;
	}
}
