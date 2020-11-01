<?php
declare(strict_types=1);
namespace jasonwynn10\Nicknames;

use _64FF00\PureChat\PureChat;
use _64FF00\PurePerms\PurePerms;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class Main extends PluginBase implements Listener {

	/** @var Config $nicknameDB */
	protected $nicknameDB;
	/** @var PureChat|null $purechat */
	private $purechat;
	/** @var PurePerms|null $pureperms */
	private $pureperms;

	public function onEnable() {
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->nicknameDB = new Config($this->getDataFolder()."Nicknames.json", Config::JSON);
		$this->purechat = $this->getServer()->getPluginManager()->getPlugin("PureChat");
		$this->pureperms = $this->getServer()->getPluginManager()->getPlugin("PurePerms");
	}

	public function onJoin(PlayerJoinEvent $event) {
		$player = $event->getPlayer();
		if(!$this->nicknameDB->exists($player->getName())) {
			$this->nicknameDB->set($player->getName(), $player->getName());
			$this->nicknameDB->save();
		}
		$nick = $this->nicknameDB->get($player->getName(), $player->getName());
		$player->setDisplayName($nick);
		$this->updatePureChatNickname($player, $nick);
	}

	public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args) : bool {
		if(empty($args[0]) or (!$sender instanceof Player and empty($args[1]))) {
			return false;
		}

		$target = $sender->getName();
		if(isset($args[1]) and $sender->hasPermission("nicknames.other")) {
			$target = $args[1];
		}elseif(!$sender->hasPermission("nicknames.other")) {
			$sender->sendMessage($this->getServer()->getLanguage()->translateString(TextFormat::RED."%commands.generic.permission"));
			return false;
		}elseif(!$sender->hasPermission("nicknames.use")) {
			$sender->sendMessage($this->getServer()->getLanguage()->translateString(TextFormat::RED."%commands.generic.permission"));
			return false;
		}

		if(!$this->nicknameDB->exists($target)) {
			$sender->sendMessage(TextFormat::RED."Player is not registered!");
			return true;
		}

		$player = $this->getServer()->getPlayer($target);

		if($args[0] === "reset") {
			$player->setDisplayName($target);
			$sender->sendMessage(TextFormat::GREEN."Nickname reset");
			$this->nicknameDB->set($target, $target);
			$this->nicknameDB->save();

			$this->updatePureChatNickname($sender, $target);
		}else {
			$player->setDisplayName($args[0]);
			$sender->sendMessage(TextFormat::GREEN."Nickname set to ".$args[0]);
			$this->nicknameDB->set($target, $args[0]);
			$this->nicknameDB->save();

			$this->updatePureChatNickname($sender, $args[0]);
		}
		return true;
	}

	public function updatePureChatNickname(Player $player, string $nickname) {
		if($this->purechat !== null and $this->pureperms !== null) {
			$original = $this->purechat->getOriginalNametag($player, $player->getLevel()->getFolderName());
			$new = str_replace('{display_name}', $nickname, $original);
			$this->purechat->setOriginalNametag($this->pureperms->getUserDataMgr()->getGroup($player, $player->getLevel()->getFolderName()), $new, $player->getLevel()->getFolderName());

			$original = $this->purechat->getOriginalChatFormat($player, $player->getLevel()->getFolderName());
			$new = str_replace('{display_name}', $nickname, $original);
			$this->purechat->setOriginalChatFormat($this->pureperms->getUserDataMgr()->getGroup($player, $player->getLevel()->getFolderName()), $new, $player->getLevel()->getFolderName());
		}
	}
}