<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use core\AtPlayer;
use core\Core;
use core\user\User;
use MyPlot\forms\MyPlotForm;
use MyPlot\forms\subforms\DenyPlayerForm;
use MyPlot\Plot;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use core\utils\TextFormat;

class DenyPlayerSubCommand extends SubCommand
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
		$dplayer = $args[0];
		$plot = $this->getPlugin()->getPlotByPosition($sender->getPosition());
		if($plot === null) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
			return true;
		}
		if ($plot->owner !== (int)$sender->getXuid() and !$sender->isTier3()) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
			return true;
		}

		$dplayer = $this->getPlugin()->getServer()->getPlayerByPrefix($dplayer);
		if (!$dplayer instanceof AtPlayer) {
			Core::getInstance()->getUserPool()->useUser($args[0], function (User $user) use ($sender, $plot) {
				if (!$sender instanceof AtPlayer || !$sender->isConnected()) return;
				if (!$user->valid()) {
					$sender->sendMessage(TextFormat::RN . "Player never seen!");
					return;
				}
				$isStaff = in_array($user->getRank(), ["mod", "trainee", "owner"]);
				if ($isStaff || $user->getXuid() === $plot->owner) {
					$sender->sendMessage($this->translateString("denyplayer.cannotdeny", [$user->getGamertag()]));
					return;
				}
				if ($this->getPlugin()->addPlotDenied($plot, $user->getXuid())) {
					$sender->sendMessage($this->translateString("denyplayer.success1", [$user->getGamertag()]));
				} else {
					$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
				}
			}, true);
			return true;
		}
		if ($dplayer->isStaff() or $dplayer->getXuid() === $plot->owner) {
			$sender->sendMessage($this->translateString("denyplayer.cannotdeny", [$dplayer->getName()]));
			$dplayer->sendMessage($this->translateString("denyplayer.attempteddeny", [$sender->getName()]));
			return true;
		}
		if ($this->getPlugin()->addPlotDenied($plot, (int)$dplayer->getXuid())) {
			$sender->sendMessage($this->translateString("denyplayer.success1", [$dplayer->getName()]));
			$dplayer->sendMessage($this->translateString("denyplayer.success2", [$plot->X, $plot->Z, $sender->getName()]));
			if($this->getPlugin()->getPlotBB($plot)->isVectorInside($dplayer->getPosition()))
				$this->getPlugin()->teleportPlayerToPlot($dplayer, $plot);
		}else{
			$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
		}
		return true;
	}

	public function getForm(?Player $player = null) : ?MyPlotForm {
		return null;
	}
}