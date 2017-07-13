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

	public function onEnable(){
		//Open some files to make sure we can close some to make space for proc_open() if the error should occur
		for($i = 0; $i < 20; ++$i){
			$this->dudPipes[$i] = fopen("randomFile$i.txt", "wb");
		}
		
		/*$this->getServer()->getScheduler()->scheduleRepeatingTask(new class($this) extends Task{
			public function __construct(Main $plugin){
				$this->plugin = $plugin;
			}
			
			public function onRun($currentTick){
				try{
					$descriptor = fopen($this->plugin->getDataFolder() . "test.txt", "wb");
					fclose($descriptor);
				}catch(\ErrorException $e){
					foreach($this->plugin->dudPipes as $pipe){
						fclose($pipe);
					}
					$this->plugin->dudPipes = [];
					Utils::execute("ls -la /proc/" . getmypid() . "/fd", $stdout, $stderr);
					var_dump($stdout, $stderr);
					
					$this->plugin->getServer()->getScheduler()->cancelTask($this->getHandler()->getTaskId());
				}
			}
		}, 1);*/

		set_error_handler(function($severity, $message, $file, $line){
			if(strpos($message, "Too many open files") !== false){
				foreach($this->dudPipes as $pipe){
					fclose($pipe);
				}
				$this->dudPipes = [];
				@Utils::execute("ls -la /proc/" . getmypid() . "/fd", $stdout, $stderr);
				var_dump($stdout, $stderr);
			}

			if((error_reporting() & $severity)){
				throw new \ErrorException($message, 0, $severity, $file, $line);
			}else{ //stfu operator
				return true;
			}
		});
		
		/*$this->getServer()->getScheduler()->scheduleRepeatingTask(new class($this) extends Task{
		
			public function __construct(Main $plugin){
				$this->plugin = $plugin;
			}
			
			public function onRun($currentTick){
				try{
					$this->plugin->testPipes[] = fopen($this->plugin->getDataFolder() . bin2hex(random_bytes(4)) . ".txt", "wb");
				}catch(\ErrorException $e){
					$this->plugin->getServer()->getScheduler()->cancelTask($this->getHandler()->getTaskId());
				}
			}
		}, 1);*/
	}
}
