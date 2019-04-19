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

namespace Teaclon\ClickGame\task;

use pocketmine\Player;
use pocketmine\Server;
// use pocketmine\scheduler\PluginTask;
use Teaclon\TSeriesAPI\task\PluginTask;

// Sounds;
use pocketmine\math\Vector3;
use pocketmine\level\sound\ClickSound;
use pocketmine\level\sound\AnvilFallSound;


class PlayerInStationWaitingTask extends PluginTask
{
	private $plugin;
	private $player;
	private $station_id;
	private $temp_name;
	
	public function __construct(\Teaclon\ClickGame\Main $plugin, Player $player, $station_id)
	{
		$this->plugin     = $plugin;
		$this->player     = $player;
		$this->station_id = $station_id;
		$this->temp_name  = $player->getName();
		parent::__construct($plugin);
	}
	
	/* public function onRun($tick)
	{
		$this->me($tick);
	} */
	
	public function me($tick)
	{
		if(!method_exists($this->plugin, "getTSApi"))
		{
			$this->plugin->getServer()->getLogger()->info(self::NORMAL_PRE."§c服务器无法找到所依赖的插件, 无法使用本插件!");
			$this->plugin->getServer()->getPluginManager()->disablePlugin($this);
			return null;
		}
		if((!$this->player instanceof Player) || !($this->plugin->getTempPlayerData($this->temp_name)))
		{
			$this->plugin->getServer()->getLogger()->info($this->plugin::NORMAL_PRE."[等待状态] §c由于玩家 §e{$this->temp_name} §c引起的未知原因导致该回合结束.");
			$this->plugin->getServer()->getScheduler()->cancelTask($this->getTaskId());
			unset($this->plugin, $p, $this->station_id, $this->temp_name, $n);
			return false;
		}
		
		$p  = $this->player;
		$n  = $p->getName();
		$ln = $p->getLevel()->getName();
		$px = $p->getFloorX();    $py = $p->getFloorY();    $pz = $p->getFloorZ();
		
		if(($this->plugin->station()->get($this->station_id)["ban_win10"] == true) && ($this->plugin->getTSApi()->getDeviceOS($n) == 7))
		{
			$message = "§c本服务器已禁止§eWIN10§c的用户使用该游戏!";
			
			if(method_exists('\pocketmine\Player', "addTitle"))
				$p->addTitle($message, $this->plugin::NORMAL_PRE, 2, 90, 2);
			elseif(method_exists('\pocketmine\Player', "sendTitle"))
				$p->sendTitle($message, $this->plugin::NORMAL_PRE, 2, 90, 2);
			else
				$p->sendMessage($message);
			
			$this->plugin->getServer()->getScheduler()->cancelTask($this->getTaskId());
			unset($this->plugin, $p, $this->station_id, $n);
			return false;
		}
		
		if($this->plugin->getTempPlayerDataWith($n, "waiting_status"))
		{
			$countdown = $this->plugin->getTempPlayerDataWith($n, "waiting_time");
			$level = $this->plugin->getServer()->getLevelByName($ln);
			if($countdown != 0)
			{
				$message = "§a{$countdown} §e秒后开始游戏...";
				if(@\method_exists($level, "addSound")) $level->addSound(new ClickSound(new Vector3($px, $py, $pz)), [$p]);
				
				if(method_exists('\pocketmine\Player', "addTitle"))
					$p->addTitle($message, $this->plugin::NORMAL_PRE, 2, 150, 2);
				elseif(method_exists('\pocketmine\Player', "sendTitle"))
					$p->sendTitle($message, $this->plugin::NORMAL_PRE, 2, 150, 2);
				else
					$p->sendMessage($message);
				
				$this->plugin->setTempPlayerData($n, "waiting_time", --$countdown);
			}
			else
			{
				if($this->plugin->setTempPlayerData($n, "isJoin", true))
				{
					$message = "§a游戏开始了!";
					if(@\method_exists($level, "addSound")) $level->addSound(new AnvilFallSound(new Vector3($px, $py, $pz)), [$p]);
					
					if(method_exists('\pocketmine\Player', "addTitle"))
						$p->addTitle($message, $this->plugin::NORMAL_PRE, 2, 90, 2);
					elseif(method_exists('\pocketmine\Player', "sendTitle"))
						$p->sendTitle($message, $this->plugin::NORMAL_PRE, 2, 90, 2);
					else
						$p->sendMessage($message);
				
					$num = (count($this->plugin->temp_level_with_station[$ln]) > 1) ? "多" : "单";
					$this->plugin->getServer()->broadcastMessage($this->plugin::NORMAL_PRE."§f[§6{$num}§e场地§f] §d玩家 §b{$n} §d加入了位于世界 §a{$ln} §d的游戏场地 §e".($this->plugin->station()->get($this->station_id)["name"])." §d.");
					$this->plugin->initPlayerInStation($p, $this->station_id);
					$this->plugin->getTSApi()->getTaskManager()->registerTask("scheduleRepeatingTask", new CountdownTask($this->plugin, $p, $this->station_id), 20, \false);
					$this->plugin->getTSApi()->getTaskManager()->cancelTask($this);
					unset($this->plugin, $p, $this->station_id, $n, $countdown, $ln, $num, $message);
				}
			}
		}
	}
	
	
	
}
?>