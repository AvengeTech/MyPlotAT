<?php

declare(strict_types=1);

namespace MyPlot;

use core\AtPlayer;
use core\Core;
use MyPlot\events\MyPlotBlockEvent;
use MyPlot\events\MyPlotBorderChangeEvent;
use MyPlot\events\MyPlotPlayerEnterPlotEvent;
use MyPlot\events\MyPlotPlayerLeavePlotEvent;
use MyPlot\events\MyPlotPvpEvent;
use pocketmine\block\Block;
use pocketmine\block\Liquid;
use pocketmine\block\Sapling;
use pocketmine\block\utils\TreeType;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockSpreadEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\world\WorldLoadEvent;
use pocketmine\event\world\WorldUnloadEvent;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use core\utils\TextFormat;
use pocketmine\world\World;
use pocketmine\block\tile\Container;
use pocketmine\block\utils\SaplingType;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\world\generator\object\TreeType as ObjectTreeType;
use prison\PrisonPlayer;

class EventListener implements Listener {
	/** @var MyPlot $plugin */
	private $plugin;

	private $pvpcsd = [];

	/**
	 * EventListener constructor.
	 *
	 * @param MyPlot $plugin
	 */
	public function __construct(MyPlot $plugin) {
		$this->plugin = $plugin;
	}

	/**
	 * @ignoreCancelled false
	 * @priority LOWEST
	 *
	 * @param WorldLoadEvent $event
	 */
	public function onLevelLoad(WorldLoadEvent $event): void {
		// NOOP
	}

	/**
	 * @ignoreCancelled false
	 * @priority MONITOR
	 *
	 * @param WorldUnloadEvent $event
	 */
	public function onLevelUnload(WorldUnloadEvent $event): void {
		if ($event->isCancelled()) {
			return;
		}
		$levelName = $event->getWorld()->getFolderName();
		if ($this->plugin->unloadLevelSettings($levelName)) {
			$this->plugin->getLogger()->debug("Level " . $event->getWorld()->getFolderName() . " unloaded!");
		}
	}

	/**
	 * @ignoreCancelled false
	 * @priority LOWEST
	 *
	 * @param BlockPlaceEvent $event
	 */
	public function onBlockPlace(BlockPlaceEvent $event): void {
		//$this->onEventOnBlock($event);
	}

	/**
	 * @ignoreCancelled false
	 * @priority LOWEST
	 *
	 * @param BlockBreakEvent $event
	 */
	public function onBlockBreak(BlockBreakEvent $event): void {
		//$this->onEventOnBlock($event);
	}

	/**
	 * @ignoreCancelled false
	 * @priority LOWEST
	 *
	 * @param PlayerInteractEvent $event
	 */
	public function onPlayerInteract(PlayerInteractEvent $event): void {
		$player = $event->getPlayer();
		if (!$player instanceof PrisonPlayer || !$player->inPlotWorld()) return;
		$pos = $event->getBlock()->getPosition();
		$plot = $this->plugin->getPlotByPosition($pos);
		$xuid = (int) $player->getXuid();
		if (!$player->isStaff() && (
			$plot === null || (
				$plot->owner != $xuid &&
				!$plot->isHelper($xuid)
			)
		)) {
			$event->cancel();
		}
		//$this->onEventOnBlock($event);
	}

	/**
	 * @ignoreCancelled false
	 * @priority LOWEST
	 *
	 * @param SignChangeEvent $event
	 */
	public function onSignChange(SignChangeEvent $event): void {
		//$this->onEventOnBlock($event);
	}

