<?php

namespace Khaled;

use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\Task;
use slapper\events\SlapperCreationEvent;

class PlayerCount extends PluginBase implements Listener{

	public function onEnable()
	{
		if(!$this->getServer()->getPluginManager()->getPlugin("Slapper")){
			$this->getServer()->getPluginManager()->disablePlugin($this);
			$this->getLogger()->emergency("You need slapper installed, disabled plugin...");
			return;
		}
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->saveDefaultConfig();
		$this->saveResource("config.yml");
		$this->getScheduler()->scheduleRepeatingTask(new RefreshCount($this), 10);
		$worlds = $this->getConfig()->get("worlds");
		foreach ($worlds as $world){
			if (file_exists($this->getServer()->getDataPath()."/worlds/".$world)){
				$this->getServer()->loadLevel($world);
			}
		}

	}

	public function slapperCreation(SlapperCreationEvent $ev){
		$entity = $ev->getEntity();
		$name = $entity->getNameTag();
		$pos = strpos($name, "count ");
		if($pos !== false){
			$levelname = str_replace("count ", "", $name);
			if(file_exists($this->getServer()->getDataPath()."/worlds/".$levelname)){
				if (!$this->getServer()->isLevelLoaded($levelname)) $this->getServer()->loadLevel($levelname);
				$entity->namedtag->setString("isPlayerCount", "yes");
				$entity->namedtag->setString("isPlayerCountLevel", $levelname);
				$search = array(
					"{world}",
					"{number}"
				);
				$replace = array(
					$levelname,
					count($this->getServer()->getLevelByName($levelname)->getPlayers())
				);
				$msg = $this->getConfig()->get("NameTag");
				$msg2 = str_replace($search, $replace, $msg);
				$entity->setNameTag($msg2);
				//$entity->setNameTag("Players playing in  ".$levelname." : ".count($this->getServer()->getLevelByName($levelname)->getPlayers()));
				$worlds = $this->getConfig()->get("worlds");
				if(!in_array($levelname, $worlds)){
					array_push($worlds, $levelname);
					$this->getConfig()->set("worlds", $worlds);
					$this->getConfig()->save();
				} else {
					return;
				}
			}
		}
	}

	public function playerCount(){
		$levels = $this->getServer()->getLevels();
		foreach ($levels as $level){
			$entities = $level->getEntities();
			foreach ($entities as $entity){
				$nbt = $entity->namedtag;
				if($nbt->hasTag("isPlayerCount")){
					if($nbt->getString("isPlayerCount") === "yes"){
						$world = $nbt->getString("isPlayerCountLevel");
						$search = array(
							"{world}",
							"{number}"
						);
						$replace = array(
							$world,
							count($this->getServer()->getLevelByName($world)->getPlayers())
						);
						$msg = $this->getConfig()->get("NameTag");
						$msg2 = str_replace($search, $replace, $msg);
						$entity->setNameTag($msg2);
						//$entity->setNameTag("Players playing in ".$world." : ".count($this->getServer()->getLevelByName($world)->getPlayers()));
					}
				}
			}
		}
	}
}

class RefreshCount extends Task{
	public $main;
	public function __construct(PlayerCount $main)
	{
		$this->plugin = $main;
	}

	public function onRun(int $currentTick)
	{
		$this->plugin->playerCount();
	}
}
