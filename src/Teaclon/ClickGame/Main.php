<?php

/*                             Copyright (c) 2017-2018 TeaTech All right Reserved.
 *
 *      ████████████  ██████████           ██         ████████  ██           ██████████    ██          ██
 *           ██       ██                 ██  ██       ██        ██          ██        ██   ████        ██
 *           ██       ██                ██    ██      ██        ██          ██        ██   ██  ██      ██
 *           ██       ██████████       ██      ██     ██        ██          ██        ██   ██    ██    ██
 *           ██       ██              ████████████    ██        ██          ██        ██   ██      ██  ██
 *           ██       ██             ██          ██   ██        ██          ██        ██   ██        ████
 *           ██       ██████████    ██            ██  ████████  ██████████   ██████████    ██          ██
**/

namespace Teaclon\ClickGame;

// Basic;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as CL;

// Event;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerInteractEvent;

use pocketmine\entity\Entity;
use pocketmine\entity\Effect;
use pocketmine\math\Vector3;
use pocketmine\level\Position;
use pocketmine\block\Block;
use pocketmine\block\Air;
use pocketmine\block\Glass;
use pocketmine\block\Grass;
use pocketmine\block\Gold;
use pocketmine\block\Quartz;
use pocketmine\item\ItemFactory as ItemF;
use pocketmine\item\Item;




// TODO: 是否仅限WIN10用户;
class Main extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener
{
	const STRING_PRE    = "NewClickGame";                     // 插件名称;
	const NORMAL_PRE    = "§e[§b".self::STRING_PRE."§e] §f";  // 插件称号;
	const BLOCKMARK     = "草方块";                           // 需要点击什么方块才能设置游戏节点;
	const BLOCKMARK_ID  = Block::GRASS;                       // 方块信息;
	const XDISTANCE     = 3;                                  // X轴算法;
	const GYDISTANCE    = 1;                                  // Y轴 + Block::GLASS  算法;
	const QYDISTANCE    = 2;                                  // X轴 + Block::Quartz 算法;
	
	// Config;
	const CONFIG_WIN10_USER_DISABLE           = "禁止WIN10用户使用";
	const CONFIG_PARTICLE_APPLY               = "粒子特效";
	const CONFIG_ADMIN_LIST                   = "管理员名单";
	const CONFIG_DEFAULT_PLAYER_WAITING_TIME  = "玩家默认等待时间";
	const CONFIG_DEFAULT_COUNTDOWN            = "默认倒计时秒";
	const CONFIG_FLOATINGTEXT_LEADERBOARD     = "浮空字排行榜";
	const CONFIG_LEADERBOARD_DISPLAY_PER_PAGE = "排行榜每页最大显示数量";
	const CONFIG_LEADERBOARD_POSITION         = "浮空字排行榜显示坐标";
	// const CONFIG_LEADERBOARD_DISPLAY_FORMAT   = "排行榜显示格式";
	const VECTOR_DIVISION      = "[AB]";
	
	// 其他参数;
	// const DEFAULT_PLAYER_WAITING_TIME = 10; // 玩家默认等待时间;
	
	
	private $station        = null; // 存储场地临时数据的配置文件(object);
	private $config         = null; // 存储场地数据的配置文件(object);
	private $server         = null; // \pocketmine\Server;
	private $setting_cache  = [];   // 使用指令设置场地的临时转存数组;
	private $tsapi          = null; // 用于获取一个玩家的数据;
	private $entityId       = null;
	
	private $default_index  = // 生成一个场地的默认配置文件;
	[
		"name"               => "",
		"creator"            => "",
		"level"              => "",
		"start_point"        => "",
		"block_glass_point"  => "",
		"block_quartz_point" => "",
		"play_time"          => 0,
		"top"                => [],
		"countdown"          => 10 // 默认的游戏回合倒计时;
	];
	
	private $default_temp_station_data =    // 加载默认场地的临时配置;
	[
		"name"      => "",
		"level"     => "",
		"player"    => "",
		"status"    => false,
		"countdown" => 10
	];
	
	private $default_temp_player_data = 
	[
		"name"           => "",    // 玩家名称;
		"waiting_status" => false, // 加入场地之后的等待状态;
		"isJoin"         => false, // 是否在一个场地中进行游戏;
		"station"        => "",    // 场地名称;
		"waiting_time"   => 5,     // 等待时间;
		"clicktime"      => 0,     // 点击的次数;
	];
	
