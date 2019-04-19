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


class CountdownTask extends PluginTask
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
			$this->server->getLogger()->info(self::NORMAL_PRE."§c服务器无法找到所依赖的插件, 无法使用本插件!");
			$this->server->getPluginManager()->disablePlugin($this);
			return null;
		}
		
		if(!$this->plugin->getTempPlayerData($this->temp_name))
		{
			$this->plugin->getServer()->getLogger()->info($this->plugin::NORMAL_PRE."[游戏中状态] §c由于玩家 §e{$this->temp_name} §c引起的未知原因导致该回合结束.");
			$this->plugin->resetStation($this->station_id);
			$this->plugin->removePlayerInStation($this->temp_name, $this->station_id);
			
			$this->plugin->getTSApi()->getTaskManager()->cancelTask($this);
			unset($this->plugin, $this->station_id, $this->temp_name);
			return false;
		}
		
		$n = $this->player->getName();
		
		if($this->plugin->getTempPlayerDataWith($n, "waiting_status"))
		{
			$info = $this->plugin->getTempStationData($this->station_id);
			if($info["countdown"] != 0)
			{
				$this->player->sendTip($this->plugin::NORMAL_PRE."§e你还有 §c{$info["countdown"]} §e秒");
				$this->plugin->reduceTempStationCountDown($this->station_id);
			}
			else
			{
				$os = ($this->plugin->getTSApi()->getDeviceOS($n) == 7) ? "[WIN10] " : "[{$this->plugin->getTSApi()->getStringDeviceOS($this->plugin->getTSApi()->getDeviceOS($n))}] ";
				$ct = $this->plugin->station()->get($this->station_id)["countdown"];
				$ln = $this->plugin->getLevelFromTempStation($this->station_id);
				
				$message = "§c游戏结束!";
				if(method_exists('\pocketmine\Player', "addTitle"))
					$this->player->addTitle($message, $this->plugin::NORMAL_PRE, 2, 90, 2);
				elseif(method_exists('\pocketmine\Player', "sendTitle"))
					$this->player->sendTitle($message, $this->plugin::NORMAL_PRE, 2, 90, 2);
				else
					$this->player->sendMessage($message);
				
				$this->player->sendTip($this->plugin::NORMAL_PRE.$message);
				$clicktime = $this->plugin->getTempPlayerDataWith($n, "clicktime");
				if($clicktime > 0)
				{
					$this->player->sendMessage($this->plugin::NORMAL_PRE."§a你点击了 §b{$clicktime} §a次~");
					$this->plugin->getServer()->broadcastMessage($this->plugin::NORMAL_PRE.$os."§a玩家 §e{$this->temp_name} §a点击了 §b{$clicktime} §a次~");
				}
				else
				{
					$this->player->sendMessage($this->plugin::NORMAL_PRE."§e兄弟, 多练练手速哇, 你也忒慢了, 游戏都超时了~");
					$this->plugin->getServer()->broadcastMessage($this->plugin::NORMAL_PRE.$os."§e玩家 §e{$this->temp_name} §e手残了, 没点到漂亮的方块~");
				}
				$this->plugin->getServer()->broadcastMessage($this->plugin::NORMAL_PRE.$os."§d场地:  §e{$ln} §f- §e{$this->station_id}; §d时间: §e{$ct} §d秒");
				$this->plugin->savePlayerInTempStationData($n, $this->station_id);
				$this->plugin->resetStation($this->station_id);
				$this->plugin->removePlayerInStation($n, $this->station_id);
				$this->plugin->getTSApi()->getTaskManager()->cancelTask($this);
				unset($this->plugin, $this->player, $this->station_id, $n, $info, $clicktime, $message, $ct);
			}
		}
	}
	
	
	
}
?>