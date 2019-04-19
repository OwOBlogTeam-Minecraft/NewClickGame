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

namespace Teaclon\ClickGame\command;

use Teaclon\ClickGame\Main;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\math\Vector3;
use pocketmine\block\Air;
use pocketmine\block\Grass;


class SetStationCommand extends Command
{
	
	const PERMISSION_HIGHEST = "admin"; // 最高权限;
	const PERMISSION_OP      = "op";    // OP权限;
	const PERMISSION_LOWEST  = "lowst"; // 最低权限;
	const PERMISSION_NONE    = "none";  // 没有权限;
	
	private $name, $logger;
	private $plugin = null;
	
	
	public function __construct($name, Main $plugin, $usageMessage = null, array $aliases = [], array $overloads = [])
	{
		$this->name = $name;
		$this->plugin = $plugin;
		$this->logger = \pocketmine\Server::getInstance()->getLogger();
		
		parent::__construct($name, "NewClickGame的主指令", $usageMessage, $aliases, $overloads);
	}
	
	
	
	public static function getCommandPermission(string $cmd)
	{
		$cmds = 
		[
			"set"    => [self::PERMISSION_HIGHEST],
			"del"    => [self::PERMISSION_HIGHEST],
			"cancel" => [self::PERMISSION_HIGHEST],
			"admin"  => [self::PERMISSION_HIGHEST],
			"wait"   => [self::PERMISSION_HIGHEST],
			"win10"  => [self::PERMISSION_HIGHEST],
			"modify" => [self::PERMISSION_HIGHEST],
			#-------------------------------------------#;
			"join"   => [self::PERMISSION_HIGHEST, self::PERMISSION_OP, self::PERMISSION_LOWEST],
			"tp"     => [self::PERMISSION_HIGHEST, self::PERMISSION_OP, self::PERMISSION_LOWEST],
		];
		
		$cmd = strtolower($cmd);
		return isset($cmds[$cmd]) ? $cmds[$cmd] : "admin";
	}
	
	public static function getHelpMessage()
	{
		return 
		[
			"用法: §d/§6{cmd} set §f<§e节点ID§f> §f<§e倒计时秒§f>  §a添加§f一个游戏节点"   => self::getCommandPermission("set"),    // OK;
			"用法: §d/§6{cmd} del §f<§e节点ID§f>             §c删除§f一个游戏节点"         => self::getCommandPermission("del"),    // OK;
			"用法: §d/§6{cmd} admin §f<§e管理员名称§f>       设置或移除一个游戏节点管理员" => self::getCommandPermission("admin"),  // OK;
			"用法: §d/§6{cmd} cancel                   §f清空当前的设置状态"               => self::getCommandPermission("cancel"), // OK;
			"用法: §d/§6{cmd} wait §f<§e等待时间秒§f>        设置玩家的默认等待时间(全部游戏节点统一设置)" => self::getCommandPermission("wait"), // OK;
			"用法: §d/§6{cmd} win10                    §f设置默认配置文件, 游戏节点是否允许WIN10玩家使用" => self::getCommandPermission("win10"), // OK;
			"用法: §d/§6{cmd} modify §f<§e节点ID§f>          §f管理一个游戏节点"           => self::getCommandPermission("modify"), // OK;
			#-------------------------------------------#;
			"用法: §d/§6{cmd} join §f<§e节点ID§f>            §f加入一个游戏节点"           => self::getCommandPermission("join"),   // OK;
			"用法: §d/§6{cmd} tp §f<§e节点ID§f>              §f传送到一个游戏节点"         => self::getCommandPermission("tp"),     // OK;
		];
	}
	
	public function getSenderPermission(CommandSender $sender)
	{
		$isInWhiteList = $this->plugin->isPlayerAdmin($sender->getName());
		$isOp          = $sender->isOp();
		
		if((!$sender instanceof Player) && (strtolower($sender->getName()) === "console")) return self::PERMISSION_HIGHEST;
		elseif($isOp && $isInWhiteList) return self::PERMISSION_HIGHEST;
		elseif($isOp && !$isInWhiteList) return self::PERMISSION_OP;
		elseif(!$isOp && !$isInWhiteList) return self::PERMISSION_LOWEST;
		else return self::PERMISSION_NONE;
	}
	
	
	