	private $temp_player_data = [];         // 玩家加入场地的临时数据缓存;
	private $temp_station_data = [];        // 加载场地的临时配置;
	public $temp_level_with_station = [];   // 服务器启动时, 通过读取配置文件, 将 data::"level" 到 index::场地名称;
	
	
	/* public function onLoad()
	{
		
	} */
	
	
	public function onEnable()
	{
		$this->server = $this->getServer();
		
		$this->server->getLogger()->info(self::NORMAL_PRE."§d-----------------------------------------");
		$this->server->getLogger()->info(self::NORMAL_PRE."§e已启动插件: §a点击方块§e小游戏");
		$this->server->getLogger()->info(self::NORMAL_PRE."§e作者:       §bTeaclon §f(§6锤子§f)");
		$this->server->getLogger()->info(self::NORMAL_PRE."§e感谢你的使用.");
		$this->server->getLogger()->info(self::NORMAL_PRE."§e指令: §d/§6cl");
		
		if(!is_dir($this->getDataFolder())) mkdir($this->getDataFolder(), 0777, true);
		$this->config  = new Config($this->getDataFolder()."config.yml", Config::YAML, 
		[
			self::CONFIG_WIN10_USER_DISABLE           => false,
			self::CONFIG_PARTICLE_APPLY               => true,
			self::CONFIG_ADMIN_LIST                   => [],
			self::CONFIG_DEFAULT_COUNTDOWN            => $this->default_temp_station_data["countdown"],
			self::CONFIG_DEFAULT_PLAYER_WAITING_TIME  => $this->default_temp_player_data["waiting_time"],
			self::CONFIG_FLOATINGTEXT_LEADERBOARD     => true,
			self::CONFIG_LEADERBOARD_DISPLAY_PER_PAGE => 2,
			self::CONFIG_LEADERBOARD_POSITION         => "",
			// self::CONFIG_LEADERBOARD_DISPLAY_FORMAT  => base64_encode(""),
		]);
		$ALL = $this->config->getAll();
		
		// 判断配置文件的 "游戏场地回合结束默认倒计时" 是否与插件的 "游戏场地回合结束默认倒计时" 相同, 如果不相同则使用配置文件中设定的进行更新;
		$config_default_countdown = $ALL[self::CONFIG_DEFAULT_COUNTDOWN];
		$plugin_default_countdown = $this->default_temp_station_data["countdown"];
		if($config_default_countdown !== $plugin_default_countdown) $this->default_temp_station_data["countdown"] = $config_default_countdown;
		
		// 判断配置文件的 "玩家等待时间默认倒计时" 是否与插件的 "玩家等待时间默认倒计时" 相同, 如果不相同则使用配置文件中设定的进行更新;
		$config_default_player_wating = $ALL[self::CONFIG_DEFAULT_PLAYER_WAITING_TIME];
		$plugin_default_player_wating = $this->default_temp_player_data["waiting_time"];
		if($config_default_player_wating !== $plugin_default_player_wating) $this->default_temp_player_data["waiting_time"] = $config_default_player_wating;
		unset($config_default_countdown, $plugin_default_countdown, $config_default_player_wating, $plugin_default_player_wating, $ALL);
		
		
		if(!$this->server->getPluginManager()->getPlugin("TSeriesAPI"))
		{
			$this->server->getLogger()->info(self::NORMAL_PRE."§c服务器无法找到所依赖的插件, 可能会导致无法启动小游戏场地!");
			$this->server->getLogger()->info(self::NORMAL_PRE."§c本插件已卸载.");
			$this->server->getPluginManager()->disablePlugin($this);
			return null;
		}
		else $this->tsapi = $this->server->getPluginManager()->getPlugin("TSeriesAPI")->setMeEnable($this);
		
		$this->station = new Config($this->getDataFolder()."station.yml", Config::YAML, []); // 用来设置游戏场地的配置文件;
		
		$data = $this->station()->getAll();
		if(count($data) > 0)
		{
			foreach($data as $name => $data)
			{
				$this->temp_level_with_station[$data["level"]][] = $name;
				$this->initTempStation($name, $data["level"]);
			}
		}
		$this->server->getLogger()->info(self::NORMAL_PRE."§a游戏场地加载完成.");
		$this->server->getLogger()->info(self::NORMAL_PRE."§d-----------------------------------------");
		unset($data);
		
		$this->server->getPluginManager()->registerEvents($this, $this);
		
		$this->tsapi->getCommandManager()->registerCommand(new command\MainCommand($this));
		$this->tsapi->getTaskManager()->registerTask("scheduleRepeatingTask", new task\PlayerJoinStationTask($this), 20);
		$this->spawnFloatingText();
	}
	