	/**
	 * @param BlockPlaceEvent|BlockBreakEvent|PlayerInteractEvent|SignChangeEvent $event
	 */
	public function onEventOnBlock($event): bool {
		/** @var PrisonPlayer $player */
		$player = $event->getPlayer();
		if ($event instanceof BlockPlaceEvent) {
			$bt = $event->getTransaction();
			foreach ($bt->getBlocks() as [$x, $y, $z, $b]) {
				/** @var Block $b */
				$pb = clone $b;
				$pb->position($player->getWorld(), $x, $y, $z);
				$block = $pb;
				break;
			}
		} elseif ($event instanceof SignChangeEvent) {
			$block = $event->getSign();
		} else {
			$block = $event->getBlock();
		}

		if (!$block->getPosition()->isValid())
			return false;

		if (
			$player->isTier3() ||
			(
				$event instanceof PlayerInteractEvent &&
				$player->isStaff() &&
				$block instanceof Container
			)
		) {
			return true;
		}

		$levelName = $block->getPosition()->getWorld()->getFolderName();
		if (!$this->plugin->isLevelLoaded($levelName)) {
			return false;
		}
		$plot = $this->plugin->getPlotByPosition($block->getPosition());
		if ($plot !== null) {
			$ev = new MyPlotBlockEvent($plot, $block, $player, $event);
			if ($event->isCancelled()) {
				$ev->cancel();
			} else {
				$ev->uncancel();
			}
			$ev->call();
			if ($ev->isCancelled()) {
				$event->cancel();
			} else {
				$event->uncancel();
			}
			$username = (int)$player->getXuid();
			if ($plot->owner == $username || $plot->isHelper($username) || $player->isTier3()) {
				if (!($event instanceof PlayerInteractEvent && $block instanceof Sapling))
					return false;
				/*
				 * Prevent growing a tree near the edge of a plot
				 * so the leaves won't go outside the plot
				 */

				/** @var Sapling $block */
				$rr = new \ReflectionClass($block);
				/** @var SaplingType $saplingType */
				$saplingType = ($rr->getProperty("saplingType"))->getValue($block);
				$maxLengthLeaves = ($saplingType->getTreeType() == ObjectTreeType::SPRUCE) ? 3 : 2;
				$beginPos = $this->plugin->getPlotPosition($plot);
				$endPos = clone $beginPos;
				$beginPos->x += $maxLengthLeaves;
				$beginPos->z += $maxLengthLeaves;
				$plotSize = $this->plugin->getLevelSettings($levelName)->plotSize;
				$endPos->x += $plotSize - $maxLengthLeaves;
				$endPos->z += $plotSize - $maxLengthLeaves;
				if ($block->getPosition()->x >= $beginPos->x && $block->getPosition()->z >= $beginPos->z && $block->getPosition()->x < $endPos->x && $block->getPosition()->z < $endPos->z) {
					return false;
				}
			}
		} elseif ($this->plugin->isPositionBorderingPlot($block->getPosition()) && $this->plugin->getLevelSettings($levelName)->editBorderBlocks) {
			$plot = $this->plugin->getPlotBorderingPosition($block->getPosition());
			if ($plot instanceof Plot) {
				$ev = new MyPlotBorderChangeEvent($plot, $block, $player, $event);
				if ($event->isCancelled()) {
					$ev->cancel();
				} else {
					$ev->uncancel();
				}
				$ev->call();
				if ($ev->isCancelled()) {
					$event->cancel();
				} else {
					$event->uncancel();
				}
				$username = (int)$player->getXuid();
				if ($plot->owner == $username || $plot->isHelper($username) || $player->isTier3())
					if (!($event instanceof PlayerInteractEvent && $block instanceof Sapling))
						return true;
			}
		}
		$event->cancel();
		$this->plugin->getLogger()->debug("Block placement/break/interaction of {$block->getName()} was cancelled at " . $block->getPosition()->__toString());
		return false;
	}

