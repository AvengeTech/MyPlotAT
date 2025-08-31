<?php

declare(strict_types=1);

namespace MyPlot\subcommand;

use core\AtPlayer;
use core\Core;
use core\inbox\Inbox;
use core\inbox\object\MessageInstance;
use core\session\CoreSession;
use core\user\User;
use MyPlot\forms\MyPlotForm;
use MyPlot\forms\subforms\GiveForm;
use MyPlot\Plot;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

use core\utils\TextFormat;
use prison\Prison;
use prison\PrisonPlayer;
use prison\PrisonSession;

class GiveSubCommand extends SubCommand {
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
		if (count($args) === 0) {
			return false;
		}

		$newOwner = $this->getPlugin()->getServer()->getPlayerByPrefix($args[0]);

		$plot = $this->getPlugin()->getPlotByPosition($sender->getPosition());
		if ($plot === null) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
			return true;
		}
		if ($plot->owner !== (int)$sender->getXuid()) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("notowner"));
			return true;
		}

		if (!$newOwner instanceof PrisonPlayer) {
			Core::getInstance()->getUserPool()->useUser($args[0], function (User $user) use ($sender, $plot, $args): void {
				if (!$sender instanceof AtPlayer || !$sender->isConnected()) return;
				if (!$user->valid()) {
					$sender->sendMessage(TextFormat::RN . "Player never seen!");
					return;
				}
				if (Core::getInstance()->getUserPool()->onlineElsewhere($user)) {
					$sender->sendMessage(TextFormat::RI . TextFormat::YELLOW . $user->getGamertag() . TextFormat::GRAY . " is online elsewhere. They must be connected to the plots subserver to give them a plot!");
					return;
				}
				Prison::getInstance()->getSessionManager()->useSession($user, function (PrisonSession $session) use ($user, $sender, $plot, $args): void {
					if (!$sender instanceof PrisonPlayer || !$sender->isConnected()) return;
					$world = $sender->getWorld();
					switch ($world->getDisplayName()) {
						default:
							break;
						case "nether_plots":
							if ($session->getRankUp()->getPrestige() < 1) {
								$sender->sendMessage(TextFormat::RI . "This player must prestige at least once to claim nether plots!");
								return;
							}
							break;
						case "end_plots":
							if ($session->getRankUp()->getPrestige() < 5) {
								$sender->sendMessage(TextFormat::RI . "This player must prestige at least " . TextFormat::YELLOW . "5 " . TextFormat::GRAY . "times to claim end plots!");
								return;
							}
							break;
					}

					$maxPlots = $this->getPlugin()->getMaxPlotsOfPlayer($user, $sender->getWorld());
					$plotsOfPlayer = count($this->getPlugin()->getPlotsOfPlayer($user->getXuid(), $sender->getWorld()->getDisplayName()));
					if ($plotsOfPlayer >= $maxPlots) {
						$sender->sendMessage(TextFormat::RED . $this->translateString("give.maxedout", [$maxPlots]));
						return;
					}

					if (count($args) == 2 and $args[1] == $this->translateString("confirm")) {
						if ($this->getPlugin()->claimPlot($plot, $user->getXuid())) {
							$plotId = TextFormat::GREEN . $plot . TextFormat::WHITE;
							$newOwnerName = TextFormat::GREEN . $user->getGamertag() . TextFormat::WHITE;
							$sender->sendMessage($this->translateString("give.success", [$newOwnerName]));
							Core::getInstance()->getSessionManager()->useSession($user, function (CoreSession $session) use ($sender, $plot): void {
								if (!$sender instanceof AtPlayer) return;
								$inbox = $session->getInbox()->getInbox(Inbox::TYPE_HERE);
								$msg = new MessageInstance(
									$inbox,
									MessageInstance::newId(),
									time(),
									$sender->getUser() ?? (int)$sender->getXuid(),
									"Plot Transfer",
									TextFormat::WHITE . "You were given ownership of " . TextFormat::GREEN . $plot . TextFormat::WHITE . " by " . TextFormat::YELLOW . $sender->getName() . TextFormat::WHITE . "!"
								);
								$inbox->addMessage($msg, true);
							});
						} else {
							$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
						}
					} else {
						$plotId = TextFormat::GREEN . $plot . TextFormat::WHITE;
						$newOwnerName = TextFormat::GREEN . $user->getGamertag() . TextFormat::WHITE;
						$sender->sendMessage($this->translateString("give.confirm", [$plotId, $newOwnerName]));
					}
				});
			}, true);
			return true;
		} elseif ($newOwner->getXuid() === $sender->getXuid()) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("give.toself"));
			return true;
		}

		$world = $sender->getWorld();
		switch ($world->getDisplayName()) {
			default:
				break;
			case "nether_plots":
				if ($newOwner->getPrestige() < 1) {
					$sender->sendMessage(TextFormat::RI . "This player must prestige at least once to claim nether plots!");
					return true;
				}
				break;
			case "end_plots":
				if ($newOwner->getPrestige() < 5) {
					$sender->sendMessage(TextFormat::RI . "This player must prestige at least " . TextFormat::YELLOW . "5 " . TextFormat::GRAY . "times to claim end plots!");
					return true;
				}
				break;
		}

		$maxPlots = $this->getPlugin()->getMaxPlotsOfPlayer($newOwner, $sender->getWorld());
		$plotsOfPlayer = count($this->getPlugin()->getPlotsOfPlayer((int)$newOwner->getXuid(), $sender->getWorld()->getDisplayName()));
		if ($plotsOfPlayer >= $maxPlots) {
			$sender->sendMessage(TextFormat::RED . $this->translateString("give.maxedout", [$maxPlots]));
			return true;
		}

		if (count($args) == 2 and $args[1] == $this->translateString("confirm")) {
			if ($this->getPlugin()->claimPlot($plot, (int)$newOwner->getXuid())) {
				$plotId = TextFormat::GREEN . $plot . TextFormat::WHITE;
				$oldOwnerName = TextFormat::GREEN . $sender->getName() . TextFormat::WHITE;
				$newOwnerName = TextFormat::GREEN . $newOwner->getName() . TextFormat::WHITE;
				$sender->sendMessage($this->translateString("give.success", [$newOwnerName]));
				$newOwner->sendMessage($this->translateString("give.received", [$oldOwnerName, $plotId]));
			} else {
				$sender->sendMessage(TextFormat::RED . $this->translateString("error"));
			}
		} else {
			$plotId = TextFormat::GREEN . $plot . TextFormat::WHITE;
			$newOwnerName = TextFormat::GREEN . $newOwner->getName() . TextFormat::WHITE;
			$sender->sendMessage($this->translateString("give.confirm", [$plotId, $newOwnerName]));
		}
		return true;
	}

	public function getForm(?Player $player = null): ?MyPlotForm {
		return null;
	}
}
