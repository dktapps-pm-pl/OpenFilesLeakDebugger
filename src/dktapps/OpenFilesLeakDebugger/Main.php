<?php

namespace dktapps\OpenFilesLeakDebugger;

use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\scheduler\Task;
use pocketmine\utils\Utils;

class Main extends PluginBase{

	public $dudPipes = [];
	public $testPipes = [];

	private $os;

	public function onEnable(){
		//Open some files to make sure we can close some to make space for proc_open() if the error should occur
		for($i = 0; $i < 20; ++$i){
			$this->dudPipes[$i] = fopen("randomFile$i.txt", "wb");
		}

		//Getting OS might require opening file handles when we can't open any more, so get this at the start
		$this->os = Utils::getOS();

		set_error_handler(function($severity, $message, $file, $line){
			if(strpos($message, "Too many open files") !== false){
				foreach($this->dudPipes as $pipe){
					fclose($pipe);
				}
				$this->dudPipes = [];
				switch($this->os){
					case "linux":
						$cmd = "ls -la /proc/" . getmypid() . "/fd";
						break;
					case "mac":
						$cmd = "lsof -p " . getmypid();
						break;
					default:
						$this->getLogger()->error("Operating system not supported");
						goto a; //i don't care if goto is bad, this is a debugging plugin
				}
				@Utils::execute($cmd, $stdout, $stderr);
				var_dump($stdout, $stderr);
			}

			a:
			if((error_reporting() & $severity)){
				throw new \ErrorException($message, 0, $severity, $file, $line);
			}else{ //stfu operator
				return true;
			}
		});

		//For testing the plugin itself only.
		/*$this->getServer()->getScheduler()->scheduleRepeatingTask(new class($this) extends Task{

			public function __construct(Main $plugin){
				$this->plugin = $plugin;
			}

			public function onRun(int $currentTick){
				try{
					$this->plugin->testPipes[] = fopen($this->plugin->getDataFolder() . bin2hex(random_bytes(4)) . ".txt", "wb");
				}catch(\ErrorException $e){
					$this->plugin->getLogger()->logException($e);
					$this->plugin->getServer()->getScheduler()->cancelTask($this->getHandler()->getTaskId());
				}
			}
		}, 1);*/
	}
}