	/**
	 * @ignoreCancelled false
	 * @priority LOWEST
	 *
	 * @param EntityExplodeEvent $event
	 */
	public function onExplosion(EntityExplodeEvent $event): void {
		if ($event->isCancelled()) {
			return;
		}
		$levelName = $event->getEntity()->getWorld()->getFolderName();
		if (!$this->plugin->isLevelLoaded($levelName))
			return;
		$plot = $this->plugin->getPlotByPosition($event->getPosition());
		if ($plot === null) {
			$event->cancel();
			return;
		}
		$beginPos = $this->plugin->getPlotPosition($plot);
		$endPos = clone $beginPos;
		$levelSettings = $this->plugin->getLevelSettings($levelName);
		$plotSize = $levelSettings->plotSize;
		$endPos->x += $plotSize;
		$endPos->z += $plotSize;
		$blocks = array_filter($event->getBlockList(), function (Block $block) use ($beginPos, $endPos): bool {
			if ($block->getPosition()->x >= $beginPos->x && $block->getPosition()->z >= $beginPos->z && $block->getPosition()->x < $endPos->x && $block->getPosition()->z < $endPos->z) {
				return true;
			}
			return false;
		});
		$event->setBlockList($blocks);
	}

	/**
	 * @ignoreCancelled false
	 * @priority LOWEST
	 *
	 * @param EntityMotionEvent $event
	 */
	public function onEntityMotion(EntityMotionEvent $event): void {
		if ($event->isCancelled()) {
			return;
		}
		$level = $event->getEntity()->getWorld();
		if (!$level instanceof World)
			return;
		$levelName = $level->getFolderName();
		if (!$this->plugin->isLevelLoaded($levelName))
			return;
		$settings = $this->plugin->getLevelSettings($levelName);
		if ($settings->restrictEntityMovement && !($event->getEntity() instanceof Player)) {
			$event->cancel();
			$this->plugin->getLogger()->debug("Cancelled entity motion on " . $levelName);
		}
	}

	/**
	 * @ignoreCancelled false
	 * @priority LOWEST
	 *
	 * @param BlockSpreadEvent $event
	 */
	public function onBlockSpread(BlockSpreadEvent $event): void {
		if ($event->isCancelled()) {
			return;
		}
		$levelName = $event->getBlock()->getPosition()->getWorld()->getFolderName();
		if (!$this->plugin->isLevelLoaded($levelName))
			return;
		$settings = $this->plugin->getLevelSettings($levelName);

		$newBlockInPlot = $this->plugin->getPlotByPosition($event->getBlock()->getPosition()) instanceof Plot;
		$sourceBlockInPlot = $this->plugin->getPlotByPosition($event->getSource()->getPosition()) instanceof Plot;

		if ($newBlockInPlot && $sourceBlockInPlot) {
			$spreadIsSamePlot = $this->plugin->getPlotByPosition($event->getBlock()->getPosition())->isSame($this->plugin->getPlotByPosition($event->getSource()->getPosition()));
		} else {
			$spreadIsSamePlot = false;
		}

		if ($event->getSource() instanceof Liquid) {
			if (!$settings->updatePlotLiquids && ($sourceBlockInPlot || $this->plugin->isPositionBorderingPlot($event->getSource()->getPosition()))) {
				$event->cancel();
				$this->plugin->getLogger()->debug("Cancelled {$event->getSource()->getName()} spread on [$levelName]");
			} elseif ($settings->updatePlotLiquids && ($sourceBlockInPlot || $this->plugin->isPositionBorderingPlot($event->getSource()->getPosition())) && (!$newBlockInPlot || !$this->plugin->isPositionBorderingPlot($event->getBlock()->getPosition()) || !$spreadIsSamePlot)) {
				$event->cancel();
				$this->plugin->getLogger()->debug("Cancelled {$event->getSource()->getName()} spread on [$levelName]");
			}
		} elseif (!$settings->allowOutsidePlotSpread && (!$newBlockInPlot || !$spreadIsSamePlot)) {
			$event->cancel();
			//$this->plugin->getLogger()->debug("Cancelled block spread of {$event->getSource()->getName()} on ".$levelName);
		}
	}

	/**
	 * @ignoreCancelled false
	 * @priority LOWEST
	 *
	 * @param PlayerMoveEvent $event
	 */
	public function onPlayerMove(PlayerMoveEvent $event): void {
		$this->onEventOnMove($event->getPlayer(), $event);
	}