	public function onDisable()
	{
		$this->server->getLogger()->info(self::NORMAL_PRE."§c本插件已卸载.");
	}
	
	
	
	
	
	
	
	public final function spawnFloatingText()
	{
		if($this->config()->get(self::CONFIG_LEADERBOARD_POSITION) === "") return \false;
		$this->tsapi->getTaskManager()->createCallbackTask($this, "scheduleRepeatingTask", "addFloatingText", [], 20 * 5);
		$this->tsapi->getTaskManager()->createCallbackTask($this, "scheduleDelayedTask", "updateNetworkPacket", [], 20 * 5.99);
	}
	
	
	private $temp_page = ["all_key" => [], "start" => \false, "c" => 0, "current_page" => 0];
	public final function addFloatingText()
    {
		if($this->config()->get(self::CONFIG_LEADERBOARD_POSITION) === "") return \false;
		if(count($this->station()->getAll()) === 0) return \false;
		
		$title_1       = "§f============§f[§f§o§l§eNew§6Cli§dck§bGa§ame§r§o§2排§6行§a榜§r§f]§f===========§r";
		$title_2       = "          §a♧§c⚔§e⚒§f>场地 §3{venues} §f[§c{type}§f]<§e⚒§c⚔§a♧§r";
		$ranking       = "  - № §e第§a{val}§e名§f: {pn}  §e>>  §6{countdown}§bs内点击§c{clicktime}§b次§r";
		$end           = "§f================--[§e{current}§d/§b{total}§f页]--================§r";
		$line_break    = "\n";
		$inhalt = "";
		
		if((count($this->temp_page["all_key"]) !== count($this->station()->getAll())) && !$this->temp_page["start"])
		{
			foreach($this->station()->getAll() as $venues => $data) $this->temp_page["all_key"][] = $venues;
			$this->temp_page["start"] = \true;
		}
		
		foreach($this->temp_page["all_key"] as $venues)
		{
			if(!$this->station()->exists($venues)) unset($this->temp_page["all_key"][array_search($venues, $this->temp_page["all_key"])]);
			$data = $this->station()->get($venues);
			if(count($data) === 0) continue;
			$inhalt .= str_replace(["{venues}", "{type}"], [$venues, (($data["ban_win10"]) ? "仅PE" : "开放式")], $title_2).$line_break;
			$val = 1;
			krsort($data["top"]);
			foreach($data["top"] as $clicktime => $pn)
			{
				if($val < 4) $inhalt .= str_replace(["{val}", "{pn}", "{countdown}", "{clicktime}"], [$val++, $pn, $data["countdown"], $clicktime], $ranking).$line_break;
			}
			unset($this->temp_page["all_key"][array_search($venues, $this->temp_page["all_key"])]);
			$this->temp_page["c"]++;
			if($this->temp_page["c"] === $this->config()->get(self::CONFIG_LEADERBOARD_DISPLAY_PER_PAGE))
			{
				$this->temp_page["c"] = 0;
				break;
			}
		}
		$this->temp_page["current_page"]++;
		// var_dump($this->temp_page);
		
		$inhalt = $title_1.$line_break.$inhalt.str_replace(["{current}", "{total}"], [$this->temp_page["current_page"], round(count($this->station()->getAll()) / $this->config()->get(self::CONFIG_LEADERBOARD_DISPLAY_PER_PAGE))], $end);
		// var_dump($inhalt);
		// $this->server->getLogger()->info($line_break.$inhalt);
		
		if(count($this->temp_page["all_key"]) === 0 || ($this->temp_page["current_page"] === round(count($this->station()->getAll()) / $this->config()->get(self::CONFIG_LEADERBOARD_DISPLAY_PER_PAGE)))) $this->temp_page = ["all_key" => [], "start" => \false, "c" => 0, "current_page" => 0];
		
		$pos = $this->getVector($this->config()->get(self::CONFIG_LEADERBOARD_POSITION));
		$pos = new Position((float) $pos[0], (float) $pos[1], (float) $pos[2], $this->getServer()->getLevelByName($pos[3]));
		$this->sendNetworkPacket($pos, $inhalt);
        return \true;
    }
	
	
	public final function updateNetworkPacket()
	{
		if($this->config()->get(self::CONFIG_LEADERBOARD_POSITION) === "") return \false;
		$pos = $this->getVector($this->config()->get(self::CONFIG_LEADERBOARD_POSITION));
		$pos = new Position((float) $pos[0], (float) $pos[1], (float) $pos[2], $this->getServer()->getLevelByName($pos[3]));
		$this->sendNetworkPacket($pos, "", "", true);
	}
	
	
	public final function sendNetworkPacket(Position $pos/* , int $entityId */, string $text, string $title = "", bool $remove = \false)
	{
		if(!isset($this->entityId)) $this->entityId = \pocketmine\entity\Entity::$entityCount++;
		if($remove)
		{
			$pk = new \pocketmine\network\mcpe\protocol\RemoveEntityPacket();
			if($this->getTSApi()->getKernelNetWorkPath() == 11) $pk->entityUniqueId = $this->entityId;
			else $pk->eid = $this->entityId;
			foreach($this->getServer()->getOnlinePlayers() as $p)
			{
				if($p->getLevel()->getName() === $pos->getLevel()->getName()) $p->dataPacket($pk);
			}
			$this->eid = \null;
			unset($pk);
			return \true;
		}
		
		$path = \class_exists("\\pocketmine\\item\\ItemFactory", \false) ? 11 : (\class_exists("\\pocketmine\\item\\Item", \false) ? 10 : \false);
		
		$pk = new \pocketmine\network\mcpe\protocol\AddPlayerPacket();
		$pk->uuid            = \pocketmine\utils\UUID::fromRandom();
		$pk->username        = $title . ($text !== "" ? "\n".$text : "");
		$pk->item            = ($path == 10) ? Item::get(\pocketmine\item\Item::AIR) : ItemF::get(\pocketmine\item\Item::AIR, 0, 0);
		if($this->getTSApi()->getKernelNetWorkPath() == 11)
		{
			// 兼容1.4及以上PMMP版本;
			$pk->entityRuntimeId = $this->entityId;
			$pk->position        = $pos;
			$flags               = (1 << Entity::DATA_FLAG_IMMOBILE);
			$pk->metadata = 
			[
				Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
				Entity::DATA_SCALE => [Entity::DATA_TYPE_FLOAT, 0.01]
			];
		}
		foreach($this->getServer()->getOnlinePlayers() as $p)
		{
			if($p->getLevel()->getName() === $pos->getLevel()->getName()) $p->dataPacket($pk);
		}
		unset($pk);
		return \true;
	}
	
	
	public final function shutdownFloatingText()
	{
		$this->tsapi->getTaskManager()->cancelCallbackTask("addFloatingText");
		$this->tsapi->getTaskManager()->cancelCallbackTask("updateNetworkPacket");
	}
	
	
	
	
	
	
	public function onPlayerJoin(PlayerJoinEvent $e)
	{
		$p = $e->getPlayer();
		$n = $p->getName();
		if(!$this->getTempPlayerData($n))
		{
			$this->temp_player_data[$n] = $this->default_temp_player_data;
			$this->temp_player_data[$n]["name"] = $n;
		}
	}
	
	
	public function onPlayerQuit(PlayerQuitEvent $e)
	{
		$n = $e->getPlayer()->getName();
		$data = $this->getTempPlayerData($n);
		if($data && $data["isJoin"])
		{
			if($this->isPlayerInTempStation($n, $data["station"]))
			{
				$this->removePlayerInStation($n, $data["station"]);
				$this->resetStation($data["station"]);
			}
		}
		unset($this->temp_player_data[$n], $data, $n);
	}
	
	
	public function onPlayerTouch(PlayerInteractEvent $e)
	{
		$p = $e->getPlayer();
		$n = $p->getName();
		$b = $e->getBlock();
		$x = $b->getX();
		$y = $b->getY();
		$z = $b->getZ();
		$level = $b->getLevel();
		$ln = $level->getName();
		
		// 设置游戏节点时执行的的步骤;
		$chache_info = $this->getCacheSettingInfo($n);
		if(count($chache_info) > 0)
		{
			if(!$e->isCancelled()) $e->setCancelled(true);
			if(!$b instanceof Grass)
			{
				$p->sendMessage(self::NORMAL_PRE."§e请点击一个坐标点作为 §f\"§b".$chache_info["name"]."§f\" §e的游戏节点, 它应当是一个 §e".Main::BLOCKMARK." §a.");
				return null;
			}
			if($ln !== $chache_info["level"])
			{
				$p->sendMessage(self::NORMAL_PRE."§c初始化的世界名称与当前世界名称不相同, 无法完成当前操作.");
				return null;
			}
			
			if(!$this->getTempStationData($chache_info["name"]))
			{
				$this->temp_level_with_station[$ln][] = $chache_info["name"];
				$this->initTempStation($chache_info["name"], $ln);
				$this->setTempStationData($chache_info["name"], "countdown", $chache_info["countdown"]);
			}
			$chache_info["start_point"]        = [$x, $y, $z];
			$chache_info["block_glass_point"]  = [$x + self::XDISTANCE, $y + self::GYDISTANCE, $z];
			$chache_info["block_quartz_point"] = [$x + self::XDISTANCE, $y + self::QYDISTANCE, $z];
			$chache_info["ban_win10"]          = $this->config()->get(self::CONFIG_WIN10_USER_DISABLE);
			$this->station()->set($chache_info["name"], $chache_info);
			$this->station()->save();
			
			$chache_info = $this->getServer()->getLevelByName($ln);
			if(
				(!$chache_info->getBlock(new Vector3($x + self::XDISTANCE, $y + self::GYDISTANCE, $z)) instanceof Air)
				|| (!$chache_info->getBlock(new Vector3($x + self::XDISTANCE, $y + self::QYDISTANCE, $z)) instanceof Air)
				|| ($chache_info->getBlock(new Vector3($x + self::XDISTANCE, $y, $z)) instanceof Gold)
			)
			{
				$p->sendMessage(self::NORMAL_PRE."§c无法将场地放置在此, 前方可能已经存在其他场地, 请更换这个场地的设置位置!");
				return null;
			}
			
			$quartz = new \ReflectionClass('\pocketmine\block\Quartz');
			$quartz = (!is_bool($quartz->getConstant("NORMAL"))) ? $quartz->getConstant("NORMAL") : (!is_bool($quartz->getConstant("QUARTZ_NORMAL")) ? $quartz->getConstant("QUARTZ_NORMAL") : 0);
			
			$chache_info->setBlock(new Vector3($x, $y, $z), new Gold);
			$chache_info->setBlock(new Vector3($x + self::XDISTANCE, $y + self::GYDISTANCE, $z), new Glass(Glass::GLASS));
			$chache_info->setBlock(new Vector3($x + self::XDISTANCE, $y + self::QYDISTANCE, $z), new Block(Quartz::QUARTZ_BLOCK, $quartz));
			
			
			$p->sendMessage(self::NORMAL_PRE."§ePosition: §aX§f->§b".$x);
			$p->sendMessage(self::NORMAL_PRE."§ePosition: §aY§f->§b".$y);
			$p->sendMessage(self::NORMAL_PRE."§ePosition: §aZ§f->§b".$z);
			$p->sendMessage(self::NORMAL_PRE."§eLevelName: §b".$ln);
			$p->sendMessage(self::NORMAL_PRE."§a节点设置完毕, 可以开始游戏了.");
			
			unset($chache_info, $this->setting_cache[$n]);
		}
		
		// 玩家在游戏节点的时候执行的步骤;
		$temp_player_data = $this->getTempPlayerData($n);
		if(is_array($temp_player_data))
		{
			if($temp_player_data["isJoin"])
			{
				if($this->config()->get(self::CONFIG_WIN10_USER_DISABLE) && ($this->dgapi->getPlayerDeviceOS($n) == 7))
				{
					$p->sendTip(self::NORMAL_PRE."§c本服务器已禁止 §eWIN10-UWP-MCPE §c的用户使用该功能!\n\n\n\n\n\n\n");
					return null;
				}
				if(!$this->isPlayerInTempStation($n, $temp_player_data["station"])) return null;
				
				$station = $this->station()->get($temp_player_data["station"]);
				$qx = $station["block_quartz_point"][0];
				$qy = $station["block_quartz_point"][1];
				$qz = $station["block_quartz_point"][2];
				if(($x == $qx) && ($y == $qy) && ($z == $qz) && ($b instanceof Quartz))
				{
					$this->temp_player_data[$n]["clicktime"]++;
				}
				// unset($p, $n, $b, $x, $y, $z, $station, $qx, $qy, $qz, $temp_player_data);
			}
		}
	}
	
	
	// 保护游戏节点不被破坏的事件;
	public function onBlockBreak(BlockBreakEvent $e)
	{
		$p = $e->getPlayer();
		$n = $p->getName();
		$b = $e->getBlock();
		$x = $b->getX();
		$y = $b->getY();
		$z = $b->getZ();
		$level = $b->getLevel();
		$ln = $level->getName();
		
		if($this->isTempStationInLevel($ln))
		{
			$stations = $this->temp_level_with_station[$ln];
			if(count($stations) > 1)
			{
				foreach($stations as $station)
				{
					$s_con = $this->station()->get($station);
					
					$sx = $s_con["start_point"][0];
					$sy = $s_con["start_point"][1];
					$sz = $s_con["start_point"][2];
					
					$gx = $s_con["block_glass_point"][0];
					$gy = $s_con["block_glass_point"][1];
					$gz = $s_con["block_glass_point"][2];
					
					$qx = $s_con["block_quartz_point"][0];
					$qy = $s_con["block_quartz_point"][1];
					$qz = $s_con["block_quartz_point"][2];
					
					if(($x == $sx) && ($y == $sy) && ($z == $sz))
					{
						if(!$e->isCancelled()) $e->setCancelled(true);
					}
					elseif(($x == $gx) && ($y == $gy) && ($z == $gz) && ($b instanceof Glass))
					{
						if(!$e->isCancelled()) $e->setCancelled(true);
					}
					elseif(($x == $qx) && ($y == $qy) && ($z == $qz) && ($b instanceof Quartz))
					{
						if(!$e->isCancelled()) $e->setCancelled(true);
					}
					
					// unset($p, $n, $b, $x, $y, $z, $s_con, $sx, $sz, $sz, $gx, $gy, $gz, $qx, $qy, $qz, $station, $stations);
				}
				
			}
			else
			{
				$s_con = $this->station()->get($stations[0]);
				
				$sx = $s_con["start_point"][0];
				$sy = $s_con["start_point"][1];
				$sz = $s_con["start_point"][2];
				
				$gx = $s_con["block_glass_point"][0];
				$gy = $s_con["block_glass_point"][1];
				$gz = $s_con["block_glass_point"][2];
				
				$qx = $s_con["block_quartz_point"][0];
				$qy = $s_con["block_quartz_point"][1];
				$qz = $s_con["block_quartz_point"][2];
				
				if(($x == $sx) && ($y == $sy) && ($z == $sz))
				{
					if(!$e->isCancelled()) $e->setCancelled(true);
				}
				elseif(($x == $gx) && ($y == $gy) && ($z == $gz) && ($b instanceof Glass))
				{
					if(!$e->isCancelled()) $e->setCancelled(true);
				}
				elseif(($x == $qx) && ($y == $qy) && ($z == $qz) && ($b instanceof Quartz))
				{
					if(!$e->isCancelled()) $e->setCancelled(true);
				}
				
				// unset($p, $n, $b, $x, $y, $z, $s_con, $sx, $sz, $sz, $gx, $gy, $gz, $qx, $qy, $qz, $station, $stations);
			}
		}
	}
	
	
	
	
#---[TEMP_PLAYER FUNCTIONS]--------------------------------------------------------------------------------------------#
	public final function getTempPlayerData(string $player_name)
	{
		return (isset($this->temp_player_data[$player_name])) ? $this->temp_player_data[$player_name] : false;
	}
	
