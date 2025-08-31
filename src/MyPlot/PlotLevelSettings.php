<?php

declare(strict_types=1);

namespace MyPlot;

use core\utils\ItemRegistry;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\VanillaBlocks;

class PlotLevelSettings {
	/** @var string $name */
	public $name;
	/** @var Block $roadBlock */
	public $roadBlock;
	/** @var Block $bottomBlock */
	public $bottomBlock;
	/** @var Block $plotFillBlock */
	public $plotFillBlock;
	/** @var Block $plotFloorBlock */
	public $plotFloorBlock;
	/** @var Block $wallBlock */
	public $wallBlock;
	/** @var int $roadWidth */
	public $roadWidth = 7;
	/** @var int $plotSize */
	public $plotSize = 64;
	/** @var int $groundHeight */
	public $groundHeight = 64;
	/** @var int $claimPrice */
	public $claimPrice = 0;
	/** @var int $clearPrice */
	public $clearPrice = 0;
	/** @var int $disposePrice */
	public $disposePrice = 0;
	/** @var int $resetPrice */
	public $resetPrice = 0;
	/** @var int $clonePrice */
	public $clonePrice = 0;
	/** @var bool $restrictEntityMovement */
	public $restrictEntityMovement = true;
	/** @var bool $restrictPVP */
	public $restrictPVP = false;
	/** @var bool $updatePlotLiquids */
	public $updatePlotLiquids = false;
	/** @var bool $allowOutsidePlotSpread */
	public $allowOutsidePlotSpread = false;
	/** @var bool $displayDoneNametags */
	public $displayDoneNametags = false;
	/** @var bool $editBorderBlocks */
	public $editBorderBlocks = false;

	/**
	 * PlotLevelSettings constructor.
	 *
	 * @param string $name
	 * @param mixed[] $settings
	 */
	public function __construct(string $name, array $settings = []) {
		$this->name = $name;
		$type = match (true) {
			stristr($name, "basic") !== false => 0,
			stristr($name, "nether") !== false => 1,
			stristr($name, "end") !== false => 2,
			default => 0
		};
		$this->roadBlock = match ($type) {
			0 => VanillaBlocks::OAK_PLANKS(),
			1 => VanillaBlocks::NETHER_BRICKS(),
			2 => VanillaBlocks::END_STONE_BRICKS()
		};
		$this->wallBlock = match ($type) {
			0 => VanillaBlocks::SMOOTH_STONE_SLAB(),
			1 => VanillaBlocks::RED_NETHER_BRICK_SLAB(),
			2 => VanillaBlocks::PURPUR_SLAB()
		};
		$this->plotFloorBlock = match ($type) {
			0 => VanillaBlocks::GRASS(),
			1 => VanillaBlocks::NETHERRACK(),
			2 => VanillaBlocks::END_STONE()
		};
		$this->plotFillBlock = match ($type) {
			0 => VanillaBlocks::DIRT(),
			1 => VanillaBlocks::NETHERRACK(),
			2 => VanillaBlocks::END_STONE()
		};
		$this->bottomBlock = VanillaBlocks::BEDROCK();
		$this->roadWidth = match ($type) {
			0 => 7,
			1 => 9,
			2 => 11
		};
		$this->plotSize = match ($type) {
			0 => 64,
			1 => 75,
			2 => 128
		};
		$this->groundHeight = 50;
		$this->claimPrice = match($type) {
			0 => 5000,
			1 => 250000,
			2 => 1000000
		};
		$this->clearPrice = 0;
		$this->disposePrice = 0;
		$this->resetPrice = 0;
		$this->clonePrice = 0;
		$this->restrictEntityMovement = true;
		$this->restrictPVP = true;
		$this->updatePlotLiquids = false;
		$this->allowOutsidePlotSpread = false;
		$this->editBorderBlocks = false;
	}

	/**
	 * @param string[] $array
	 * @param string|int $key
	 * @param Block $default
	 *
	 * @return Block
	 */
	public static function parseBlock(array &$array, $key, Block $default): Block {
		if (isset($array[$key])) {
			$id = $array[$key];
			$block = ItemRegistry::findItem($id, true)?->getBlock() ?? $default;
		} else {
			$block = $default;
		}
		return $block;
	}

	/**
	 * @param string[] $array
	 * @param string|int $key
	 * @param int $default
	 *
	 * @return int
	 */
	public static function parseNumber(array &$array, $key, int $default): int {
		if (isset($array[$key]) and is_numeric($array[$key])) {
			return (int) $array[$key];
		} else {
			return $default;
		}
	}

	/**
	 * @param mixed[] $array
	 * @param string|int $key
	 * @param bool $default
	 *
	 * @return bool
	 */
	public static function parseBool(array &$array, $key, bool $default): bool {
		if (isset($array[$key]) and is_bool($array[$key])) {
			return $array[$key];
		} else {
			return $default;
		}
	}
}
