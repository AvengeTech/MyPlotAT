<?php

declare(strict_types=1);

namespace MyPlot;

use core\Core;
use core\user\User;
use pocketmine\math\Facing;

class Plot {
	/** @var string $levelName */
	public $levelName = "";
	/** @var int $X */
	public $X = -0;
	/** @var int $Z */
	public $Z = -0;
	/** @var string $name */
	public string $name = "";
	/** @var int $owner */
	public int $owner = 0;
	/** @var int[] $helpers */
	public $helpers = [];
	/** @var int[] $denied */
	public $denied = [];
	/** @var string $biome */
	public $biome = "PLAINS";
	/** @var bool $pvp */
	public $pvp = true;
	/** @var float $price */
	public $price = 0.0;
	/** @var int $id */
	public $id = -1;

	public ?User $user = null;

	/**
	 * Plot constructor.
	 *
	 * @param string $levelName
	 * @param int $X
	 * @param int $Z
	 * @param string $name
	 * @param string $owner
	 * @param string[] $helpers
	 * @param string[] $denied
	 * @param string $biome
	 * @param bool|null $pvp
	 * @param float $price
	 * @param int $id
	 */
	public function __construct(string $levelName, int $X, int $Z, string $name = "", int $owner = 0, array $helpers = [], array $denied = [], string $biome = "PLAINS", ?bool $pvp = null, float $price = -1, int $id = -1) {
		$this->levelName = $levelName;
		$this->X = $X;
		$this->Z = $Z;
		$this->name = $name;
		$this->owner = $owner;
		$this->helpers = $helpers;
		foreach ($this->helpers as $i => $h) {
			$this->helpers[$i] = intval($h);
		}
		$this->denied = $denied;
		foreach ($this->denied as $i => $d) {
			$this->denied[$i] = intval($d);
		}
		$this->biome = strtoupper($biome);
		$settings = MyPlot::getInstance()->getLevelSettings($levelName);
		if (!isset($pvp)) {
			$this->pvp = !$settings->restrictPVP;
		} else {
			$this->pvp = $pvp;
		}
		if (MyPlot::getInstance()->getConfig()->get('UseEconomy', false) === true)
			$this->price = $price < 0 ? $settings->claimPrice : $price;
		else
			$this->price = 0;
		$this->id = $id;
		Core::getInstance()->getUserPool()->useUser($owner, function (User $user) {
			if ($user->valid()) $this->user = $user;
		});
	}

	public function updateUser(): void {
		Core::getInstance()->getUserPool()->useUser($this->owner, function (User $user) {
			if ($user->valid()) $this->user = $user;
		});
	}

	public function getUser(): ?User {
		return $this->user;
	}

	public function hasOwner(): bool {
		return $this->owner > 0;
	}

	/**
	 * @api
	 */
	public function isHelper(int $xuid): bool {
		return in_array($xuid, $this->helpers, true);
	}

	/**
	 * @api
	 *
	 * @param int $xuid
	 *
	 * @return bool
	 */
	public function addHelper(int $xuid): bool {
		if (!$this->isHelper($xuid)) {
			$this->unDenyPlayer($xuid);
			$this->helpers[] = $xuid;
			return true;
		}
		return false;
	}

	/**
	 * @api
	 *
	 * @param int $xuid
	 *
	 * @return bool
	 */
	public function removeHelper(int $xuid): bool {
		if (!$this->isHelper($xuid)) {
			return false;
		}
		$key = array_search($xuid, $this->helpers, true);
		if ($key === false) {
			return false;
		}
		unset($this->helpers[$key]);
		return true;
	}

	public function fetchHelpers(\Closure $callback): void{
		Core::getInstance()->getUserPool()->useUsers($this->helpers, function (array $users) use ($callback): void {
			$callback($users);
		});
	}

	/**
	 * @api
	 *
	 * @param int $xuid
	 *
	 * @return bool
	 */
	public function isDenied(int $xuid): bool {
		return in_array($xuid, $this->denied, true);
	}

	/**
	 * @api
	 *
	 * @param int $xuid
	 *
	 * @return bool
	 */
	public function denyPlayer(int $xuid): bool {
		if (!$this->isDenied($xuid)) {
			$this->removeHelper($xuid);
			$this->denied[] = $xuid;
			return true;
		}
		return false;
	}

	/**
	 * @api
	 *
	 * @param int $xuid
	 *
	 * @return bool
	 */
	public function unDenyPlayer(int $xuid): bool {
		if (!$this->isDenied($xuid)) {
			return false;
		}
		$key = array_search($xuid, $this->denied, true);
		if ($key === false) {
			return false;
		}
		unset($this->denied[$key]);
		return true;
	}

	public function fetchDenied(\Closure $callback): void {
		Core::getInstance()->getUserPool()->useUsers($this->denied, function (array $users) use ($callback): void {
			$callback($users);
		});
	}

	/**
	 * @api
	 *
	 * @param Plot $plot
	 * @param bool $checkMerge
	 *
	 * @return bool
	 */
	public function isSame(Plot $plot, bool $checkMerge = true): bool {
		if ($checkMerge)
			$plot = MyPlot::getInstance()->getProvider()->getMergeOrigin($plot);
		return $this->X === $plot->X and $this->Z === $plot->Z and $this->levelName === $plot->levelName;
	}

	/**
	 * @api
	 *
	 * @return bool
	 */
	public function isMerged(): bool {
		return count(MyPlot::getInstance()->getProvider()->getMergedPlots($this, true)) > 1; // only calculate the adjacent to save resources
	}

	/**
	 * @api
	 *
	 * @param int $side
	 * @param int $step
	 *
	 * @return Plot
	 */
	public function getSide(int $side, int $step = 1): Plot {
		$levelSettings = MyPlot::getInstance()->getLevelSettings($this->levelName);
		$pos = MyPlot::getInstance()->getPlotPosition($this, false);
		$sidePos = $pos->getSide($side, $step * ($levelSettings->plotSize + $levelSettings->roadWidth));
		$sidePlot = MyPlot::getInstance()->getPlotByPosition($sidePos);
		if ($sidePlot === null) {
			switch ($side) {
				case Facing::NORTH:
					$sidePlot = new self($this->levelName, $this->X, $this->Z - $step);
					break;
				case Facing::SOUTH:
					$sidePlot = new self($this->levelName, $this->X, $this->Z + $step);
					break;
				case Facing::WEST:
					$sidePlot = new self($this->levelName, $this->X - $step, $this->Z);
					break;
				case Facing::EAST:
					$sidePlot = new self($this->levelName, $this->X + $step, $this->Z);
					break;
				default:
					return clone $this;
			}
		}
		return $sidePlot;
	}

	public function __toString(): string {
		return "(" . $this->X . ";" . $this->Z . ")";
	}
}