	public final function getTempPlayerDataWith(string $player_name, string $index)
	{
		if($this->getTempPlayerData($player_name))
		{
			if(!isset($this->default_temp_player_data[$index]))
			{
				throw new \Exception("参数 {$index} 不存在");
				return false;
			}
			return $this->getTempPlayerData($player_name)[$index];
		}
		else return false;
	}
	
	public final function setTempPlayerData(string $player_name, string $index, $param)
	{
		if($this->getTempPlayerData($player_name))
		{
			if(!isset($this->default_temp_player_data[$index]))
			{
				throw new \Exception("参数 {$index} 不存在");
				return false;
			}
			$this->temp_player_data[$player_name][$index] = $param;
			return true;
		}
		else return false;
	}
	
	// 这个函数是当玩家加入一个游戏场地的时候才使用的, 初始化玩家配置文件的步骤写在了PlayerJoinEvent事件里面;
	public final function initPlayerInStation(Player $p, $station_id)
	{
		$n = $p->getName();
		if($this->getTempStationData($station_id))
		{
			if($this->getTempPlayerData($n))
			{
				$this->setTempPlayerData($n, "waiting_status", true);
				$this->setTempPlayerData($n, "station", $station_id);
				$this->setPlayerInTempStation($n, $station_id);
				
				$t = $this->station()->get($station_id);
				$t1 = $t["start_point"];
				$p->teleport($this->server->getLevelByName($t["level"])->getSafeSpawn(new Vector3($t1[0], $t1[1] + 1, $t1[2])));
				unset($n, $t, $t1);
				return true;
			}
			else return false;
		}
		else return false;
	}
	
