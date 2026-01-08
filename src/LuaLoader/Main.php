<?php

namespace LuaLoader;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class Main extends PluginBase{
	
	/** @var string Path to the libs folder */
	private $libsPath;
	
	/**
	 * Download URLs for php_lua from official PECL/PHP.net
	 * Source: https://pecl.php.net/package/lua/2.0.7/windows
	 */
	private static $downloadUrls = [
		"windows" => [
			"7.2" => [
				"x64" => [
					"ts" => "https://downloads.php.net/~windows/pecl/releases/lua/2.0.7/php_lua-2.0.7-7.2-ts-vc15-x64.zip",
					"nts" => "https://downloads.php.net/~windows/pecl/releases/lua/2.0.7/php_lua-2.0.7-7.2-nts-vc15-x64.zip"
				],
				"x86" => [
					"ts" => "https://downloads.php.net/~windows/pecl/releases/lua/2.0.7/php_lua-2.0.7-7.2-ts-vc15-x86.zip",
					"nts" => "https://downloads.php.net/~windows/pecl/releases/lua/2.0.7/php_lua-2.0.7-7.2-nts-vc15-x86.zip"
				]
			],
			"7.3" => [
				"x64" => [
					"ts" => "https://downloads.php.net/~windows/pecl/releases/lua/2.0.7/php_lua-2.0.7-7.3-ts-vc15-x64.zip",
					"nts" => "https://downloads.php.net/~windows/pecl/releases/lua/2.0.7/php_lua-2.0.7-7.3-nts-vc15-x64.zip"
				],
				"x86" => [
					"ts" => "https://downloads.php.net/~windows/pecl/releases/lua/2.0.7/php_lua-2.0.7-7.3-ts-vc15-x86.zip",
					"nts" => "https://downloads.php.net/~windows/pecl/releases/lua/2.0.7/php_lua-2.0.7-7.3-nts-vc15-x86.zip"
				]
			],
			"7.4" => [
				"x64" => [
					"ts" => "https://downloads.php.net/~windows/pecl/releases/lua/2.0.7/php_lua-2.0.7-7.4-ts-vc15-x64.zip",
					"nts" => "https://downloads.php.net/~windows/pecl/releases/lua/2.0.7/php_lua-2.0.7-7.4-nts-vc15-x64.zip"
				],
				"x86" => [
					"ts" => "https://downloads.php.net/~windows/pecl/releases/lua/2.0.7/php_lua-2.0.7-7.4-ts-vc15-x86.zip",
					"nts" => "https://downloads.php.net/~windows/pecl/releases/lua/2.0.7/php_lua-2.0.7-7.4-nts-vc15-x86.zip"
				]
			],
			"8.0" => [
				"x64" => [
					"ts" => "https://downloads.php.net/~windows/pecl/releases/lua/2.0.7/php_lua-2.0.7-8.0-ts-vs16-x64.zip",
					"nts" => "https://downloads.php.net/~windows/pecl/releases/lua/2.0.7/php_lua-2.0.7-8.0-nts-vs16-x64.zip"
				],
				"x86" => [
					"ts" => "https://downloads.php.net/~windows/pecl/releases/lua/2.0.7/php_lua-2.0.7-8.0-ts-vs16-x86.zip",
					"nts" => "https://downloads.php.net/~windows/pecl/releases/lua/2.0.7/php_lua-2.0.7-8.0-nts-vs16-x86.zip"
				]
			],
			"8.1" => [
				"x64" => [
					"ts" => "https://downloads.php.net/~windows/pecl/releases/lua/2.0.7/php_lua-2.0.7-8.1-ts-vs16-x64.zip",
					"nts" => "https://downloads.php.net/~windows/pecl/releases/lua/2.0.7/php_lua-2.0.7-8.1-nts-vs16-x64.zip"
				],
				"x86" => [
					"ts" => "https://downloads.php.net/~windows/pecl/releases/lua/2.0.7/php_lua-2.0.7-8.1-ts-vs16-x86.zip",
					"nts" => "https://downloads.php.net/~windows/pecl/releases/lua/2.0.7/php_lua-2.0.7-8.1-nts-vs16-x86.zip"
				]
			],
			"8.2" => [
				"x64" => [
					"ts" => "https://downloads.php.net/~windows/pecl/releases/lua/2.0.7/php_lua-2.0.7-8.2-ts-vs16-x64.zip",
					"nts" => "https://downloads.php.net/~windows/pecl/releases/lua/2.0.7/php_lua-2.0.7-8.2-nts-vs16-x64.zip"
				],
				"x86" => [
					"ts" => "https://downloads.php.net/~windows/pecl/releases/lua/2.0.7/php_lua-2.0.7-8.2-ts-vs16-x86.zip",
					"nts" => "https://downloads.php.net/~windows/pecl/releases/lua/2.0.7/php_lua-2.0.7-8.2-nts-vs16-x86.zip"
				]
			],
			"8.3" => [
				"x64" => [
					"ts" => "https://downloads.php.net/~windows/pecl/releases/lua/2.0.7/php_lua-2.0.7-8.3-ts-vs16-x64.zip",
					"nts" => "https://downloads.php.net/~windows/pecl/releases/lua/2.0.7/php_lua-2.0.7-8.3-nts-vs16-x64.zip"
				],
				"x86" => [
					"ts" => "https://downloads.php.net/~windows/pecl/releases/lua/2.0.7/php_lua-2.0.7-8.3-ts-vs16-x86.zip",
					"nts" => "https://downloads.php.net/~windows/pecl/releases/lua/2.0.7/php_lua-2.0.7-8.3-nts-vs16-x86.zip"
				]
			],
			"8.4" => [
				"x64" => [
					"ts" => "https://downloads.php.net/~windows/pecl/releases/lua/2.0.7/php_lua-2.0.7-8.4-ts-vs17-x64.zip",
					"nts" => "https://downloads.php.net/~windows/pecl/releases/lua/2.0.7/php_lua-2.0.7-8.4-nts-vs17-x64.zip"
				],
				"x86" => [
					"ts" => "https://downloads.php.net/~windows/pecl/releases/lua/2.0.7/php_lua-2.0.7-8.4-ts-vs17-x86.zip",
					"nts" => "https://downloads.php.net/~windows/pecl/releases/lua/2.0.7/php_lua-2.0.7-8.4-nts-vs17-x86.zip"
				]
			],
			"8.5" => [
				"x64" => [
					"ts" => "https://downloads.php.net/~windows/pecl/releases/lua/2.0.7/php_lua-2.0.7-8.5-ts-vs17-x64.zip",
					"nts" => "https://downloads.php.net/~windows/pecl/releases/lua/2.0.7/php_lua-2.0.7-8.5-nts-vs17-x64.zip"
				],
				"x86" => [
					"ts" => "https://downloads.php.net/~windows/pecl/releases/lua/2.0.7/php_lua-2.0.7-8.5-ts-vs17-x86.zip",
					"nts" => "https://downloads.php.net/~windows/pecl/releases/lua/2.0.7/php_lua-2.0.7-8.5-nts-vs17-x86.zip"
				]
			]
		],
		"linux" => [
			"source" => "https://pecl.php.net/get/lua-2.0.7.tgz"
		],
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
				$this->getLogger()->info("Libraries not found. Auto-downloading...");
				$this->autoDownloadLibrary();
			}
			
