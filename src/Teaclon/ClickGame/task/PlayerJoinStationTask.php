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
use Teaclon\TSeriesAPI\task\PluginTask;
// use pocketmine\scheduler\PluginTask;


class PlayerJoinStationTask extends PluginTask
{
	private $plugin;
	private $con;
	private $c = 0;
	public function __construct(\Teaclon\ClickGame\Main $plugin)
	{
		$this->plugin = $plugin;
		$this->con = $this->plugin->station();
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
			$this->server->getLogger()->info(self::NORMAL_PRE."§c服务器无法找到所依赖的插件, 无法使用本插件!");
			$this->server->getPluginManager()->disablePlugin($this);
			return null;
		}
		// var_dump($this->c++);
		$onlinePlayers = $this->plugin->getServer()->getOnlinePlayers();
		if(count($onlinePlayers) == 0) return null;
		
		foreach($onlinePlayers as $p)
		{
			$n  = $p->getName();
			$ln = $p->getLevel()->getName();
			$px = $p->getFloorX();    $py = $p->getFloorY();    $pz = $p->getFloorZ();
			
			if($this->plugin->isTempStationInLevel($ln))
			{
				$stations = $this->plugin->temp_level_with_station[$ln];
				if(count($stations) > 1)
				{
					foreach($stations as $station)
					{
						$s_con  = $this->con->get($station);
						$s      = $s_con["start_point"];
						$s_name = $station;
						
						if($this->plugin->getTempPlayerDataWith($n, "waiting_status") || $this->plugin->getTempPlayerDataWith($n, "isJoin")) return null;
						if($this->plugin->getTempStationData($s_con["name"])["status"]) return null;
						if(($ln === $s_con["level"]) && ($px == $s[0]) && ($py - 1 == $s[1]) && ($pz == $s[2]))
						{
							if($this->plugin->setTempPlayerData($n, "waiting_status", true))
						$this->plugin->getTSApi()->getTaskManager()->registerTask("scheduleRepeatingTask", new PlayerInStationWaitingTask($this->plugin, $p, $s_name), 20, \false);
							// unset($s_con, $s, $n, $ln, $px, $py, $pz, $s_con, $s);
						}
					}
					
				}
				else
				{
					$s_con   = $this->con->get($stations[0]);
					$s_name  = $stations[0];
					$s_level = $s_con["level"];
					$s       = $s_con["start_point"];
					
					if($this->plugin->getTempPlayerDataWith($n, "waiting_status") || $this->plugin->getTempPlayerDataWith($n, "isJoin")) return null;
					if($this->plugin->getTempStationData($s_name)["status"]) return null;
					if(($ln === $s_level) && ($px == $s[0]) && ($py - 1 == $s[1]) && ($pz == $s[2]))
					{
						if($this->plugin->setTempPlayerData($n, "waiting_status", true))
					$this->plugin->getTSApi()->getTaskManager()->registerTask("scheduleRepeatingTask", new PlayerInStationWaitingTask($this->plugin, $p, $s_name), 20, \false);
						// unset($s_con, $s, $n, $ln, $px, $py, $pz, $s_con, $s);
					}
				}
			}
		}
	}
	
	
	
}
?>