	public final function removePlayerInStation(string $player_name, $station_id)
	{
		if($this->getTempPlayerData($player_name))
		{
			$this->temp_player_data[$player_name] = $this->default_temp_player_data;
			$this->temp_player_data[$player_name]["name"]    = $player_name;
			$this->temp_player_data[$player_name]["station"] = "";
			
			$t = $this->station()->get($station_id)["start_point"];
			$this->server->getPlayer($player_name)->teleport(new Vector3($t[0] - 2, $t[1] + 1, $t[2]));
			unset($t);
			return true;
		}
		else return false;
	}
	
	public final function isPlayerInTempStation(string $player_name, $station_id)
	{
		$station = $this->getTempStationData($station_id);
		return ($station) ? ($player_name === $station["player"]) : false;
	}
	
	public final function setPlayerInTempStation(string $player_name, $station_id)
	{
		if($this->getTempStationData($station_id))
		{
			$this->setTempStationData($station_id, "player", $player_name);
			return true;
		}
		else return false;
	}
	
	
#---[TEMP_STATION FUNCTIONS]--------------------------------------------------------------------------------------------#
	public final function getTempStationData($station_id)
	{
		return (isset($this->temp_station_data[$station_id])) ? $this->temp_station_data[$station_id] : false;
	}
	
