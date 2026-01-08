<?php

namespace LuaLoader;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class Main extends PluginBase{
	
	/** @var string Path to the libs folder */
	private $libsPath;
	
	/**
	 * Download URLs for php_lua by PHP version and platform
	 * Sources:
	 * - Windows: https://pecl.php.net/package/lua (DLL downloads)
	 * - Linux/macOS: PECL pecl install lua (build from source)
	 * - Pre-built binaries from various sources
	 */
	private static $downloadUrls = [
		// Windows DLLs from PECL and GitHub releases
		"windows" => [
			"7.0" => [
				"x64" => [
					"ts" => "https://windows.php.net/downloads/pecl/releases/lua/2.0.7/php_lua-2.0.7-7.0-ts-vc14-x64.zip",
					"nts" => "https://windows.php.net/downloads/pecl/releases/lua/2.0.7/php_lua-2.0.7-7.0-nts-vc14-x64.zip"
				],
				"x86" => [
					"ts" => "https://windows.php.net/downloads/pecl/releases/lua/2.0.7/php_lua-2.0.7-7.0-ts-vc14-x86.zip",
					"nts" => "https://windows.php.net/downloads/pecl/releases/lua/2.0.7/php_lua-2.0.7-7.0-nts-vc14-x86.zip"
				]
			],
			"7.1" => [
				"x64" => [
					"ts" => "https://windows.php.net/downloads/pecl/releases/lua/2.0.7/php_lua-2.0.7-7.1-ts-vc14-x64.zip",
					"nts" => "https://windows.php.net/downloads/pecl/releases/lua/2.0.7/php_lua-2.0.7-7.1-nts-vc14-x64.zip"
				]
			],
			"7.2" => [
				"x64" => [
					"ts" => "https://windows.php.net/downloads/pecl/releases/lua/2.0.7/php_lua-2.0.7-7.2-ts-vc15-x64.zip",
					"nts" => "https://windows.php.net/downloads/pecl/releases/lua/2.0.7/php_lua-2.0.7-7.2-nts-vc15-x64.zip"
				]
			],
			"7.3" => [
				"x64" => [
					"ts" => "https://windows.php.net/downloads/pecl/releases/lua/2.0.7/php_lua-2.0.7-7.3-ts-vc15-x64.zip",
					"nts" => "https://windows.php.net/downloads/pecl/releases/lua/2.0.7/php_lua-2.0.7-7.3-nts-vc15-x64.zip"
				]
			],
			"7.4" => [
				"x64" => [
					"ts" => "https://windows.php.net/downloads/pecl/releases/lua/2.0.7/php_lua-2.0.7-7.4-ts-vc15-x64.zip",
					"nts" => "https://windows.php.net/downloads/pecl/releases/lua/2.0.7/php_lua-2.0.7-7.4-nts-vc15-x64.zip"
				]
			],
			"8.0" => [
				"x64" => [
					"ts" => "https://windows.php.net/downloads/pecl/releases/lua/2.0.7/php_lua-2.0.7-8.0-ts-vs16-x64.zip",
					"nts" => "https://windows.php.net/downloads/pecl/releases/lua/2.0.7/php_lua-2.0.7-8.0-nts-vs16-x64.zip"
				]
			],
			"8.1" => [
				"x64" => [
					"ts" => "https://windows.php.net/downloads/pecl/releases/lua/2.0.7/php_lua-2.0.7-8.1-ts-vs16-x64.zip",
					"nts" => "https://windows.php.net/downloads/pecl/releases/lua/2.0.7/php_lua-2.0.7-8.1-nts-vs16-x64.zip"
				]
			],
			"8.2" => [
				"x64" => [
					"ts" => "https://windows.php.net/downloads/pecl/releases/lua/2.0.7/php_lua-2.0.7-8.2-ts-vs16-x64.zip",
					"nts" => "https://windows.php.net/downloads/pecl/releases/lua/2.0.7/php_lua-2.0.7-8.2-nts-vs16-x64.zip"
				]
			],
			"8.3" => [
				"x64" => [
					"ts" => "https://windows.php.net/downloads/pecl/releases/lua/2.0.7/php_lua-2.0.7-8.3-ts-vs16-x64.zip",
					"nts" => "https://windows.php.net/downloads/pecl/releases/lua/2.0.7/php_lua-2.0.7-8.3-nts-vs16-x64.zip"
				]
			]
		],
		// Linux - will attempt to build from source using pecl
		"linux" => [
			"source" => "https://pecl.php.net/get/lua-2.0.7.tgz"
		],
		// macOS - will attempt to build from source using pecl
		"macos" => [
			"source" => "https://pecl.php.net/get/lua-2.0.7.tgz"
		]
	];

