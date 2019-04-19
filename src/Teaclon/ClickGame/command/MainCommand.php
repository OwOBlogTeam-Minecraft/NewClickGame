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

use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\math\Vector3;
use pocketmine\block\Air;
use pocketmine\block\Grass;

use Teaclon\TSeriesAPI\command\subcommand\BaseCommand;
use Teaclon\TSeriesAPI\command\CommandManager;


class MainCommand extends BaseCommand
{
	const MY_COMMAND             = "cl";
	const MY_COMMAND_PEREMISSION = [self::PERMISSION_CONSOLE, self::PERMISSION_ADMINO];
	public $myprefix = Main::NORMAL_PRE;
	private $tsapi = null;
	
	
	
	
	
	public function __construct(Main $plugin)
	{
		$this->tsapi = $plugin->getTSApi();
		$this->usePluginAdminList = \true;
		// CommandName, Description, usage, aliases, overloads;
		$this->init($plugin, self::MY_COMMAND, Main::STRING_PRE."的主指令", null, [], []);
	}
	
	
	
	public function execute(CommandSender $sender, $commandLabel, array $args)
	{
		try
		{
		$senderName = strtolower($sender->getName());
		if(!isset($args[0]))
		{
			$this->sendMessage($sender, "§e--------------§b".Main::STRING_PRE."指令助手§e--------------");
			foreach(self::getHelpMessage() as $cmd => $message)
			{
				if($this->hasSenderPermission($sender, $cmd))
					$this->sendMessage($sender, str_replace("{cmd}", self::MY_COMMAND, $message));
				else continue;
			}
			$this->sendMessage($sender, "§e---------------------------");
			return true;
		}
		
		switch($args[0])
		{
			default:
			case "help":
			case "帮助":
				$this->execute($sender, $commandLabel, []);
				return true;
			break;
			
			
			case "set":
				if(!$sender instanceof Player)
				{
					$this->sendMessage($sender, "§c请在游戏内使用这个指令.");
					return true;
				}
				if(!$this->hasSenderPermission($sender, $args[0]))
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
				if(!$this->hasSenderPermission($sender, $args[0]))
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
				if(!$this->hasSenderPermission($sender, $args[0]))
				{
					$this->sendMessage($sender, "§c你没有权限使用这个指令.");
					return true;
				}
				if(!$this->getCacheSettingInfo($sender, \false))
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
				if(!$this->hasSenderPermission($sender, $args[0]))
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
				if(!$this->hasSenderPermission($sender, $args[0]))
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
				if(!$this->hasSenderPermission($sender, $args[0]))
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
				if(!$this->hasSenderPermission($sender, $args[0]))
				{
					$this->sendMessage($sender, "§c你没有权限使用这个指令.");
					return true;
				}
				
				if(!isset($args[1], $args[2]))
				{
					$this->sendMessage($sender, "§d/§6".self::MY_COMMAND." modify §f<§e游戏节点§f> win10                §f设置该游戏场地是否允许win10用户使用");
					$this->sendMessage($sender, "§d/§6".self::MY_COMMAND." modify §f<§e游戏节点§f> countdown §f<§etime§f>     §f设置该游戏场地的游戏时间");
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
						$this->sendMessage($sender, "§c指令使用错误, 请输入 §d/§6".self::MY_COMMAND." modify §c查看帮助.");
					break;
					
					
					case "win10":
						$this->plugin->station()->setNested($args[1].".ban_win10", ($this->plugin->station()->get($args[1])["ban_win10"] ? false : true));
						$this->plugin->station()->save();
						// $this->plugin->station()->reload(); // 这里需要加入一个配置文件重载机制来进行在线配置生效;
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
						// $this->plugin->station()->reload(); // 这里需要加入一个配置文件重载机制来进行在线配置生效;
						$this->plugin->updateTempStation($args[1], "countdown", $args[3]);
						$countdown = $this->plugin->station()->getNested($args[1].".countdown");
						$this->sendMessage($sender, "§b修改成功. 游戏节点 §e{$args[1]} §b的游戏时间为: §6".$countdown."§bs");
						return true;
					break;
				}
				return true;
			break;
			
			
			
			case "ft":
			case "fkz":
			case "浮空字":
			case "浮空字排行榜":
				if(!$sender instanceof Player)
				{
					$this->sendMessage($sender, "§c请在游戏内使用这个指令.");
					return true;
				}
				if($args[0] === "fkz" || $args[0] === "浮空字" || $args[0] === "浮空字排行榜") $args[0] = "ft";
				if(!$this->hasSenderPermission($sender, $args[0]))
				{
					$this->sendMessage($sender, "§c你没有权限使用这个指令.");
					return true;
				}
				
				if(!isset($args[1]))
				{
					$this->sendMessage($sender, "用法: §d/§6".self::MY_COMMAND." ft spawn§f|§6edit§f|§6tphere§f|§6tpto§f|§6del    §e生成§f|§e编辑§f|§e传送到这里§f|§e传送到那去§f|§e删除§f浮空字排行榜");
					return true;
				}
				switch($args[1])
				{
					default:
						$this->sendMessage($sender, "§c指令使用错误, 请输入 §d/§6".self::MY_COMMAND." ft §c查看帮助.");
					break;
					
					
					case "spawn":
						$pos = $sender->getFloorX().Main::VECTOR_DIVISION.$sender->getFloorY().Main::VECTOR_DIVISION.$sender->getFloorZ().Main::VECTOR_DIVISION.$sender->getLevel()->getName();
						$this->plugin->config()->set(Main::CONFIG_LEADERBOARD_POSITION, $pos);
						$this->plugin->config()->save();
						$this->sendMessage($sender, "§a浮空字排行榜坐标数据保存成功, 准备生成浮空字...");
						$this->plugin->spawnFloatingText();
						return true;
					break;
					
					
					case "edit":
						if(!isset($args[2]) || (isset($args[2]) && !is_numeric($args[2])))
						{
							$this->sendMessage($sender, "用法: §d/§6".self::MY_COMMAND." ft edit §f<§e显示数量§f> 设置浮空字排行榜每页显示的场地数量");
							return true;
						}
						if((int) $args[2] > 5)
						{
							$this->sendMessage($sender, "§c显示数量过多会导致浮空字排行榜拥挤, 不建议设置过多数据挤在同一页面.");
							return true;
						}
						$this->plugin->config()->set(Main::CONFIG_LEADERBOARD_DISPLAY_PER_PAGE, $args[2]);
						$this->plugin->config()->save();
						$this->sendMessage($sender, "§a浮空字排行榜显示状态已设置为: §d{$args[2]}§e个场地数据§f/§e页");
						return true;
					break;
					
					
					case "tphere":
						$pos = $sender->getFloorX().Main::VECTOR_DIVISION.$sender->getFloorY().Main::VECTOR_DIVISION.$sender->getFloorZ().Main::VECTOR_DIVISION.$sender->getLevel()->getName();
						$this->plugin->config()->set(Main::CONFIG_LEADERBOARD_POSITION, $pos);
						$this->plugin->config()->save();
						$this->plugin->config()->reload();
						$this->sendMessage($sender, "§a已将浮空字排行榜坐标移动到你当前的位置.");
						return true;
					break;
					
					
					case "tpto":
						$pos = $this->plugin->getVector($this->plugin->config()->get(Main::CONFIG_LEADERBOARD_POSITION));
						$pos = new \pocketmine\level\Position((float) $pos[0], (float) $pos[1], (float) $pos[2], $this->plugin->getServer()->getLevelByName($pos[3]));
						$sender->teleport($pos);
						$this->sendMessage($sender, "§a已将你传送到浮空字排行榜的坐标位置.");
						return true;
					break;
					
					
					case "del":
						$this->plugin->shutdownFloatingText();
						$this->plugin->config()->set(Main::CONFIG_LEADERBOARD_POSITION, \null);
						$this->plugin->config()->save();
						$this->sendMessage($sender, "§a浮空字排行榜坐标数据清除成功.");
						return true;
					break;
				}
				return true;
			break;
			
			
			case "reload":
				$this->plugin->config()->reload();
				$this->plugin->station()->reload();
				// $this->plugin->config()->save();
				// $this->plugin->station()->save();
				$this->sendMessage($sender, "§a配置文件重载完成.");
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
				if(!$this->hasSenderPermission($sender, $args[0]))
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
		catch(\Exception $e)
		{
			$this->plugin->getTSApi()->ssm(Main::NORMAL_PRE."§c用户 §e{$senderName} §c在执行指令的时候出现了一个问题. 错误说明如下:", "warning", "server");
			$this->plugin->getTSApi()->ssm(Main::NORMAL_PRE."§fAn Exception with class {$e->getFile()}: {$e->getMessage()} at line {$e->getLine()}", "warning", "server");
			$this->sendMessage($sender, "§c在执行指令的时候出现了一个问题. 错误说明: §f".$e->getMessage());
			return true;
		}
	}
	
	public static function getCommandPermission(string $cmd)
	{
		$cmds = 
		[
			"edit"   => [self::PERMISSION_CONSOLE, self::PERMISSION_ADMINO, self::PERMISSION_ADMINT, self::PERMISSION_OP],
			"set"    => [self::PERMISSION_CONSOLE, self::PERMISSION_ADMINO, self::PERMISSION_ADMINT, self::PERMISSION_OP],
			"del"    => [self::PERMISSION_CONSOLE, self::PERMISSION_ADMINO, self::PERMISSION_ADMINT, self::PERMISSION_OP],
			"cancel" => [self::PERMISSION_CONSOLE, self::PERMISSION_ADMINO, self::PERMISSION_ADMINT, self::PERMISSION_OP],
			"admin"  => [self::PERMISSION_CONSOLE, self::PERMISSION_ADMINO, self::PERMISSION_ADMINT, self::PERMISSION_OP],
			"wait"   => [self::PERMISSION_CONSOLE, self::PERMISSION_ADMINO, self::PERMISSION_ADMINT, self::PERMISSION_OP],
			"win10"  => [self::PERMISSION_CONSOLE, self::PERMISSION_ADMINO, self::PERMISSION_ADMINT, self::PERMISSION_OP],
			"modify" => [self::PERMISSION_CONSOLE, self::PERMISSION_ADMINO, self::PERMISSION_ADMINT, self::PERMISSION_OP],
			"ft"     => [self::PERMISSION_CONSOLE, self::PERMISSION_ADMINO, self::PERMISSION_ADMINT, self::PERMISSION_OP],
			"reload" => [self::PERMISSION_CONSOLE, self::PERMISSION_ADMINO, self::PERMISSION_ADMINT, self::PERMISSION_OP],
			#-------------------------------------------#;
			"join"   => [self::PERMISSION_ALL],
			"tp"     => [self::PERMISSION_ALL],
		];
		
		$cmd = strtolower($cmd);
		return isset($cmds[$cmd]) ? $cmds[$cmd] : self::$ifCommandPermissionNotFound;
	}
	
	public static function getHelpMessage() : array
	{
		return 
		[
			"set"    => "用法: §d/§6{cmd} set §f<§e节点ID§f> §f<§e倒计时秒§f>          §a添加§f一个游戏节点",
			"del"    => "用法: §d/§6{cmd} del §f<§e节点ID§f>                     §c删除§f一个游戏节点",
			"admin"  => "用法: §d/§6{cmd} admin §f<§e管理员名称§f>               设置或移除一个游戏节点管理员",
			"cancel" => "用法: §d/§6{cmd} cancel                           §f清空当前的设置状态",
			"wait"   => "用法: §d/§6{cmd} wait §f<§e等待时间秒§f>                设置玩家的默认等待时间(全部游戏节点统一设置)",
			"win10"  => "用法: §d/§6{cmd} win10                            §f设置默认配置文件, 游戏节点是否允许WIN10玩家使用",
			"modify" => "用法: §d/§6{cmd} modify §f<§e节点ID§f>                  §f管理一个游戏节点",
			"ft"     => "用法: §d/§6{cmd} ft spawn§f|§6edit§f|§6tphere§f|§6tpto§f|§6del    §e生成§f|§e编辑§f|§e传送到这里§f|§e传送到那去§f|§e删除§f浮空字排行榜",
			"reload"     => "用法: §d/§6{cmd} reload                           §f重载并保存配置文件",
			#-------------------------------------------#;
			"join"   => "用法: §d/§6{cmd} join §f<§e节点ID§f>                    §f加入一个游戏节点",
			"tp"     => "用法: §d/§6{cmd} tp §f<§e节点ID§f>                      §f传送到一个游戏节点",
		];
	}
	
	
	private function getCacheSettingInfo(CommandSender $sender, bool $display =\true)
	{
		$info = $this->plugin->getCacheSettingInfo($sender->getName());
		if(count($info) > 0)
		{
			if($display)
			{
				$this->sendMessage($sender, "§c你正在设置游戏节点 §e".$info["name"]." §c, 请将此节点设置完成之后再设置其他节点.");
				$this->sendMessage($sender, "§c你也可以使用指令 §f\"§d/§6".self::MY_COMMAND." cancel§f\" 取消该节点的设置.");
			}
			unset($info);
			return true;
		}
		else return false;
	}
	
	
}
?>