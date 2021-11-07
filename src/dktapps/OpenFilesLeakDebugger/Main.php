<?php

namespace dktapps\OpenFilesLeakDebugger;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Utils;

class Main extends PluginBase{

	public static $dudPipes = [];

	public function onEnable(){

		@mkdir($this->getDataFolder() . "dudFiles", 0777, true);
		//Open some files to make sure we can close some to make space for proc_open() if the error should occur
		if(empty(self::$dudPipes)){
			for($i = 0; $i < 20; ++$i){
				self::$dudPipes[$i] = fopen($this->getDataFolder() . "dudFiles" . DIRECTORY_SEPARATOR . "randomFile$i.txt", "wb");
			}
		}

		//Getting OS might require opening file handles when we can't open any more, so get this at the start
		$os = Utils::getOS();

		set_error_handler(function($severity, $message, $file, $line) use ($os){
			if(strpos($message, "Too many open files") !== false or strpos($message, "No file descriptors available") !== false){
				foreach(self::$dudPipes as $pipe){
					fclose($pipe);
				}
				self::$dudPipes = [];
				switch($os){
					case "linux":
					case "android":
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
				$this->getLogger()->emergency("File descriptor leak results:");
				$this->getLogger()->emergency("stdout:\n$stdout");
				$this->getLogger()->emergency("stderr:\n$stderr");
				$this->getServer()->shutdown();
			}

			a:
			Utils::errorExceptionHandler($severity, $message, $file, $line);
		});

		//For testing the plugin itself only.
		if((bool) $this->getConfig()->get("test-mode", false)){
			$this->runTest();
		}
	}

	private function runTest() : void{
		$this->getLogger()->notice("Running leak detection test. The server will freeze and may crash.");

		$testFiles = [];
		$directory = $this->getDataFolder() . "spamFiles" . DIRECTORY_SEPARATOR;

		@unlink($directory);
		@mkdir($directory, 0777, true);

		while(true){
			try{
				$testFiles[] = fopen($directory . bin2hex(random_bytes(4)) . ".txt", "wb");
			}catch(\ErrorException $e){
				$this->getLogger()->logException($e);
				foreach($testFiles as $file){
					fclose($file);
				}
				$this->getServer()->shutdown();
				break;
			}
		}
	}
}
