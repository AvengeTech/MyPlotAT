<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use MyPlot\forms\subforms\HomeForm;
use MyPlot\Plot;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class HomeSubCommand extends SubCommand
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
		if(count($args) === 0) {
			$plotNumber = 1;
		}elseif(is_numeric($args[0])) {
			$plotNumber = (int) $args[0];
		}else{
			$sender->sendMessage(TextFormat::GOLD . "Usage: /p home [number] [type (basic, nether, end)]");
			return true;
		}
		$gwf = fn (string $check) => match (true) {
			strtolower($check) === "basic" => "s4plots",
			strtolower($check) === "nether" => "nether_plots_s4",
			strtolower($check) === "end" => "end_plots_s4",
			default => $check
		};
		$gnf = fn (string $name) => match (true) {
			stristr($name, "end") !== false => "End Plots",
			stristr($name, "nether") !== false => "Nether Plots",
			default => "Basic Plots"
		};
		$levelName = $args[1] ?? $sender->getWorld()->getFolderName();
		$levelName = $gwf($levelName);
		$plots = $this->getPlugin()->getPlotsOfPlayer((int)$sender->getXuid(), $levelName);
		if(count($plots) === 0) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("home.noplots"));
			return true;
		}
		usort($plots, function(Plot $plot1, Plot $plot2) {
			if($plot1->levelName == $plot2->levelName) {
				return 0;
			}
			return ($plot1->levelName < $plot2->levelName) ? -1 : 1;
		});
		if(!isset($plots[$plotNumber - 1])) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("home.notexist", [$plotNumber]));
			return true;
		}
		$plot = $plots[$plotNumber - 1];
		if($this->getPlugin()->teleportPlayerToPlot($sender, $plot)) {
			$sender->sendMessage($this->translateString("home.success", [$plot->__toString(), $gnf($plot->levelName)]));
		}else{
			$sender->sendMessage(TextFormat::RED . $this->translateString("home.error"));
		}
		return true;
	}

	public function getForm(?Player $player = null) : ?MyPlotForm {
		return null;
	}
}