<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use core\AtPlayer;
use MyPlot\forms\MyPlotForm;
use MyPlot\forms\subforms\KickForm;
use MyPlot\Plot;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class KickSubCommand extends SubCommand
{
	public function canUse(CommandSender $sender) : bool {
		return ($sender instanceof Player);
	}

	/**
	 * @param AtPlayer $sender
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) : bool {
		if (!isset($args[0])) return false;
		$plot = $this->getPlugin()->getPlotByPosition($sender->getPosition());
		if($plot === null) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
			return true;
		}
		if ($plot->owner !== (int)$sender->getXuid() and !$sender->isTier3()) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
			return true;
		}
		$target = $this->getPlugin()->getServer()->getPlayerByPrefix($args[0]);
		/** @var AtPlayer $target */
		if ($target === null) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("kick.noPlayer"));
			return true;
		}
		if (($targetPlot = $this->getPlugin()->getPlotByPosition($target->getPosition())) === null || !$plot->isSame($targetPlot)) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("kick.notInPlot"));
			return true;
		}
		if ($target->isStaff()) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("kick.cannotkick"));
			$target->sendMessage($this->translateString("kick.attemptkick", [$target->getName()]));
			return true;
		}
		if ($this->getPlugin()->teleportPlayerToPlot($target, $plot)) {
			$sender->sendMessage($this->translateString("kick.success1", [$target->getName(), $plot->__toString()]));
			$target->sendMessage($this->translateString("kick.success2", [$sender->getXuid(), $plot->__toString()]));
			return true;
		}
		$sender->sendMessage($this->translateString("error"));
		return true;
	}

	public function getForm(?Player $player = null) : ?MyPlotForm {
		return null;
	}
}
