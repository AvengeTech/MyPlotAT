<?php

declare(strict_types=1);

namespace MyPlot\subcommand;

use core\AtPlayer;
use core\Core;
use core\user\User;
use core\utils\TextFormat;
use MyPlot\forms\MyPlotForm;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;

class ListSubCommand extends SubCommand {
	public function canUse(CommandSender $sender): bool {
		return ($sender instanceof Player);
	}

	/**
	 * @param AtPlayer $sender
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, array $args): bool {
		$gnf = fn (string $name) => match (true) {
			stristr($name, "end") !== false => "End Plots",
			stristr($name, "nether") !== false => "Nether Plots",
			default => "Basic Plots"
		};
		if ($sender->isStaff()) {
			if (count($args) > 0) {
				Core::getInstance()->getUserPool()->useUser($args[0], function (User $user) use ($sender, $gnf): void {
					if (!$sender instanceof AtPlayer || !$sender->isConnected()) return;
					if (!$user->valid()) {
						$sender->sendMessage(TextFormat::RN . "Player never seen!");
						return;
					}
					if (count($this->getPlugin()->getPlotsOfPlayer((int)$user->getXuid())) < 1) {
						$sender->sendMessage(TextFormat::RN . TextFormat::YELLOW . $user->getGamertag() . TextFormat::RED . " does not own any plots!");
						return;
					}
					$message = TextFormat::DARK_GREEN . "Plots owned by " . TextFormat::YELLOW . $user->getGamertag() . TextFormat::DARK_GREEN . ":";
					foreach ($this->getPlugin()->getPlotLevels() as $levelName => $settings) {
						$plots = $this->getPlugin()->getPlotsOfPlayer((int)$user->getXuid(), $levelName);
						if (count($plots) < 1) continue;
						$message .= PHP_EOL . "  " . TextFormat::GREEN . $gnf($levelName);
						foreach ($plots as $i => $plot) {
							$name = $plot->name;
							if ($name != "") $name .= " at ";
							$x = $plot->X;
							$z = $plot->Z;
							$message .= PHP_EOL . "   " . TextFormat::DARK_GREEN . ($i + 1) . ") " . TextFormat::WHITE . $name . $plot;
						}
					}
					$sender->sendMessage($message);
				});
			} else {
				if (count($this->getPlugin()->getPlotsOfPlayer((int)$sender->getXuid())) < 1) {
					$sender->sendMessage(TextFormat::RN . "You do not own any plots!");
					return true;
				}
				$message = TextFormat::DARK_GREEN . "Plots you own:";
				foreach ($this->getPlugin()->getPlotLevels() as $levelName => $settings) {
					$plots = $this->getPlugin()->getPlotsOfPlayer((int)$sender->getXuid(), $levelName);
					if (count($plots) < 1) continue;
					$message .= PHP_EOL . "  " . TextFormat::GREEN . $gnf($levelName);
					foreach ($plots as $i => $plot) {
						$name = $plot->name;
						if ($name != "") $name .= " at ";
						$x = $plot->X;
						$z = $plot->Z;
						$message .= PHP_EOL . "   " . TextFormat::DARK_GREEN . ($i + 1) . ") " . TextFormat::WHITE . $name . $plot;
					}
				}
				$sender->sendMessage($message);
			}
		} else {
			if (count($this->getPlugin()->getPlotsOfPlayer((int)$sender->getXuid())) < 1) {
				$sender->sendMessage(TextFormat::RN . "You do not own any plots!");
				return true;
			}
			$message = TextFormat::DARK_GREEN . "Plots you own:";
			foreach ($this->getPlugin()->getPlotLevels() as $levelName => $settings) {
				$plots = $this->getPlugin()->getPlotsOfPlayer((int)$sender->getXuid(), $levelName);
				if (count($plots) < 1) continue;
				$message .= PHP_EOL . "  " . TextFormat::GREEN . $gnf($levelName);
				foreach ($plots as $i => $plot) {
					$name = $plot->name;
					if ($name != "") $name .= " at ";
					$x = $plot->X;
					$z = $plot->Z;
					$message .= PHP_EOL . "   " . TextFormat::DARK_GREEN . ($i + 1) . ") " . TextFormat::WHITE . $name . $plot;
				}
			}
			$sender->sendMessage($message);
		}
		return true;
	}

	public function getForm(?Player $player = null): ?MyPlotForm {
		return null; // this will probably be merged into the homes command
	}
}