	public function execute(CommandSender $sender, $currentAlias, array $args)
	{
		
		if(!isset($args[0]))
		{
			foreach(self::getHelpMessage() as $message => $permission)
			{
				if(in_array($this->getSenderPermission($sender), $permission))
					$this->sendMessage($sender, str_replace("{cmd}", $this->name, $message));
				else continue;
			}
			return true;
		}
		
		switch($args[0])
		{
			default:
			case "help":
				foreach(self::getHelpMessage() as $message => $permission)
				{
					if(in_array($this->getSenderPermission($sender), $permission))
						$this->sendMessage($sender, str_replace("{cmd}", $this->name, $message));
					else continue;
				}
				return true;
			break;
			
			
			case "set":
				if(!$sender instanceof Player)
				{
					$this->sendMessage($sender, "§c请在游戏内使用这个指令.");
					return true;
				}
				if(!in_array($this->getSenderPermission($sender), $this->getCommandPermission($args[0])))
				{
					$this->sendMessage($sender, "§c你没有权限使用这个指令.");
					return true;
				}
				if($this->getCacheSettingInfo($sender)) return true;
				
				if(!isset($args[1]))
				{
					$this->sendMessage($sender, "§c请输入一个有效的§e游戏节点ID§c.");
					return true;
				}
				if($this->plugin->station()->exists($args[1]))
				{
					$this->sendMessage($sender, "§c游戏节点ID §e{$args[1]} §c已存在, 请更换名字.");
					return true;
				}
				$countdown = $this->plugin->config()->get(Main::CONFIG_DEFAULT_COUNTDOWN);
				$countdown = (!isset($args[2])) ? $countdown : ((is_numeric($args[2])) ? $args[2] : $countdown);
				$this->plugin->initStationSetting
				([
					"name"               => $args[1],
					"creator"            => $sender->getName(),
					"level"              => $sender->getLevel()->getName(),
					"start_point"        => "",
					"block_glass_point"  => "",
					"block_quartz_point" => "",
					"play_time"          => 0,
					"top"                => [],
					"countdown"          => $countdown
				]);
				$this->sendMessage($sender, "§e默认倒计时数已被设置为 {$countdown} 秒!");
				$this->sendMessage($sender, "§e已初始化游戏节点设置, 请点击一个§a".Main::BLOCKMARK."§e作为开始坐标点.");
				return true;
			break;
			
			
			case "del":
				if(!in_array($this->getSenderPermission($sender), $this->getCommandPermission($args[0])))
				{
					$this->sendMessage($sender, "§c你没有权限使用这个指令.");
					return true;
				}
				if($this->getCacheSettingInfo($sender)) return true;
				if(!isset($args[1]))
				{
					$this->sendMessage($sender, "§c请输入一个有效的§e游戏节点ID§c.");
					return true;
				}
				
				if(!$this->plugin->station()->exists($args[1]))
				{
					$this->sendMessage($sender, "§c游戏节点ID §e{$args[1]} §c不存在.");
					return true;
				}
				$temp = $this->plugin->station()->get($args[1]);
				$s =  $temp["start_point"];
				$g =  $temp["block_glass_point"];
				$q =  $temp["block_quartz_point"];
				$temp = $this->plugin->getServer()->getLevelByName($temp["level"]);
				$temp->setBlock(new Vector3($s[0], $s[1], $s[2]), new Grass);
				$temp->setBlock(new Vector3($g[0], $g[1], $g[2]), new Air);
				$temp->setBlock(new Vector3($q[0], $q[1], $q[2]), new Air);
				
				$this->plugin->station()->remove($args[1]);
				$this->plugin->station()->save();
				$this->sendMessage($sender, "§c已删除游戏节点ID §e{$args[1]} §c.");
				
				unset($temp, $s, $g, $q);
				return true;
			break;
			
			
			case "cancel":
				if(!in_array($this->getSenderPermission($sender), $this->getCommandPermission($args[0])))
				{
					$this->sendMessage($sender, "§c你没有权限使用这个指令.");
					return true;
				}
				if(!$this->getCacheSettingInfo($sender))
				{
					$this->sendMessage($sender, "§c你不需要取消任何东西.");
					return true;
				}
				$arr = 
				["name" => "", "creator" => $sender->getName(), "level" => "", "start_point"  => "", "block_glass_point" => "", "block_quartz_point" => "", "play_time" => 0];
				if($this->plugin->initStationSetting($arr, true))
				{
					unset($arr);
					$this->sendMessage($sender, "§c已经取消了你目前的设置状态.");
					return true;
				}
				else
				{
					unset($arr);
					$this->sendMessage($sender, "§c未知错误.");
					return true;
				}
			break;
			
			
			case "admin":
				if(!in_array($this->getSenderPermission($sender), $this->getCommandPermission($args[0])))
				{
					$this->sendMessage($sender, "§c你没有权限使用这个指令.");
					return true;
				}
				if(!isset($args[1]))
				{
					$this->sendMessage($sender, "§c请输入一个有效的玩家名称.");
					return true;
				}
				if(!file_exists($this->plugin->getPlayerFilePath($args[1])))
				{
					$this->sendMessage($sender, "§e{$args[1]} §c不是这个服务器的玩家.");
					return true;
				}
				
				if($this->plugin->isPlayerAdmin($args[1]))
				{
					$this->plugin->removeAdmin($sender->getName());
					$this->sendMessage($sender, "§e玩家 §a{$args[1]} §e已被取消游戏节点管理员的权限.");
					if($this->plugin->getServer()->getPlayer($args[1])) $this->plugin->getServer()->getPlayer($args[1])->sendMessage(Main::PREFIX."§c你已被取消游戏节点管理员!");
				}
				else
				{
					$this->plugin->addAdmin($args[1]);
					$this->sendMessage($sender, "§e玩家 §a{$args[1]} §e已成为游戏节点管理员的权限.");
					if($this->plugin->getServer()->getPlayer($args[1])) $this->plugin->getServer()->getPlayer($args[1])->sendMessage(Main::PREFIX."§a你已成为游戏节点管理员!");
				}
				return true;
			break;
			
			
			case "wait":
			case "等待时间":
			case "wait_time":
			case "waiting_time":
				if($args[0] === "等待时间" || $args[0] === "wait_time" || $args[0] === "waiting_time") $args[0] = "wait";
				if(!in_array($this->getSenderPermission($sender), $this->getCommandPermission($args[0])))
				{
					$this->sendMessage($sender, "§c你没有权限使用这个指令.");
					return true;
				}
				if(!isset($args[1]))
				{
					$this->sendMessage($sender, "§c请输入一个有效的数字.");
					return true;
				}
				if(!is_numeric($args[1]))
				{
					$this->sendMessage($sender, "§c请输入一个有效的数字.");
					return true;
				}
				
				$this->plugin->config()->set(Main::CONFIG_DEFAULT_PLAYER_WAITING_TIME, $args[1]);
				$this->plugin->config()->save();
				$this->sendMessage($sender, "§e已设置玩家默认等待时间为 §a{$args[1]} §e秒.");
				return true;
			break;
			
			
			case "win10":
				if(!in_array($this->getSenderPermission($sender), $this->getCommandPermission($args[0])))
				{
					$this->sendMessage($sender, "§c你没有权限使用这个指令.");
					return true;
				}
				$status = $this->plugin->config()->get(Main::CONFIG_WIN10_USER_DISABLE);
				$this->plugin->config()->set(Main::CONFIG_WIN10_USER_DISABLE, $status ? false : true);
				$this->plugin->config()->save();
				$this->sendMessage($sender, "§e已 ".($status ? "§a允许" : "§c禁止")." §eWIN10玩家使用所有的游戏场地.");
				return true;
			break;
			
			
			case "modify":
			case "修改":
			case "编辑":
				if($args[0] === "修改" || $args[0] === "编辑") $args[0] = "modify";
				if(!in_array($this->getSenderPermission($sender), $this->getCommandPermission($args[0])))
				{
					$this->sendMessage($sender, "§c你没有权限使用这个指令.");
					return true;
				}
				
				if(!isset($args[1], $args[2]))
				{
					$this->sendMessage($sender, "§d/§6cl modify §f<§e游戏节点§f> win10                §f设置该游戏场地是否允许win10用户使用");
					$this->sendMessage($sender, "§d/§6cl modify §f<§e游戏节点§f> countdown §f<§etime§f>     §f设置该游戏场地的游戏时间");
					return true;
				}
				if(!$this->plugin->station()->exists($args[1]))
				{
					$this->sendMessage($sender, "§c游戏节点 §e{$args[1]} §c不存在.");
					return true;
				}
				switch($args[2])
				{
					default:
						$this->sendMessage($sender, "§c指令使用错误, 请输入 §d/§6cl modify §c查看帮助.");
					break;
					
					
					case "win10":
						$this->plugin->station()->setNested($args[1].".ban_win10", ($this->plugin->station()->get($args[1])["ban_win10"] ? false : true));
						$this->plugin->station()->save();
						$status = $this->plugin->station()->getNested($args[1].".ban_win10") ? "§c禁止" : "§a允许";
						$this->sendMessage($sender, "§b修改成功. 游戏节点 §e{$args[1]} §b的WIN10使用状态: ".$status);
						return true;
					break;
					
					
					case "countdown":
						if(!isset($args[3]) || !is_numeric($args[3]))
						{
							$this->sendMessage($sender, "§c请输入需要设置的数值.");
							return true;
						}
						$this->plugin->station()->setNested($args[1].".countdown", $args[3]);
						$this->plugin->station()->save();
						$status = $this->plugin->station()->getNested($args[1].".countdown");
						$this->sendMessage($sender, "§b修改成功. 游戏节点 §e{$args[1]} §b的游戏时间为: §6".$status."§bs");
						return true;
					break;
				}
				return true;
			break;
			
			
			
			
			
			
			
			
			
			case "join":
			case "加入":
			case "tp":
			case "传送":
				if(!$sender instanceof Player)
				{
					$this->sendMessage($sender, "§c请在游戏内使用这个指令.");
					return true;
				}
				if($args[0] === "加入") $args[0] = "join";
				elseif($args[0] === "传送") $args[0] = "tp";
				if(!in_array($this->getSenderPermission($sender), $this->getCommandPermission($args[0])))
				{
					$this->sendMessage($sender, "§c你没有权限使用这个指令.");
					return true;
				}
				if(!isset($args[1]))
				{
					$this->sendMessage($sender, "§c请输入一个有效的§e游戏节点ID§c.");
					return true;
				}
				if(!$this->plugin->station()->exists($args[1]))
				{
					$this->sendMessage($sender, "§c游戏节点ID §e{$args[1]} §c不存在.");
					return true;
				}
				
				$quick_transfer_and_start_game = ((strtolower($args[0]) === "join") || ($args[0] === "加入"));
				
				$temp = $this->plugin->station()->get($args[1]);
				$s =  $temp["start_point"];
				if(!$quick_transfer_and_start_game){$s[0] = $s[0] - 2; $s[2] = $s[2] - 1;}
				$sender->teleport($this->plugin->getServer()->getLevelByName($temp["level"])->getSafeSpawn(new Vector3($s[0], $s[1], $s[2])));
				$this->sendMessage($sender, "§a你已被传送到游戏节点ID: §e{$args[1]} §a.");
				unset($temp, $s);
				
				return true;
			break;
		}
	}
	
	
	
	
	private function getCacheSettingInfo(CommandSender $sender)
	{
		$info = $this->plugin->getCacheSettingInfo($sender->getName());
		if(count($info) > 0)
		{
			$this->sendMessage($sender, "§c你正在设置游戏节点 §e".$info["name"]." §c, 请将此节点设置完成之后再设置其他节点.");
			$this->sendMessage($sender, "§c你也可以使用指令 §f\"§d/§6{$this->name} cancel§f\" 取消该节点的设置.");
			unset($info);
			return true;
		}
		else return false;
	}
	
	protected final function sendMessage(CommandSender $sender, $msg)
	{
		$sender->sendMessage($this->plugin::PREFIX.$msg);
	}
	
}
?>