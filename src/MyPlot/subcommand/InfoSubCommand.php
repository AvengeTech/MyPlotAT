<?php
declare(strict_types=1);
namespace MyPlot\subcommand;

use core\user\User;
use MyPlot\forms\MyPlotForm;
use MyPlot\forms\subforms\InfoForm;
use MyPlot\Plot;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use core\utils\TextFormat;
use prison\PrisonPlayer;

class InfoSubCommand extends SubCommand
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
		if(isset($args[0])) {
			if(isset($args[1]) and is_numeric($args[1])) {
				$key = ((int) $args[1] - 1) < 1 ? 1 : ((int) $args[1] - 1);
				/** @var Plot[] $plots */
				$plots = [];
				foreach($this->getPlugin()->getPlotLevels() as $levelName => $settings) {
					$plots = array_merge($plots, $this->getPlugin()->getPlotsOfPlayer((int)$args[0], $levelName));
				}
				if(isset($plots[$key])) {
					$sender->sendMessage(TextFormat::GN . "Loading plot info...");
					/** @var Plot $plot */
					$plot = $plots[$key];
					$plot->fetchHelpers(function (array $helpers) use ($sender, $plot): void {
						/** @var User[] $helpers */
						if (!$sender instanceof PrisonPlayer || !$sender->isConnected()) return;
						$hnames = [];
						foreach ($helpers as $u) $hnames[] = $u->getGamertag();
						$plot->fetchDenied(function (array $denied) use ($sender, $plot, $hnames): void {
							/** @var User[] $denied */
							if (!$sender instanceof PrisonPlayer || !$sender->isConnected()) return;
							$dnames = [];
							foreach ($denied as $u) $dnames[] = $u->getGamertag();
							$sender->sendMessage(
								TextFormat::GOLD . str_repeat("=", 16) . PHP_EOL .
									TextFormat::AQUA . ($plot->name !== "" ? $plot->name . " | " : "") . $plot . TextFormat::RESET . PHP_EOL .
									TextFormat::GREEN . "Owner: " . TextFormat::YELLOW . ($plot->getUser()?->getGamertag() ?? "Unknown") . PHP_EOL .
									TextFormat::GREEN . "Helpers: " . TextFormat::YELLOW . implode(TextFormat::GRAY . ", " . TextFormat::YELLOW, $hnames) . PHP_EOL .
									TextFormat::GREEN . "Denied: " . TextFormat::YELLOW . implode(TextFormat::GRAY . ", " . TextFormat::YELLOW, $dnames) . PHP_EOL .
									TextFormat::GOLD . str_repeat("=", 16)
							);
						});
					});
				}else{
					$sender->sendMessage(TextFormat::RED . $this->translateString("info.notfound"));
				}
			}else{
				return false;
			}
		}else{
			$plot = $this->getPlugin()->getPlotByPosition($sender->getPosition());
			if($plot === null) {
				$sender->sendMessage(TextFormat::RED . $this->translateString("notinplot"));
				return true;
			}
			$sender->sendMessage(TextFormat::GN . "Loading plot info...");
			$plot->fetchHelpers(function (array $helpers) use ($sender, $plot): void {
				/** @var User[] $helpers */
				if (!$sender instanceof PrisonPlayer || !$sender->isConnected()) return;
				$hnames = [];
				foreach ($helpers as $u) $hnames[] = $u->getGamertag();
				$plot->fetchDenied(function (array $denied) use ($sender, $plot, $hnames): void {
					/** @var User[] $denied */
					if (!$sender instanceof PrisonPlayer || !$sender->isConnected()) return;
					$dnames = [];
					foreach ($denied as $u) $dnames[] = $u->getGamertag();
					$sender->sendMessage(
						TextFormat::GOLD . str_repeat("=", 16) . PHP_EOL .
							TextFormat::AQUA . ($plot->name !== "" ? $plot->name . " | " : "") . $plot . TextFormat::RESET . PHP_EOL .
							TextFormat::GREEN . "Owner: " . TextFormat::YELLOW . ($plot->getUser()?->getGamertag() ?? "Unknown") . PHP_EOL .
							TextFormat::GREEN . "Helpers: " . TextFormat::YELLOW . implode(TextFormat::GRAY . ", " . TextFormat::YELLOW, $hnames) . PHP_EOL .
							TextFormat::GREEN . "Denied: " . TextFormat::YELLOW . implode(TextFormat::GRAY . ", " . TextFormat::YELLOW, $dnames) . PHP_EOL .
							TextFormat::GOLD . str_repeat("=", 16)
					);
				});
			});
		}
		return true;
	}

	public function getForm(?Player $player = null) : ?MyPlotForm {
		return null;
	}
}