	public final function setTempStationData($station_id, string $index, $param)
	{
		if($this->getTempStationData($station_id))
		{
			if(!isset($this->default_temp_station_data[$index]))
			{
				throw new \Exception("参数 {$index} 不存在");
				return false;
			}
			$this->temp_station_data[$station_id][$index] = $param;
			return true;
		}
		else return false;
	}
	
	// 这个函数将添加一个游戏场地至缓存数组;
	public final function initTempStation($station_id, string $level)
	{
		if(!$this->getTempStationData($station_id))
		{
			$this->temp_station_data[$station_id] = $this->default_temp_station_data;
			$this->temp_station_data[$station_id]["name"]      = $station_id;
			$this->temp_station_data[$station_id]["level"]     = $level;
			$this->temp_station_data[$station_id]["countdown"] = $this->station()->getNested($station_id.".countdown");
			return true;
		}
		else return false;
	}
	
	// 更新缓存数组中的场地数据;
	public final function updateTempStation($station_id, $index, $param)
	{
		if($this->getTempStationData($station_id))
		{
			$this->temp_station_data[$station_id][$index] = $param;
			return true;
		}
		else return false;
	}
	
	public final function delTempStation($station_id)
	{
		if($this->getTempStationData($station_id))
		{
			unset($this->temp_station_data[$station_id]);
			return true;
		}
		return false;
	}
	