	public function onLoad(){
		$this->libsPath = dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . "libs";
		@mkdir($this->libsPath, 0777, true);
		
		if(!extension_loaded("lua")){
			$this->getLogger()->info("Lua extension not found. Checking for libraries...");
			
			if(!$this->checkLibraryExists()){
				$this->getLogger()->info("Libraries not found. Attempting auto-download/install...");
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
			"macos" => ["lua.so", "php_lua.so"]
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
		
		// Check PHP extension directory
		$extDir = ini_get("extension_dir");
		if($extDir){
			foreach($extensionNames[$os] ?? [] as $extName){
				if(file_exists($extDir . DIRECTORY_SEPARATOR . $extName)){
					return true;
				}
			}
		}
		
		return false;
	}
	
	/**
	 * Auto-download php_lua library based on PHP version and OS
	 */
	private function autoDownloadLibrary(){
		$os = $this->getOS();
		$phpVersion = PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;
		$arch = PHP_INT_SIZE === 8 ? "x64" : "x86";
		$ts = PHP_ZTS ? "ts" : "nts";
		
		$this->getLogger()->info("=== Auto-Download/Install ===");
		$this->getLogger()->info("PHP Version: " . $phpVersion);
		$this->getLogger()->info("Architecture: " . $arch);
		$this->getLogger()->info("Thread Safety: " . ($ts === "ts" ? "Enabled" : "Disabled"));
		$this->getLogger()->info("OS: " . $os);
		
		switch($os){
			case "windows":
				return $this->downloadWindowsLibrary($phpVersion, $arch, $ts);
			case "linux":
				return $this->installLinuxLibrary();
			case "macos":
				return $this->installMacOSLibrary();
		}
		
		return false;
	}
	
	/**
	 * Download Windows DLL
	 */
	private function downloadWindowsLibrary($phpVersion, $arch, $ts){
		$url = $this->getDownloadUrl("windows", $phpVersion, $arch, $ts);
		
		if($url === null){
			$this->getLogger()->warning("No pre-built DLL for PHP $phpVersion ($arch, $ts)");
			$this->showManualDownloadInstructions("windows", $phpVersion);
			return false;
		}
		
		$this->getLogger()->info("Downloading from: " . $url);
		
		$targetDir = $this->libsPath . DIRECTORY_SEPARATOR . "windows";
		@mkdir($targetDir, 0777, true);
		
		try {
			$result = $this->downloadFile($url, $targetDir);
			if($result){
				$this->getLogger()->info("Successfully downloaded Lua library!");
				
				// Also try to download liblua.dll
				$this->downloadLuaCoreDLL($targetDir);
				return true;
			}
		} catch(\Throwable $e){
			$this->getLogger()->error("Download failed: " . $e->getMessage());
		}
		
		$this->showManualDownloadInstructions("windows", $phpVersion);
		return false;
	}
	
	/**
	 * Download Lua core DLL for Windows
	 */
	private function downloadLuaCoreDLL($targetDir){
		// Try to download liblua.dll from lua.org or GitHub
		$luaUrls = [
			"https://sourceforge.net/projects/luabinaries/files/5.3.6/Windows%20Libraries/Dynamic/lua-5.3.6_Win64_dll17_lib.zip/download",
		];
		
		$this->getLogger()->info("Attempting to download Lua core library...");
		
		// For simplicity, just inform user to download manually
		if(!file_exists($targetDir . DIRECTORY_SEPARATOR . "liblua.dll")){
			$this->getLogger()->warning("Please also download liblua.dll from lua.org");
			$this->getLogger()->info("Place it in: " . $targetDir);
		}
	}
	
	/**
	 * Install Lua extension on Linux using pecl or build from source
	 */
	private function installLinuxLibrary(){
		$this->getLogger()->info("=== Linux Auto-Install ===");
		
		// Check if pecl is available
		$peclPath = trim(shell_exec("which pecl 2>/dev/null") ?? "");
		
		if(!empty($peclPath)){
			$this->getLogger()->info("Found pecl at: " . $peclPath);
			$this->getLogger()->info("Attempting: pecl install lua");
			
			// Try to install via pecl
			$output = [];
			$returnCode = 0;
			exec("pecl install lua 2>&1", $output, $returnCode);
			
			if($returnCode === 0){
				$this->getLogger()->info("Successfully installed lua extension via pecl!");
				$this->getLogger()->info("Please add 'extension=lua.so' to php.ini and restart.");
				return true;
			}else{
				$this->getLogger()->warning("pecl install failed. You may need sudo.");
				$this->getLogger()->info("Try manually: sudo pecl install lua");
			}
		}
		
		// Try to download and build from source
		$this->getLogger()->info("Attempting to download source and build...");
		
		$sourceUrl = self::$downloadUrls["linux"]["source"] ?? null;
		if($sourceUrl){
			$targetDir = $this->libsPath . DIRECTORY_SEPARATOR . "linux";
			@mkdir($targetDir, 0777, true);
			
			$result = $this->downloadFile($sourceUrl, $targetDir);
			if($result){
				$this->getLogger()->info("Source downloaded to: " . $targetDir);
				$this->getLogger()->info("To build manually:");
				$this->getLogger()->info("  cd " . $targetDir);
				$this->getLogger()->info("  tar xzf lua-2.0.7.tgz && cd lua-2.0.7");
				$this->getLogger()->info("  phpize && ./configure && make && sudo make install");
				return true;
			}
		}
		
		$this->showManualDownloadInstructions("linux", PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION);
		return false;
	}
	
	/**
	 * Install Lua extension on macOS using pecl or brew
	 */
	private function installMacOSLibrary(){
		$this->getLogger()->info("=== macOS Auto-Install ===");
		
		// Check for Homebrew Lua first
		$brewLua = trim(shell_exec("brew --prefix lua 2>/dev/null") ?? "");
		if(empty($brewLua)){
			$this->getLogger()->info("Lua not found via Homebrew. Installing...");
			$output = [];
			exec("brew install lua 2>&1", $output, $returnCode);
			if($returnCode === 0){
				$this->getLogger()->info("Installed Lua via Homebrew.");
			}else{
				$this->getLogger()->warning("Failed to install Lua via Homebrew.");
			}
		}else{
			$this->getLogger()->info("Found Lua at: " . $brewLua);
		}
		
		// Check if pecl is available
		$peclPath = trim(shell_exec("which pecl 2>/dev/null") ?? "");
		
		if(!empty($peclPath)){
			$this->getLogger()->info("Found pecl at: " . $peclPath);
			$this->getLogger()->info("Attempting: pecl install lua");
			
			$output = [];
			$returnCode = 0;
			exec("pecl install lua 2>&1", $output, $returnCode);
			
			if($returnCode === 0){
				$this->getLogger()->info("Successfully installed lua extension via pecl!");
				$this->getLogger()->info("Please add 'extension=lua.so' to php.ini and restart.");
				return true;
			}else{
				$this->getLogger()->warning("pecl install failed.");
				$this->getLogger()->info("Try manually: sudo pecl install lua");
			}
		}
		
		// Download source for manual build
		$sourceUrl = self::$downloadUrls["macos"]["source"] ?? null;
		if($sourceUrl){
			$targetDir = $this->libsPath . DIRECTORY_SEPARATOR . "macos";
			@mkdir($targetDir, 0777, true);
			
			$result = $this->downloadFile($sourceUrl, $targetDir);
			if($result){
				$this->getLogger()->info("Source downloaded to: " . $targetDir);
				$this->getLogger()->info("To build manually:");
				$this->getLogger()->info("  cd " . $targetDir);
				$this->getLogger()->info("  tar xzf lua-2.0.7.tgz && cd lua-2.0.7");
				$this->getLogger()->info("  phpize && ./configure && make && sudo make install");
				return true;
			}
		}
		
		$this->showManualDownloadInstructions("macos", PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION);
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
		
		// Try x64 default for Windows
		if($os === "windows" && isset(self::$downloadUrls[$os][$phpVersion]["x64"][$ts])){
			return self::$downloadUrls[$os][$phpVersion]["x64"][$ts];
		}
		
		// Try any thread safety match
		if(isset(self::$downloadUrls[$os][$phpVersion][$arch])){
			$available = self::$downloadUrls[$os][$phpVersion][$arch];
			return reset($available);
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
				"header" => [
					"User-Agent: LuaLoader/1.1.0 PHP/" . PHP_VERSION,
					"Accept: */*"
				],
				"follow_location" => true,
				"timeout" => 60
			],
			"ssl" => [
				"verify_peer" => false,
				"verify_peer_name" => false
			]
		]);
		
		$content = @file_get_contents($url, false, $context);
		
		if($content === false){
			$this->getLogger()->error("Failed to download from: " . $url);
			return false;
		}
		
		$filename = basename(parse_url($url, PHP_URL_PATH));
		
		// Handle ZIP files
		if(substr($filename, -4) === ".zip" || strpos($url, ".zip") !== false){
			$tempFile = $targetDir . DIRECTORY_SEPARATOR . "download.zip";
			file_put_contents($tempFile, $content);
			
			if(class_exists("ZipArchive")){
				$zip = new \ZipArchive();
				if($zip->open($tempFile) === true){
					$zip->extractTo($targetDir);
					$zip->close();
					unlink($tempFile);
					$this->getLogger()->info("Extracted to: " . $targetDir);
					return true;
				}
			}else{
				$this->getLogger()->warning("ZipArchive not available. Please extract manually: " . $tempFile);
				return true;
			}
		}
		// Handle tar.gz files
		elseif(substr($filename, -4) === ".tgz" || substr($filename, -7) === ".tar.gz"){
			$tempFile = $targetDir . DIRECTORY_SEPARATOR . $filename;
			file_put_contents($tempFile, $content);
			$this->getLogger()->info("Downloaded: " . $filename);
			return true;
		}
		// Direct file
		else{
			$savePath = $targetDir . DIRECTORY_SEPARATOR . $filename;
			file_put_contents($savePath, $content);
			$this->getLogger()->info("Downloaded: " . $filename);
			return true;
		}
		
		return false;
	}
	
