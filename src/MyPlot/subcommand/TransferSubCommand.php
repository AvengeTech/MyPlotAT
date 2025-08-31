<?php

namespace MyPlot\subcommand;

use core\AtPlayer;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use core\utils\TextFormat;

use MyPlot\forms\MyPlotForm;

use core\Core;
use core\user\User;

class TransferSubCommand extends SubCommand {
	/**
	 * @param CommandSender $sender
	 * @return bool
	 */
	public function canUse(CommandSender $sender): bool {
		return !$sender instanceof AtPlayer || $sender->isTier3();
	}

	/**
	 * @param AtPlayer $sender
	 * @param string[] $args
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args): bool {
		if (empty($args)) {
			return false;
		}
		$first = array_shift($args);
		if (empty($args)) {
			$sender->sendMessage(TextFormat::RN . "Must provide second player!");
			return true;
		}
		$second = array_shift($args);
		Core::getInstance()->getUserPool()->useUser($first, function (User $firstUser) use ($second, $sender): void {
			if (!$sender instanceof AtPlayer || !$sender->isConnected()) return;
			if (!$firstUser->valid()) {
				$sender->sendMessage(TextFormat::RN . "First player never seen!");
				return;
			}
			$plots = $this->getPlugin()->getPlotsOfPlayer((string)$firstUser->getXuid());
			if (empty($plots)) {
				$sender->sendMessage(TextFormat::RN . "This player has no plots!");
				return;
			}
			Core::getInstance()->getUserPool()->useUser($second, function (User $secondUser) use ($firstUser, $sender, $plots) {
				if (!$sender instanceof AtPlayer || !$sender->isConnected()) return;
				$failed = 0;
				$success = 0;
				foreach ($plots as $plot) {
					$plot->owner = $secondUser->getXuid();
					if (!$this->getPlugin()->savePlot($plot)) {
						$failed++;
					} else {
						$success++;
					}
				}
				if ($failed > 0) {
					$sender->sendMessage(TextFormat::RN . "Failed to transfer " . $failed . " plots!");
				}
				$sender->sendMessage(TextFormat::GN . "Successfully transferred " . $success . " plots from " . TextFormat::YELLOW . $firstUser->getGamertag() . TextFormat::GREEN . " to " . TextFormat::YELLOW . $secondUser->getGamertag() . TextFormat::GREEN . "!");
			});
		});
		return true;
	}

	public function getForm(?Player $player = null): ?MyPlotForm {
		return null;
	}
}
