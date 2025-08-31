<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use MyPlot\forms\MyPlotForm;
use MyPlot\forms\subforms\ClaimForm;
use MyPlot\MyPlot;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

use core\utils\TextFormat;
use prison\PrisonPlayer;

class ClaimSubCommand extends SubCommand
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
		if (!$sender instanceof PrisonPlayer) return false;
		$name = "";
		$force = false;
		if(isset($args[0])) {
			$name = $args[0];
			if ($name === "-f" && $sender->isTier3()) {
				$force = true;
				$name = "";
			}
		}
		$plot = $this->getPlugin()->getPlotByPosition($sender->getPosition());
		if($plot === null) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
			return true;
		}
		if ($plot->hasOwner() && !$force) {
			if ($plot->owner === $sender->getXuid()) {
				$sender->sendMessage(TextFormat::RED . $this->translateString("claim.yourplot"));
			}else{
				$sender->sendMessage(TextFormat::RED . $this->translateString("claim.alreadyclaimed", [$plot->owner]));
			}
			return true;
		}
		$maxPlots = $this->getPlugin()->getMaxPlotsOfPlayer($sender);
		$world = $sender->getWorld();

		switch($world->getDisplayName()){
			case "nether_plots_s0":
				$price = 250000;
				if ($sender->getPrestige() < 1 && !$force) {
					$sender->sendMessage(TextFormat::RI . "You must prestige at least once to claim nether plots!");
					return true;
				}
				break;
			case "end_plots_s0":
				$price = 1000000;
				if ($sender->getPrestige() < 5 && !$force) {
					$sender->sendMessage(TextFormat::RI . "You must prestige at least " . TextFormat::YELLOW . "5 " . TextFormat::GRAY . "times to claim end plots!");
					return true;
				}
				break;
			default:
				$price = 5000;
				break;
		}

		$plotsOfPlayer = count($this->getPlugin()->getPlotsOfPlayer((int)$sender->getXuid(), $world->getDisplayName()));
		if ($plotsOfPlayer >= $maxPlots && !$force) {
			$sender->sendMessage(TextFormat::RI . $this->translateString("claim.maxplots", [$maxPlots]));
			return true;
		}

		if ($sender->getTechits() < $price && !$force) {
			$sender->sendMessage(TextFormat::RI . "You need " . TextFormat::AQUA . number_format($price) . " techits" . TextFormat::GRAY . " to claim plots in this world!");
			return true;
		}

		if ($this->getPlugin()->claimPlot($plot, (int)$sender->getXuid(), $name)) {
			if (!$force) $sender->takeTechits($price);
			$sender->sendMessage(TextFormat::GI . $this->translateString("claim.success"));
		}else{
			$sender->sendMessage(TextFormat::RI . $this->translateString("error"));
		}
		return true;
	}

	public function getForm(?Player $player = null) : ?MyPlotForm {
		return null;
	}
}