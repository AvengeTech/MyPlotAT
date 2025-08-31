<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use core\AtPlayer;
use MyPlot\forms\MyPlotForm;
use MyPlot\forms\subforms\OwnerForm;
use MyPlot\Plot;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

use core\utils\TextFormat;

class SetOwnerSubCommand extends SubCommand {
	public function canUse(CommandSender $sender) : bool {
		return $sender instanceof AtPlayer && $sender->isTier3();
	}

	/**
	 * @param Player $sender
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) : bool {
		$sender->sendMessage(TextFormat::RI . "Out of service...");
		return true;
		if(count($args) === 0) {
			return false;
		}
		$plot = $this->getPlugin()->getPlotByPosition($sender->getPosition());
		if($plot === null) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
			return true;
		}
		$maxPlots = $this->getPlugin()->getMaxPlotsOfPlayer($sender);
		$plotsOfPlayer = 0;
		foreach($this->getPlugin()->getPlotLevels() as $level => $settings) {
			$level = $this->getPlugin()->getServer()->getWorldManager()->getWorldByName($level);
			if($level !== null and $level->isLoaded()) {
				$plotsOfPlayer += count($this->getPlugin()->getPlotsOfPlayer($sender->getXuid(), $level->getFolderName()));
			}
		}
		if($plotsOfPlayer >= $maxPlots) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("setowner.maxplots", [$maxPlots]));
			return true;
		}

		# the fuck is going on here shane

		$key = ord(strtoupper($new->getMineRank())) - ord('A') + 1;
		if($key < 4 && $new->getPrestige() < 1){
			$sender->sendMessage(TextFormat::RI . "This player must be in at least mine " . TextFormat::YELLOW . "D" . TextFormat::GRAY . " to claim a plot!");
			return true;
		}

		$new = $args[0];
		foreach($this->getPlugin()->getServer()->getOnlinePlayers() as $player) {
			if(similar_text($new,strtolower($player->getName()))/strlen($player->getName()) >= 0.3 ) { //TODO correct with a better system
				$new = $this->getPlugin()->getServer()->getPlayerExact($new);
				break;
			}
		}
		if(!$new instanceof Player) {
			$sender->sendMessage($this->translateString(TextFormat::RI . "This player is not online!"));
			return true;
		}


		switch($sender->getWorld()->getDisplayName()){
			default:
			case "new_plots":
				break;
			case "nether_plots":
				if($new->getPrestige() < 1){
					$sender->sendMessage(TextFormat::RI . "This player must prestige at least once to claim nether plots!");
					return true;
				}
				break;
			case "end_plots":
				if($new->getPrestige() < 5){
					$sender->sendMessage(TextFormat::RI . "This player must prestige at least " . TextFormat::YELLOW . "5 " . TextFormat::GRAY . "times to claim end plots!");
					return true;
				}
				break;
		}


		if ($this->getPlugin()->claimPlot($plot, intval($args[0]))) {
			$sender->sendMessage(TextFormat::GI . $this->translateString("setowner.success", [$args[0]]));
		}else{
			$sender->sendMessage(TextFormat::RI . TextFormat::RED . $this->translateString("error"));
		}
		return true;
	}

	public function getForm(?Player $player = null) : ?MyPlotForm {
		return null;
	}
}