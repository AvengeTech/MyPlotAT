<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use core\AtPlayer;
use MyPlot\forms\MyPlotForm;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class MiddleSubCommand extends SubCommand
{
	public function canUse(CommandSender $sender) : bool {
		return ($sender instanceof AtPlayer) and ($sender->hasPermission("prison.perm"));
	}

	/**
	 * @param AtPlayer $sender
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) : bool {
		if(count($args) != 0) {
			return false;
		}
		$plot = $this->getPlugin()->getPlotByPosition($sender->getPosition());
		if($plot === null) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
			return true;
		}
		if ($plot->owner !== (int)$sender->getXuid() and !$sender->isStaff()) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
			return true;
		}
		if($plot->levelName === "end_plotsnew" && $plot->X == 0 && $plot->Z == 1){
			$sender->sendMessage(TextFormat::RED . "Cannot teleport to the middle of this plot.");
			return true;
		}
		if($this->getPlugin()->teleportPlayerToPlot($sender, $plot, true)) {
			$sender->sendMessage(TextFormat::GREEN . $this->translateString("middle.success"));
		}
		return true;
	}

	public function getForm(?Player $player = null) : ?MyPlotForm {
		return null;
	}
}