	public final function reduceTempStationCountDown($station_id)
	{
		return ($this->getTempStationData($station_id)) ? $this->temp_station_data[$station_id]["countdown"]-- : false;
	}
	
	public final function resetStation($station_id)
	{
		if($this->getTempStationData($station_id))
		{
			$this->setTempStationData($station_id, "player", "");
			// $this->setTempStationData($station_id, "level", "");
			$this->setTempStationData($station_id, "status", false);
			$this->setTempStationData($station_id, "countdown", $this->station()->getNested($station_id.".countdown"));
			return true;
		}
		else return false;
	}
	
	public final function getLevelFromTempStation($station_id) // 通过一个游戏节点来获取这个游戏节点的地图名称;
	{
		$data = $this->getTempStationData($station_id);
		return ($data) ? $data["level"] : false;
	}
	
	public final function isTempStationInLevel(string $level) // 判断一个世界是否存在游戏节点;
	{
		return isset($this->temp_level_with_station[$level]);
	}
	
	public final function savePlayerInTempStationData(string $player_name, $station_id)
	{
		if($this->getTempStationData($station_id))
		{
			$data                       = $this->getTempPlayerData($player_name);
			$s_con                      = $this->station()->get($station_id);
			$s_con["play_time"]         = $s_con["play_time"] + 1;
			// $s_con["top"][$player_name] = $data["clicktime"];
			$s_con["top"][$data["clicktime"]] = $player_name;
			$level                      = $this->getLevelFromTempStation($station_id);
			$this->station()->setNested($station_id.".play_time", $s_con["play_time"]);
			
			if($data["clicktime"] > 0)
			{
				krsort($s_con["top"]);     // 对数组进行降序排列;
				if(count($s_con["top"]) > 3) array_pop($s_con["top"]);  // 移除数组中最后一个元素;
				$this->station()->setNested($station_id.".top", $s_con["top"]);
			}
			
			$this->server->getLogger()->info(self::NORMAL_PRE."§a玩家 §b{$player_name} §a在世界 §d{$level} §a的游戏场地 §e{$station_id} §a的数据已保存.");
			$this->server->getLogger()->info(self::NORMAL_PRE."§b{$player_name} §e本次的点击次数: §a".$data["clicktime"]);
			
			unset($data, $s_con, $level);
			return $this->station()->save();
		}
		else return false;
	}
	
	
	
	
#---[CONFIG_STATION_SETTING FUNCTIONS]--------------------------------------------------------------------------------------------#
	public final function getCacheSettingInfo(string $creator) : array
	{
		return (!isset($this->setting_cache[$creator])) ? [] : $this->setting_cache[$creator];
	}
	
