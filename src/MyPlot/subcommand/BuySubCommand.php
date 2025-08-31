<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use core\AtPlayer;
use MyPlot\forms\MyPlotForm;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class BuySubCommand extends SubCommand
{
	public function canUse(CommandSender $sender) : bool {
		return $sender instanceof AtPlayer && $sender->isTier3();
	}

	/**
	 * @param AtPlayer $sender
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args) : bool {
		if($this->getPlugin()->getEconomyProvider() === null){
			$command = new ClaimSubCommand($this->getPlugin(), "claim");
			return $command->execute($sender, []);
		}
		$plot = $this->getPlugin()->getPlotByPosition($sender->getPosition());
		if($plot === null){
			$sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
			return true;
		}
		if ($plot->owner === $sender->getXuid() and !$sender->isTier3()) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("buy.noself"));
			return true;
		}
		if($plot->price <= 0){
			$sender->sendMessage(TextFormat::RED . $this->translateString("buy.notforsale"));
			return true;
		}
		$maxPlots = $this->getPlugin()->getMaxPlotsOfPlayer($sender);
		$plotsOfPlayer = 0;
		foreach($this->getPlugin()->getPlotLevels() as $level => $settings) {
			$level = $this->getPlugin()->getServer()->getWorldManager()->getWorldByName((string)$level);
			if($level !== null and $level->isLoaded()) {
				$plotsOfPlayer += count($this->getPlugin()->getPlotsOfPlayer($sender->getXuid(), $level->getFolderName()));
			}
		}
		if($plotsOfPlayer >= $maxPlots) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("claim.maxplots", [$maxPlots]));
			return true;
		}
		$price = $plot->price;
		if(strtolower($args[0] ?? "") !== $this->translateString("confirm")){
			$sender->sendMessage($this->translateString("buy.confirm", ["{$plot->X};{$plot->Z}", $price]));
			return true;
		}
		$oldOwner = $this->getPlugin()->getServer()->getPlayerByPrefix($plot->owner);
		if($this->getPlugin()->buyPlot($plot, $sender)) {
			$sender->sendMessage($this->translateString("buy.success", ["{$plot->X};{$plot->Z}", $price]));
			if($oldOwner !== null)
				$oldOwner->sendMessage($this->translateString("buy.sold", [$sender->getXuid(), "{$plot->X};{$plot->Z}", $price])); // TODO: queue messages for sending when player rejoins
		}else{
			$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
		}
		return true;
	}

	public function getForm(?Player $player = null) : ?MyPlotForm {
		// TODO: Implement getForm() method.
		return null;
	}
}