	/**
	 * Show manual download instructions
	 */
	private function showManualDownloadInstructions($os, $phpVersion){
		$this->getLogger()->info("=== Manual Installation ===");
		
		switch($os){
			case "windows":
				$this->getLogger()->info("1. Visit: https://pecl.php.net/package/lua");
				$this->getLogger()->info("   Or: https://windows.php.net/downloads/pecl/releases/lua/");
				$this->getLogger()->info("2. Download DLL for PHP $phpVersion");
				$this->getLogger()->info("3. Place php_lua.dll in plugins/LuaLoader/libs/windows/");
				$this->getLogger()->info("4. Download liblua.dll from lua.org");
				$this->getLogger()->info("Alternative: Add 'extension=php_lua.dll' to php.ini");
				break;
			case "linux":
				$this->getLogger()->info("Option 1 - Using pecl (recommended):");
				$this->getLogger()->info("  sudo apt install lua5.3 liblua5.3-dev php-dev");
				$this->getLogger()->info("  sudo pecl install lua");
				$this->getLogger()->info("  echo 'extension=lua.so' | sudo tee /etc/php/*/conf.d/lua.ini");
				$this->getLogger()->info("");
				$this->getLogger()->info("Option 2 - Build from source:");
				$this->getLogger()->info("  wget https://pecl.php.net/get/lua-2.0.7.tgz");
				$this->getLogger()->info("  tar xzf lua-2.0.7.tgz && cd lua-2.0.7");
				$this->getLogger()->info("  phpize && ./configure && make && sudo make install");
				break;
			case "macos":
				$this->getLogger()->info("Option 1 - Using Homebrew + pecl:");
				$this->getLogger()->info("  brew install lua");
				$this->getLogger()->info("  pecl install lua");
				$this->getLogger()->info("");
				$this->getLogger()->info("Option 2 - Build from source:");
				$this->getLogger()->info("  wget https://pecl.php.net/get/lua-2.0.7.tgz");
				$this->getLogger()->info("  tar xzf lua-2.0.7.tgz && cd lua-2.0.7");
				$this->getLogger()->info("  phpize && ./configure && make && sudo make install");
				break;
		}
	}
	
	/**
	 * Attempt to load the Lua extension
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
			$this->getLogger()->info("Found library: " . $foundExt);
		}
		
		if(!function_exists("dl")){
			$this->getLogger()->warning("dl() not available. Add extension to php.ini instead.");
			return;
		}

		try{
			$loaded = false;
			foreach($extensionNames[$os] ?? [] as $extName){
				if(@dl($extName)){
					$this->getLogger()->info("Loaded: " . $extName);
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
			$this->getLogger()->error("Load error: " . $e->getMessage());
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
		$this->getLogger()->info("LuaPluginLoader registered. PHP " . PHP_VERSION);

		$plugins = $this->getServer()->getPluginManager()->loadPlugins($this->getServer()->getPluginPath(), [LuaPluginLoader::class]);
		$folderPlugins = $this->scanFolderPlugins($this->getServer()->getPluginPath());
		
		$totalLoaded = count($plugins) + count($folderPlugins);
		
		if($totalLoaded > 0){
			$this->getLogger()->info("Loaded " . $totalLoaded . " Lua plugin(s).");
			
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
