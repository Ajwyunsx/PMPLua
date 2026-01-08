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
			// PHP 7.0 and 7.1 use lua 2.0.3 (older version)
			"7.0" => [
				"x64" => [
					"ts" => "http://windows.php.net/downloads/pecl/releases/lua/2.0.3/php_lua-2.0.3-7.0-ts-vc14-x64.zip",
					"nts" => "http://windows.php.net/downloads/pecl/releases/lua/2.0.3/php_lua-2.0.3-7.0-nts-vc14-x64.zip"
				],
				"x86" => [
					"ts" => "http://windows.php.net/downloads/pecl/releases/lua/2.0.3/php_lua-2.0.3-7.0-ts-vc14-x86.zip",
					"nts" => "http://windows.php.net/downloads/pecl/releases/lua/2.0.3/php_lua-2.0.3-7.0-nts-vc14-x86.zip"
				]
			],
			"7.1" => [
				"x64" => [
					"ts" => "http://windows.php.net/downloads/pecl/releases/lua/2.0.3/php_lua-2.0.3-7.1-ts-vc14-x64.zip",
					"nts" => "http://windows.php.net/downloads/pecl/releases/lua/2.0.3/php_lua-2.0.3-7.1-nts-vc14-x64.zip"
				],
				"x86" => [
					"ts" => "http://windows.php.net/downloads/pecl/releases/lua/2.0.3/php_lua-2.0.3-7.1-ts-vc14-x86.zip",
					"nts" => "http://windows.php.net/downloads/pecl/releases/lua/2.0.3/php_lua-2.0.3-7.1-nts-vc14-x86.zip"
				]
			],
			// PHP 7.2+ use lua 2.0.7
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
		$this->getLogger()->info("Connecting to: " . parse_url($url, PHP_URL_HOST));
		
		// Create context with better options
		$context = stream_context_create([
			"http" => [
				"method" => "GET",
				"header" => [
					"User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) LuaLoader/1.1",
					"Accept: */*",
					"Connection: close"
				],
				"follow_location" => true,
				"max_redirects" => 5,
				"timeout" => 120,
				"ignore_errors" => false
			],
			"ssl" => [
				"verify_peer" => false,
				"verify_peer_name" => false,
				"allow_self_signed" => true
			]
		]);
		
		// Try to download
		$content = @file_get_contents($url, false, $context);
		
		if($content === false){
			$error = error_get_last();
			$this->getLogger()->error("Download failed: " . ($error['message'] ?? 'Unknown error'));
			
			// Try with cURL if available
			if(function_exists('curl_init')){
				$this->getLogger()->info("Trying cURL fallback...");
				$content = $this->downloadWithCurl($url);
			}
		}
		
		if($content === false || strlen($content) < 1000){
			$this->getLogger()->error("Download incomplete or failed. Size: " . ($content ? strlen($content) : 0));
			return false;
		}
		
		$this->getLogger()->info("Downloaded " . number_format(strlen($content)) . " bytes");
		
		$filename = basename(parse_url($url, PHP_URL_PATH));
		
		// Handle ZIP files
		if(strpos($filename, ".zip") !== false || strpos($url, ".zip") !== false){
			$tempFile = $targetDir . DIRECTORY_SEPARATOR . "download.zip";
			$written = file_put_contents($tempFile, $content);
			
			if($written === false){
				$this->getLogger()->error("Failed to write ZIP file");
				return false;
			}
			
			$this->getLogger()->info("Saved ZIP: " . $tempFile . " (" . $written . " bytes)");
			
			if(class_exists("ZipArchive")){
				$zip = new \ZipArchive();
				$openResult = $zip->open($tempFile);
				
				if($openResult === true){
					$this->getLogger()->info("Extracting " . $zip->numFiles . " files...");
					
					// Extract all files
					$zip->extractTo($targetDir);
					$zip->close();
					
					// List extracted files
					$files = glob($targetDir . DIRECTORY_SEPARATOR . "*.dll");
					foreach($files as $f){
						$this->getLogger()->info("Extracted: " . basename($f));
					}
					
					@unlink($tempFile);
					return true;
				}else{
					$this->getLogger()->error("Failed to open ZIP. Error code: " . $openResult);
					return false;
				}
			}else{
				$this->getLogger()->warning("ZipArchive not available. Manual extract: " . $tempFile);
				return true;
			}
		}
		
		// Non-ZIP file
		$savePath = $targetDir . DIRECTORY_SEPARATOR . $filename;
		file_put_contents($savePath, $content);
		$this->getLogger()->info("Saved: " . $filename);
		return true;
	}
	
	/**
	 * Fallback download using cURL
	 */
	private function downloadWithCurl($url){
		$ch = curl_init();
		curl_setopt_array($ch, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_MAXREDIRS => 5,
			CURLOPT_TIMEOUT => 120,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_USERAGENT => 'Mozilla/5.0 LuaLoader/1.1',
			CURLOPT_HTTPHEADER => ['Accept: */*']
		]);
		
		$content = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = curl_error($ch);
		curl_close($ch);
		
		if($content !== false && $httpCode === 200){
			$this->getLogger()->info("cURL success! HTTP " . $httpCode);
			return $content;
		}
		
		$this->getLogger()->error("cURL failed: HTTP $httpCode - $error");
		return false;
	}
	
	private function loadLuaExtension(){
		$os = $this->getOS();
		$pluginDir = dirname(dirname(dirname(__FILE__)));
		
		$extensionNames = [
			"windows" => ["php_lua.dll"],
			"linux" => ["lua.so"],
			"macos" => ["lua.so"]
		];
		
		$dependencyNames = [
			"windows" => ["liblua.dll"],
			"linux" => [],
			"macos" => []
		];
		
		// Find DLLs in plugin folder
		$searchPaths = [
			$pluginDir . DIRECTORY_SEPARATOR . "libs" . DIRECTORY_SEPARATOR . $os,
			$pluginDir . DIRECTORY_SEPARATOR . "libs",
			$pluginDir,
		];
		
		$foundExtPath = null;
		$foundDepPaths = [];
		
		foreach($searchPaths as $searchPath){
			if(!is_dir($searchPath)) continue;
			
			// Find extension DLL
			if($foundExtPath === null){
				foreach($extensionNames[$os] ?? [] as $extName){
					$extPath = $searchPath . DIRECTORY_SEPARATOR . $extName;
					if(file_exists($extPath)){
						$foundExtPath = $extPath;
						$this->getLogger()->info("Found extension: " . $extPath);
					}
				}
			}
			
			// Find dependency DLLs
			foreach($dependencyNames[$os] ?? [] as $depName){
				$depPath = $searchPath . DIRECTORY_SEPARATOR . $depName;
				if(file_exists($depPath) && !isset($foundDepPaths[$depName])){
					$foundDepPaths[$depName] = $depPath;
					$this->getLogger()->info("Found dependency: " . $depPath);
				}
			}
		}
		
		if($foundExtPath === null){
			$this->getLogger()->warning("Extension DLL not found in plugin folder");
			return;
		}
		
		// Get PHP directories
		$phpExtDir = ini_get("extension_dir");
		$phpBinDir = dirname(PHP_BINARY);
		
		$this->getLogger()->info("PHP ext dir: " . $phpExtDir);
		$this->getLogger()->info("PHP bin dir: " . $phpBinDir);
		
		// Copy extension DLL to PHP extension directory
		$extName = basename($foundExtPath);
		$targetExtPath = $phpExtDir . DIRECTORY_SEPARATOR . $extName;
		
		if(!file_exists($targetExtPath) || filesize($targetExtPath) !== filesize($foundExtPath)){
			$this->getLogger()->info("Copying $extName to PHP ext dir...");
			if(@copy($foundExtPath, $targetExtPath)){
				$this->getLogger()->info("Copied: " . $targetExtPath);
			}else{
				$this->getLogger()->warning("Failed to copy to ext dir. Trying bin dir...");
				// Try PHP bin directory as fallback
				$targetExtPath = $phpBinDir . DIRECTORY_SEPARATOR . $extName;
				if(@copy($foundExtPath, $targetExtPath)){
					$this->getLogger()->info("Copied to bin dir: " . $targetExtPath);
				}
			}
		}
		
		// Copy dependency DLLs to PHP binary directory (for Windows PATH)
		foreach($foundDepPaths as $depName => $depPath){
			$targetDepPath = $phpBinDir . DIRECTORY_SEPARATOR . $depName;
			if(!file_exists($targetDepPath) || filesize($targetDepPath) !== filesize($depPath)){
				$this->getLogger()->info("Copying $depName to PHP bin dir...");
				if(@copy($depPath, $targetDepPath)){
					$this->getLogger()->info("Copied: " . $targetDepPath);
				}else{
					$this->getLogger()->warning("Failed to copy $depName");
				}
			}
		}
		
		// Try to load using dl()
		if(!function_exists("dl")){
			$this->getLogger()->warning("dl() not available. Please add 'extension=$extName' to php.ini");
			$this->getLogger()->warning("Then restart the server.");
			return;
		}

		foreach($extensionNames[$os] ?? [] as $extName){
			$this->getLogger()->info("Trying to load: " . $extName);
			if(@dl($extName)){
				$this->getLogger()->info("SUCCESS! Loaded: " . $extName);
				return;
			}
		}
		
		// If dl() failed, advise user
		$this->getLogger()->warning("Could not load extension dynamically.");
		$this->getLogger()->warning("DLLs have been copied. Please RESTART the server.");
		$this->getLogger()->info("If still not working, add to php.ini: extension=$extName");
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
