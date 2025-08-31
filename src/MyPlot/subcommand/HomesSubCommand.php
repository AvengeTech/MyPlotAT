<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class HomesSubCommand extends SubCommand
{
	public function canUse(CommandSender $sender) : bool {
		return ($sender instanceof Player);
	}

	/**
	 * @param Player $sender
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) : bool {
		$levelName = $args[0] ?? $sender->getWorld()->getFolderName();
		$plots = $this->getPlugin()->getPlotsOfPlayer((int)$sender->getXuid(), $levelName);
		if(count($plots) === 0) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("homes.noplots"));
			return true;
		}
		$gnf = fn (string $name) => match (true) {
			stristr($name, "end") !== false => "End Plots",
			stristr($name, "nether") !== false => "Nether Plots",
			default => "Basic Plots"
		};
		$message = TextFormat::DARK_GREEN . "Plots you own in " . TextFormat::GREEN . $gnf($levelName) . TextFormat::DARK_GREEN . ":";
		for($i = 0; $i < count($plots); $i++) {
			$plot = $plots[$i];
			$message .= PHP_EOL . TextFormat::DARK_GREEN . ($i + 1) . ") ";
			$message .= TextFormat::WHITE . $plot;
			if($plot->name !== "") {
				$message .= " " . $plot->name;
			}
		}
		$sender->sendMessage($message);
		return true;
	}

	public function getForm(?Player $player = null) : ?MyPlotForm {
		return null; // we can just list homes in the home form
	}
}