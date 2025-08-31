<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use core\AtPlayer;
use core\Core;
use core\user\User;
use MyPlot\forms\MyPlotForm;
use MyPlot\forms\subforms\UndenyPlayerForm;
use MyPlot\Plot;
use pocketmine\command\CommandSender;
use pocketmine\player\OfflinePlayer;
use pocketmine\player\Player;
use pocketmine\Server;
use core\utils\TextFormat;

class UnDenySubCommand extends SubCommand
{
	public function canUse(CommandSender $sender) : bool {
		return ($sender instanceof AtPlayer);
	}

	/**
	 * @param AtPlayer $sender
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) : bool {
		if(count($args) === 0) {
			return false;
		}
		$dplayerName = $args[0];
		$plot = $this->getPlugin()->getPlotByPosition($sender->getPosition());
		if($plot === null) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
			return true;
		}
		if ($plot->owner !== (int)$sender->getXuid() and !$sender->isTier3()) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
			return true;
		}
		$dplayer = $this->getPlugin()->getServer()->getPlayerByPrefix($dplayerName);
		if (!$dplayer instanceof AtPlayer) {
			Core::getInstance()->getUserPool()->useUser($args[0], function (User $user) use ($sender, $plot) {
				if (!$sender instanceof AtPlayer || !$sender->isConnected()) return;
				if (!$user->valid()) {
					$sender->sendMessage(TextFormat::RN . "Player never seen!");
					return;
				}
				if ($this->getPlugin()->removePlotDenied($plot, $user->getXuid())) {
					$sender->sendMessage($this->translateString("undenyplayer.success1", [$user->getGamertag()]));
				} else {
					$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
				}
			}, true);
			return true;
		}
		if ($this->getPlugin()->removePlotDenied($plot, (int)$dplayer->getXuid())) {
			$sender->sendMessage($this->translateString("undenyplayer.success1", [$dplayer->getName()]));
			$dplayer->sendMessage($this->translateString("undenyplayer.success2", [$plot->X, $plot->Z, $sender->getName()]));
		} else {
			$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
		}
		return true;
	}

	public function getForm(?Player $player = null) : ?MyPlotForm {
		return null;
	}
}