	/**
	 * @ignoreCancelled false
	 * @priority LOWEST
	 *
	 * @param EntityTeleportEvent $event
	 */
	public function onPlayerTeleport(EntityTeleportEvent $event): void {
		$entity = $event->getEntity();
		if ($entity instanceof Player) {
			$this->onEventOnMove($entity, $event);
		}
	}

	/**
	 * @param PlayerMoveEvent|EntityTeleportEvent $event
	 */
	private function onEventOnMove(AtPlayer $player, $event): void {
		$levelName = $player->getWorld()->getFolderName();
		if (!$this->plugin->isLevelLoaded($levelName))
			return;
		$plot = $this->plugin->getPlotByPosition($event->getTo());
		$plotFrom = $this->plugin->getPlotByPosition($event->getFrom());
		if ($plot !== null && ($plotFrom === null || !$plot->isSame($plotFrom))) {
			if (strpos((string) $plot, "-0") !== false) {
				return;
			}
			$ev = new MyPlotPlayerEnterPlotEvent($plot, $player);
			if ($event->isCancelled()) {
				$ev->cancel();
			} else {
				$ev->uncancel();
			}
			$username = (int)$ev->getPlayer()->getXuid();
			if ($plot->owner !== $username && ($plot->isDenied($username)) && !$ev->getPlayer()->isStaff()) {
				$ev->cancel();
			}
			$ev->call();
			if ($ev->isCancelled()) {
				$event->cancel();
			} else {
				$event->uncancel();
			}
			if ($event->isCancelled()) {
				return;
			}
			if (!(bool) $this->plugin->getConfig()->get("ShowPlotPopup", true))
				return;
			$popup = TextFormat::GRAY . $this->plugin->getLanguage()->translateString("popup", [TextFormat::AQUA . $plot]);
			switch ($levelName) {
				case "nether_plots_s0":
					$price = 250000;
					break;
				case "end_plots_s0":
					$price = 1000000;
					break;
				default:
					$price = 5000;
					break;
			}
			if ($plot->hasOwner()) {
				$owner = TextFormat::GREEN . ($plot->getUser()?->getGamertag() ?? "Unknown");
				if ($plot->price > 0 && $plot->owner !== $player->getXuid()) {
					$ownerPopup = $this->plugin->getLanguage()->translateString("popup.forsale", [$owner . TextFormat::WHITE, TextFormat::AQUA . $price . " techits" . TextFormat::WHITE]);
				} else {
					$ownerPopup = $this->plugin->getLanguage()->translateString("popup.owner", [$owner . TextFormat::WHITE]);
				}
			} else {
				$ownerPopup = $this->plugin->getLanguage()->translateString("popup.available", [TextFormat::YELLOW . "/p claim" . TextFormat::GRAY, TextFormat::AQUA . number_format($price) . " techits" . TextFormat::GRAY]);
			}
			$ownerPopup = TextFormat::GRAY . $ownerPopup;
			$paddingSize = (int) floor((strlen($popup) - strlen($ownerPopup)) / 2);
			$paddingPopup = str_repeat(" ", max(0, -$paddingSize));
			$paddingOwnerPopup = str_repeat(" ", max(0, $paddingSize));
			$popup = TextFormat::WHITE . $paddingPopup . $popup . "\n" . TextFormat::WHITE . $paddingOwnerPopup . $ownerPopup;
			$ev->getPlayer()->sendTip($popup);
		} elseif ($plotFrom !== null && ($plot === null || !$plot->isSame($plotFrom))) {
			if (strpos((string) $plotFrom, "-0") !== false) {
				return;
			}
			$ev = new MyPlotPlayerLeavePlotEvent($plotFrom, $player);
			if ($event->isCancelled()) {
				$ev->cancel();
			} else {
				$ev->uncancel();
			}
			$ev->call();
			if ($ev->isCancelled()) {
				$event->cancel();
			} else {
				$event->uncancel();
			}
		} elseif ($plotFrom !== null && $plot !== null && ($plot->isDenied((int)$player->getXuid())) && $plot->owner !== $player->getXuid() && !$player->isStaff()) {
			$this->plugin->teleportPlayerToPlot($player, $plot);
		}
	}
}