	public final function initStationSetting(array $data, bool $cancel = \false) : bool
	{
		foreach($this->default_index as $name => $param)
		{
			if(!in_array($name, $data))
			{
				throw new \Exception("缺少参数 \"{$name}\", 设置失败.");
				return false;
			}
		}
		
		if($cancel && isset($this->setting_cache[$data["creator"]]))
		{
			unset($this->setting_cache[$data["creator"]]);
			return true;
		}
		else
		{
			$this->setting_cache[$data["creator"]] = $data;
			return true;
		}
	}
	
	public final function getPlayerTouchPointInStation(string $player_name, $station_id)
	{
		if(!$this->station()->exists($station_id))
		{
			throw new \Exception("找不到游戏场地 {$station_id}.");
			return false;
		}
		return array_flip($this->station()->get($station_id)["top"])[$player_name];
	}
	
	
#---[CONFIG FUNCTIONS]--------------------------------------------------------------------------------------------#
	public final function config() : Config
	{
		return $this->config;
	}
	public final function station() : Config
	{
		return $this->station;
	}
	public final function getVector(string $index) : array
	{
		return explode(self::VECTOR_DIVISION, $index);
	}
	
#---[OTHER FUNCTIONS]--------------------------------------------------------------------------------------------#
	public final function getTSApi() : \Teaclon\TSeriesAPI\Main
	{
		return $this->tsapi;
	}
	
	public final function isPlayerAdmin(string $player_name)
	{
		return in_array($player_name, $this->config()->get(Main::CONFIG_ADMIN_LIST));
	}
	
	public final function addAdmin(string $player_name)
	{
		if(!$this->isPlayerAdmin($player_name))
		{
			$list   = $this->config()->get(Main::CONFIG_ADMIN_LIST);
			$list[] = $player_name;
			$this->config()->set(Main::CONFIG_ADMIN_LIST, $list);
			unset($list);
			return $this->config()->save();
		}
		else return false;
	}
	
	public final function removeAdmin(string $player_name)
	{
		if($this->isPlayerAdmin($player_name))
		{
			$list   = $this->config()->get(Main::CONFIG_ADMIN_LIST);
			$key    = array_search($player_name, $list);
			unset($list[$key]);
			$this->config()->set(Main::CONFIG_ADMIN_LIST, $list);
			unset($list, $key);
			return $this->config()->save();
		}
		else return false;
	}
	
	public final function getPlayerFilePath(string $player_name)
	{
		return Server::getInstance()->getDataPath() . "players" . DIRECTORY_SEPARATOR .strtolower($player_name).".dat";
	}
	
	public final function addDefaultParticle(Player $p, $r = 2.5)
	{
		// 弹性圆算法by aabbcc872, 原作者已授权使用;
		$pos = [];
		$y = 1.5;
		
		if($r == 0)
		{
			return [0, 0, 0];
		}
		$ar = round($r, 1);
		$c = 0;
		for($a = 0; $a <= $ar; $a += 0.1)
		{
			$c++;
		}
		$b = 360 / ($c * 4);
		if(($b > 90) || ($b < 0)) return null;
		for($i = 0; $i <= 90; $i += $b)
		{
			$x = $r * cos(deg2rad($i));
			$z = $r * sin(deg2rad($i));
			$pos["c"][] = [$x, $y, $z];
			$pos["c"][] = [-$z, $y, $x];
			$pos["c"][] = [-$x, $y, -$z];
			$pos["c"][] = [$z, $y, -$x];
		}
		
		// 正方形算法
		$r /= sqrt(2);
		for($i =- $r; $i <= $r; $i += 0.2)
		{
			$pos["q"][] = [$i, $y, $r];
			$pos["q"][] = [$i, $y, -$r];
			$pos["q"][] = [$r, $y, $i];
			$pos["q"][] = [-$r, $y, $i];
		}
		
		foreach($pos["c"] as $po)
		{
			$p->getLevel()->addParticle(new DustParticle(new Vector3($po[0] + $p->getX(), $po[1] + $p->getY(), $po[2] + $p->getZ()), 0, 221, 255));
		}
		foreach($pos["q"] as $po)
		{
			$p->getLevel()->addParticle(new DustParticle(new Vector3($po[0] + $p->getX(), $po[1] + $p->getY(), $po[2] + $p->getZ()), 255, 0, 98));
		}
	}
	
}
?>