<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use core\AtPlayer;
use MyPlot\forms\MyPlotForm;
use MyPlot\forms\subforms\NameForm;
use MyPlot\Plot;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class NameSubCommand extends SubCommand
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
		$plot = $this->getPlugin()->getPlotByPosition($sender->getPosition());
		if($plot === null) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
			return true;
		}
		if ($plot->owner !== (int)$sender->getXuid() and !$sender->isStaff("mod")) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
			return true;
		}
		if ($this->getPlugin()->renamePlot($plot, implode(" ", $args))) {
			$sender->sendMessage($this->translateString("name.success"));
		}else{
			$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
		}
		return true;
	}

	public function getForm(?Player $player = null) : ?MyPlotForm {
		return null;
	}
}