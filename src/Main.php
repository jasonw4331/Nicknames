<?php
declare(strict_types=1);
namespace jasonwynn10\Nicknames;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class Main extends PluginBase implements Listener {

	protected Config $nicknameDB;

	public function onEnable() : void {
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->nicknameDB = new Config($this->getDataFolder()."Nicknames.json", Config::JSON);
	}

	public function onJoin(PlayerJoinEvent $event) : void {
		$player = $event->getPlayer();
		if(!$this->nicknameDB->exists($player->getName())){
			$this->nicknameDB->set($player->getName(), $player->getDisplayName());
			$this->nicknameDB->save();
		}
		$nick = $this->nicknameDB->get($player->getName(), $player->getDisplayName());
		$player->setDisplayName(TextFormat::clean($nick, false));
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool {
		if(!isset($args[0]) or (!$sender instanceof Player and !isset($args[1]))) {
			return false;
		}

		$target = $sender->getName();
		if(isset($args[1]) and $sender->hasPermission("nicknames.other")) {
			$target = $args[1];
		}elseif(!$command->testPermission($sender, "nicknames.use")) {
			return false;
		}

		if(!$this->nicknameDB->exists($target)) {
			$sender->sendMessage(TextFormat::RED."Player is not registered!");
			return true;
		}

		$player = $this->getServer()->getPlayerByPrefix($target);
		if($player === null) {
			$sender->sendMessage(TextFormat::RED."Invalid player selected");
			return true;
		}

		if($args[0] === "reset") {
			$player->setDisplayName($target);
			$sender->sendMessage(TextFormat::GREEN."Nickname reset");
			$this->nicknameDB->set($target, $target);
		}else {
			$player->setDisplayName($args[0]);
			$sender->sendMessage(TextFormat::GREEN."Nickname set to ".$args[0]);
			$this->nicknameDB->set($target, $args[0]);
		}
		$this->nicknameDB->save();
		return true;
	}
}