			$this->loadLuaExtension();
		}else{
			$this->getLogger()->info("Lua extension already loaded.");
		}
	}
	
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
	
	private function autoDownloadLibrary(){
		$os = $this->getOS();
		$phpVersion = PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;
		$arch = PHP_INT_SIZE === 8 ? "x64" : "x86";
		$ts = PHP_ZTS ? "ts" : "nts";
		
		$this->getLogger()->info("=== Auto-Download ===");
		$this->getLogger()->info("PHP: $phpVersion | Arch: $arch | TS: $ts | OS: $os");
		
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
	
	private function downloadWindowsLibrary($phpVersion, $arch, $ts){
		$url = $this->getDownloadUrl("windows", $phpVersion, $arch, $ts);
		
		if($url === null){
			$this->getLogger()->warning("No DLL for PHP $phpVersion ($arch, $ts)");
			$this->getLogger()->info("Download manually: https://pecl.php.net/package/lua/2.0.7/windows");
			return false;
		}
		
		$this->getLogger()->info("Downloading: " . $url);
		
		$targetDir = $this->libsPath . DIRECTORY_SEPARATOR . "windows";
		@mkdir($targetDir, 0777, true);
		
		try {
			if($this->downloadFile($url, $targetDir)){
				$this->getLogger()->info("Download complete!");
				return true;
			}
		} catch(\Throwable $e){
			$this->getLogger()->error("Download failed: " . $e->getMessage());
		}
		
		$this->getLogger()->info("Manual download: https://pecl.php.net/package/lua/2.0.7/windows");
		return false;
	}
	
	private function installLinuxLibrary(){
		$this->getLogger()->info("=== Linux Install ===");
		
		$peclPath = trim(shell_exec("which pecl 2>/dev/null") ?? "");
		
		if(!empty($peclPath)){
			$this->getLogger()->info("Found pecl. Running: pecl install lua");
			
			$output = [];
			exec("pecl install lua 2>&1", $output, $returnCode);
			
			if($returnCode === 0){
				$this->getLogger()->info("Success! Add 'extension=lua.so' to php.ini");
				return true;
			}
			$this->getLogger()->warning("pecl failed. Try: sudo pecl install lua");
		}
		
		$sourceUrl = self::$downloadUrls["linux"]["source"] ?? null;
		if($sourceUrl){
			$targetDir = $this->libsPath . DIRECTORY_SEPARATOR . "linux";
			@mkdir($targetDir, 0777, true);
			
			if($this->downloadFile($sourceUrl, $targetDir)){
				$this->getLogger()->info("Source downloaded. Build with:");
				$this->getLogger()->info("  cd $targetDir && tar xzf lua-2.0.7.tgz && cd lua-2.0.7");
				$this->getLogger()->info("  phpize && ./configure && make && sudo make install");
				return true;
			}
		}
		
		$this->getLogger()->info("Manual: sudo pecl install lua");
		return false;
	}
	
	private function installMacOSLibrary(){
		$this->getLogger()->info("=== macOS Install ===");
		
		// Check brew
		exec("brew --prefix lua 2>&1", $brewOutput, $brewCode);
		if($brewCode !== 0){
			$this->getLogger()->info("Installing Lua via Homebrew...");
			exec("brew install lua 2>&1");
		}
		
		$peclPath = trim(shell_exec("which pecl 2>/dev/null") ?? "");
		
		if(!empty($peclPath)){
			$this->getLogger()->info("Running: pecl install lua");
			
			$output = [];
			exec("pecl install lua 2>&1", $output, $returnCode);
			
			if($returnCode === 0){
				$this->getLogger()->info("Success! Add 'extension=lua.so' to php.ini");
				return true;
			}
		}
		
		$sourceUrl = self::$downloadUrls["macos"]["source"] ?? null;
		if($sourceUrl){
			$targetDir = $this->libsPath . DIRECTORY_SEPARATOR . "macos";
			@mkdir($targetDir, 0777, true);
			
			if($this->downloadFile($sourceUrl, $targetDir)){
				$this->getLogger()->info("Source downloaded. Build manually.");
				return true;
			}
		}
		
		$this->getLogger()->info("Manual: brew install lua && pecl install lua");
		return false;
	}
	
	private function getDownloadUrl($os, $phpVersion, $arch, $ts){
		if(isset(self::$downloadUrls[$os][$phpVersion][$arch][$ts])){
			return self::$downloadUrls[$os][$phpVersion][$arch][$ts];
		}
		if(isset(self::$downloadUrls[$os][$phpVersion]["x64"][$ts])){
			return self::$downloadUrls[$os][$phpVersion]["x64"][$ts];
		}
		if(isset(self::$downloadUrls[$os][$phpVersion][$arch])){
			return reset(self::$downloadUrls[$os][$phpVersion][$arch]);
		}
		return null;
	}
	
	private function downloadFile($url, $targetDir){
		$context = stream_context_create([
			"http" => [
				"method" => "GET",
				"header" => "User-Agent: LuaLoader/1.1\r\n",
				"follow_location" => true,
				"timeout" => 60
			],
			"ssl" => ["verify_peer" => false, "verify_peer_name" => false]
		]);
		
		$content = @file_get_contents($url, false, $context);
		if($content === false){
			return false;
		}
		
		$filename = basename(parse_url($url, PHP_URL_PATH));
		
		if(strpos($filename, ".zip") !== false){
			$tempFile = $targetDir . DIRECTORY_SEPARATOR . "download.zip";
			file_put_contents($tempFile, $content);
			
			if(class_exists("ZipArchive")){
				$zip = new \ZipArchive();
				if($zip->open($tempFile) === true){
					$zip->extractTo($targetDir);
					$zip->close();
					unlink($tempFile);
					return true;
				}
			}
			return true;
		}
		
		file_put_contents($targetDir . DIRECTORY_SEPARATOR . $filename, $content);
		return true;
	}
	
	private function loadLuaExtension(){
		$os = $this->getOS();
		$pluginDir = dirname(dirname(dirname(__FILE__)));
		
		$extensionNames = [
			"windows" => ["php_lua.dll"],
			"linux" => ["lua.so"],
			"macos" => ["lua.so"]
		];
		
		$searchPaths = [
			$pluginDir . DIRECTORY_SEPARATOR . "libs" . DIRECTORY_SEPARATOR . $os,
			$pluginDir . DIRECTORY_SEPARATOR . "libs",
			$pluginDir,
		];
		
		foreach($searchPaths as $searchPath){
			if(!is_dir($searchPath)) continue;
			foreach($extensionNames[$os] ?? [] as $extName){
				$extPath = $searchPath . DIRECTORY_SEPARATOR . $extName;
				if(file_exists($extPath)){
					$this->getLogger()->info("Found: " . $extPath);
					break 2;
				}
			}
		}

		if(!function_exists("dl")) return;

		foreach($extensionNames[$os] ?? [] as $extName){
			if(@dl($extName)){
				$this->getLogger()->info("Loaded: " . $extName);
				return;
			}
		}
	}
	
	private function getOS(){
		if(stripos(PHP_OS, 'WIN') === 0) return "windows";
		if(stripos(PHP_OS, 'DARWIN') === 0) return "macos";
		return "linux";
	}

	public function onEnable(){
		if(!class_exists("Lua", false)){
			$this->getLogger()->warning("Lua extension NOT loaded.");
			return;
		}
		
		$this->getServer()->getPluginManager()->registerInterface(LuaPluginLoader::class);
		$this->getLogger()->info("LuaPluginLoader registered. PHP " . PHP_VERSION);

		$plugins = $this->getServer()->getPluginManager()->loadPlugins($this->getServer()->getPluginPath(), [LuaPluginLoader::class]);
		$folderPlugins = $this->scanFolderPlugins($this->getServer()->getPluginPath());
		
		$total = count($plugins) + count($folderPlugins);
		if($total > 0){
			$this->getLogger()->info("Loaded $total Lua plugin(s).");
			foreach($plugins as $p){ if(!$p->isEnabled()) $this->getServer()->getPluginManager()->enablePlugin($p); }
			foreach($folderPlugins as $p){ if(!$p->isEnabled()) $this->getServer()->getPluginManager()->enablePlugin($p); }
		}
	}
	
	private function scanFolderPlugins($path){
		$plugins = [];
		$loader = new LuaPluginLoader($this->getServer());
		
		foreach(scandir($path) as $file){
			if($file === "." || $file === "..") continue;
			$fullPath = $path . DIRECTORY_SEPARATOR . $file;
			if(is_dir($fullPath) && LuaPluginLoader::isLuaPluginFolder($fullPath)){
				try {
					$plugin = $loader->loadPlugin($fullPath);
					if($plugin !== null) $plugins[] = $plugin;
				} catch(\Throwable $e){}
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
				$sender->sendMessage("§e/luareload <name|all>");
				return true;
			}
			
			$target = $args[0];
			if(strtolower($target) === "all"){
				$count = 0;
				foreach($this->getServer()->getPluginManager()->getPlugins() as $p){
					if($p instanceof LuaPlugin){ $p->reloadScript(); $count++; }
				}
				$sender->sendMessage("§aReloaded $count plugin(s).");
			}else{
				$p = $this->getServer()->getPluginManager()->getPlugin($target);
				if($p instanceof LuaPlugin){
					$p->reloadScript();
					$sender->sendMessage("§aReloaded: " . $p->getName());
				}else{
					$sender->sendMessage("§cNot found: $target");
				}
			}
			return true;
		}
		return false;
	}
}
