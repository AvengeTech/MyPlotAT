<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use core\AtPlayer;
use core\Core;
use core\user\User;
use MyPlot\forms\MyPlotForm;
use MyPlot\forms\subforms\RemoveHelperForm;
use MyPlot\Plot;
use pocketmine\command\CommandSender;
use pocketmine\player\OfflinePlayer;
use pocketmine\player\Player;
use pocketmine\Server;
use core\utils\TextFormat;

class RemoveHelperSubCommand extends SubCommand
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
		$helperName = $args[0];
		$plot = $this->getPlugin()->getPlotByPosition($sender->getPosition());
		if($plot === null) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
			return true;
		}
		if ($plot->owner !== (int)$sender->getXuid() and !$sender->isTier3()) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
			return true;
		}
		$helper = $this->getPlugin()->getServer()->getPlayerByPrefix($helperName);
		if ($helper === null) {
			Core::getInstance()->getUserPool()->useUser($helperName, function (User $user) use ($sender, $plot): void {
				if (!$sender instanceof AtPlayer || !$sender->isConnected()) return;
				if (!$user->valid()) {
					$sender->sendMessage(TextFormat::RN . "Player never seen!");
					return;
				}
				if ($this->getPlugin()->removePlotHelper($plot, $user->getXuid())) {
					$sender->sendMessage(TextFormat::GN . "Removed " . TextFormat::YELLOW . $user->getGamertag() . TextFormat::GREEN . " as a helper!");
				} else {
					$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
				}
			});
		} else {
			if ($this->getPlugin()->removePlotHelper($plot, (int)$helper->getXuid())) {
				$sender->sendMessage(TextFormat::GN . "Removed " . TextFormat::YELLOW . $helper->getName() . TextFormat::GREEN . " as a helper!");
			} else {
				$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
			}
		}
		return true;
	}

	public function getForm(?Player $player = null) : ?MyPlotForm {
		return null;
	}
}