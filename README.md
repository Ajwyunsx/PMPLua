# PMPLua Plugin for Genisys/PocketMine

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

A plugin that enables Lua scripting support for Genisys/PocketMine-MP servers.

**Contributors:** [Ajwyunsx](https://github.com/Ajwyunsx) & [gemini-code-assist](https://github.com/gemini-code-assist)

## Features

- **Lua Script Plugins**: Write plugins in Lua instead of PHP
- **Single File Plugins**: Place `.lua` files directly in the plugins folder
- **Folder Plugins**: Create plugin folders with `plugin.lua` or `main.lua`
- **Module Support**: Use `require()` to load Lua modules
- **Hot Reload**: Reload Lua scripts without restarting the server
- **Cross-Platform**: Windows, Linux, and macOS support
- **Comprehensive API**: 50+ functions for server, player, world, items, config, and more

## Installation

### Windows
1. Copy `php_lua.dll` and `liblua.dll` to `plugins/LuaLoader/libs/windows/`
2. Alternatively, add `extension=php_lua.dll` to `php.ini`

### Linux
```bash
# Install Lua
sudo apt install lua5.3 liblua5.3-dev

# Install PHP Lua extension
pecl install lua

# Add to php.ini
echo "extension=lua.so" >> /path/to/php.ini
```

### macOS
```bash
brew install lua
pecl install lua
```

## Plugin Structure

### Single File Plugin
```
plugins/
  MyPlugin.lua
```

### Folder Plugin
```
plugins/
  MyPlugin/
    plugin.lua    # or main.lua
    lib/
      utils.lua
    data/
      config.yml
```

## Lua Script Format

```lua
-- @name MyPlugin
-- @version 1.0.0
-- @author YourName
-- @description A description of your plugin

function onEnable()
    logInfo("Plugin enabled!")
end

function onDisable()
    logInfo("Plugin disabled!")
end

function onCommand(sender, commandName, label, args)
    if commandName == "mycommand" then
        sendMessage(sender, "Hello!")
        return true
    end
    return false
end
```

## Commands

| Command | Description | Permission |
|---------|-------------|------------|
| `/luareload <name\|all>` | Reload Lua plugin(s) | `lualoader.reload` |

## API Reference

### Logger
- `logInfo(msg)` - Log info message
- `logWarning(msg)` - Log warning
- `logError(msg)` - Log error
- `logDebug(msg)` - Log debug

### Server
- `getServerName()` - Get server name
- `getServerMotd()` / `setServerMotd(motd)` - Get/set MOTD
- `broadcastMessage(msg)` - Broadcast to all players
- `broadcastTip(msg)` / `broadcastPopup(msg)` - Broadcast tip/popup
- `getMaxPlayers()` / `getOnlineCount()` - Player counts
- `getTPS()` / `getTickUsage()` - Server performance
- `dispatchCommand(cmd)` - Execute console command

### Player
- `getOnlinePlayers()` - Get list of player names
- `getPlayer(name)` / `getPlayerExact(name)` - Get player
- `sendMessage(player, msg)` - Send message
- `sendTip(player, msg)` / `sendPopup(player, msg)` - Send tip/popup
- `kickPlayer(player, reason)` - Kick player
- `getPlayerPosition(player)` - Get position {x, y, z, level}
- `teleportPlayer(player, x, y, z, level)` - Teleport
- `getPlayerHealth(player)` / `setPlayerHealth(player, hp)` - Health
- `getPlayerGamemode(player)` / `setPlayerGamemode(player, mode)` - Gamemode
- `giveItem(player, itemId, count, meta)` - Give item
- `isOp(player)` - Check if OP

### World
- `getLevels()` - Get all level names
- `getDefaultLevel()` - Get default level
- `loadLevel(name)` / `unloadLevel(name)` - Load/unload level
- `getBlock(level, x, y, z)` - Get block {id, meta, name}
- `setBlock(level, x, y, z, blockId, meta)` - Set block
- `getLevelTime(level)` / `setLevelTime(level, time)` - Level time

### Config/File
- `getDataFolder()` - Get plugin data folder
- `createDataFolder()` - Create data folder
- `readFile(name)` / `writeFile(name, content)` - Read/write files
- `fileExists(name)` / `deleteFile(name)` - File operations
- `loadConfig(name)` / `saveConfig(name, data)` - YAML config
- `loadJson(name)` / `saveJson(name, data)` - JSON files

### Utility
- `time()` / `microtime()` - Timestamps
- `date(format, timestamp)` - Date formatting
- `random(min, max)` - Random number
- `getOS()` - Get OS type (windows/linux/macos)
- `jsonEncode(data)` / `jsonDecode(json)` - JSON
- `strContains(str, needle)` / `strReplace(search, replace, str)` - Strings
- `tableCount(table)` / `tableContains(table, value)` - Tables

### Module
- `require(moduleName)` - Load Lua module
- `dofile(filename)` - Execute Lua file
- `loadfile(filename)` - Load file content
- `getPluginFolder()` - Get plugin folder path
- `getScriptPath()` - Get main script path
- `reloadScript()` - Reload this plugin

## License

MIT License
