# PMPLua 插件 (Genisys/PocketMine)

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

[English](README.md) | **中文**

为 Genisys/PocketMine-MP 服务器提供 Lua 脚本支持的插件。

**贡献者:** [Ajwyunsx](https://github.com/Ajwyunsx) & [gemini-code-assist](https://github.com/gemini-code-assist)

## 功能特性

- **Lua 脚本插件**: 使用 Lua 代替 PHP 编写插件
- **单文件插件**: 直接将 `.lua` 文件放入 plugins 文件夹
- **文件夹插件**: 创建包含 `plugin.lua` 或 `main.lua` 的插件文件夹
- **模块支持**: 使用 `require()` 加载 Lua 模块
- **热重载**: 无需重启服务器即可重载 Lua 脚本
- **跨平台**: 支持 Windows、Linux 和 macOS
- **自动下载**: 自动下载 php_lua 扩展（支持 PHP 7.0-8.5）
- **丰富 API**: 50+ 个函数，涵盖服务器、玩家、世界、物品、配置等

## 安装

### Windows（自动）
1. 将插件放入 `plugins/LuaLoader/`
2. 启动服务器 - 自动下载并安装 DLL
3. **重启服务器** - 加载扩展

### Windows（手动）
1. 下载 `php_lua.dll` 和 `liblua.dll`
2. 放入 `plugins/LuaLoader/libs/windows/`
3. 或添加 `extension=php_lua.dll` 到 `php.ini`

### Linux
```bash
# 安装 Lua
sudo apt install lua5.3 liblua5.3-dev

# 安装 PHP Lua 扩展
pecl install lua

# 添加到 php.ini
echo "extension=lua.so" >> /path/to/php.ini
```

### macOS
```bash
brew install lua
pecl install lua
```

## 插件结构

### 单文件插件
```
plugins/
  我的插件.lua
```

### 文件夹插件
```
plugins/
  我的插件/
    plugin.lua    # 或 main.lua
    lib/
      utils.lua
    data/
      config.yml
```

## Lua 脚本格式

```lua
-- @name 我的插件
-- @version 1.0.0
-- @author 作者名
-- @description 插件描述

function onEnable()
    logInfo("插件已启用！")
end

function onDisable()
    logInfo("插件已禁用！")
end

function onCommand(sender, commandName, label, args)
    if commandName == "mycommand" then
        sendMessage(sender, "你好！")
        return true
    end
    return false
end
```

## 命令

| 命令 | 描述 | 权限 |
|------|------|------|
| `/luareload <名称\|all>` | 重载 Lua 插件 | `lualoader.reload` |

## API 参考

### 日志
- `logInfo(msg)` - 输出信息日志
- `logWarning(msg)` - 输出警告日志
- `logError(msg)` - 输出错误日志
- `logDebug(msg)` - 输出调试日志

### 服务器
- `getServerName()` - 获取服务器名称
- `getServerMotd()` / `setServerMotd(motd)` - 获取/设置 MOTD
- `broadcastMessage(msg)` - 广播消息
- `broadcastTip(msg)` / `broadcastPopup(msg)` - 广播提示/弹窗
- `getMaxPlayers()` / `getOnlineCount()` - 玩家数量
- `getTPS()` / `getTickUsage()` - 服务器性能
- `dispatchCommand(cmd)` - 执行控制台命令

### 玩家
- `getOnlinePlayers()` - 获取在线玩家列表
- `getPlayer(name)` / `getPlayerExact(name)` - 获取玩家
- `sendMessage(player, msg)` - 发送消息
- `sendTip(player, msg)` / `sendPopup(player, msg)` - 发送提示/弹窗
- `kickPlayer(player, reason)` - 踢出玩家
- `getPlayerPosition(player)` - 获取位置 {x, y, z, level}
- `teleportPlayer(player, x, y, z, level)` - 传送玩家
- `getPlayerHealth(player)` / `setPlayerHealth(player, hp)` - 生命值
- `getPlayerGamemode(player)` / `setPlayerGamemode(player, mode)` - 游戏模式
- `giveItem(player, itemId, count, meta)` - 给予物品
- `isOp(player)` - 检查是否为 OP

### 世界
- `getLevels()` - 获取所有世界名称
- `getDefaultLevel()` - 获取默认世界
- `loadLevel(name)` / `unloadLevel(name)` - 加载/卸载世界
- `getBlock(level, x, y, z)` - 获取方块 {id, meta, name}
- `setBlock(level, x, y, z, blockId, meta)` - 设置方块
- `getLevelTime(level)` / `setLevelTime(level, time)` - 世界时间

### 配置/文件
- `getDataFolder()` - 获取数据文件夹
- `createDataFolder()` - 创建数据文件夹
- `readFile(name)` / `writeFile(name, content)` - 读写文件
- `fileExists(name)` / `deleteFile(name)` - 文件操作
- `loadConfig(name)` / `saveConfig(name, data)` - YAML 配置
- `loadJson(name)` / `saveJson(name, data)` - JSON 文件

### 工具
- `time()` / `microtime()` - 时间戳
- `date(format, timestamp)` - 日期格式化
- `random(min, max)` - 随机数
- `getOS()` - 获取系统类型 (windows/linux/macos)
- `jsonEncode(data)` / `jsonDecode(json)` - JSON 编解码
- `strContains(str, needle)` / `strReplace(search, replace, str)` - 字符串操作
- `tableCount(table)` / `tableContains(table, value)` - 表操作

### 模块
- `require(moduleName)` - 加载 Lua 模块
- `dofile(filename)` - 执行 Lua 文件
- `loadfile(filename)` - 加载文件内容
- `getPluginFolder()` - 获取插件文件夹路径
- `getScriptPath()` - 获取脚本路径
- `reloadScript()` - 重载当前插件

## 许可证

MIT License - [Ajwyunsx](https://github.com/Ajwyunsx) & [gemini-code-assist](https://github.com/gemini-code-assist)
