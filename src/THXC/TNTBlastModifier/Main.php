<?php

declare(strict_types=1);

namespace THXC\TNTBlastModifier;

use pocketmine\event\entity\ExplosionPrimeEvent;
use pocketmine\event\Listener;
use pocketmine\math\AxisAlignedBB;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Config;

class Main extends PluginBase implements Listener {
	/** @var Config */
	protected $config;
	/** @var float */
	protected $factor;

	public function onEnable(): void {
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML, [
			"blastMultiplier" => 1.0
		]);
		$this->factor = $this->config->get("blastMultiplier", 1.0);
	}

	/**
	 * @param ExplosionPrimeEvent $ev
	 *
	 * @priority        HIGHEST
	 * @ignoreCancelled true
	 */
	public function onExplode(ExplosionPrimeEvent $ev): void {
		$level = $ev->getEntity()->getLevel();
		$this->getScheduler()->scheduleTask(new ClosureTask(function (int $_) use ($ev, $level): void {
			$src = $ev->getEntity();
			$explosionSize = $ev->getForce() * 2;
			$minX = (int)floor($src->x - $explosionSize - 1);
			$maxX = (int)ceil($src->x + $explosionSize + 1);
			$minY = (int)floor($src->y - $explosionSize - 1);
			$maxY = (int)ceil($src->y + $explosionSize + 1);
			$minZ = (int)floor($src->z - $explosionSize - 1);
			$maxZ = (int)ceil($src->z + $explosionSize + 1);

			$explosionBB = new AxisAlignedBB($minX, $minY, $minZ, $maxX, $maxY, $maxZ);

			$list = $level->getNearbyEntities($explosionBB, $src);
			foreach($list as $entity) {
				$distance = $entity->distance($src) / $explosionSize;

				if($distance <= 1) {
					$motion = $entity->subtract($src)->normalize();
					$impact = (1 - $distance) * ($exposure = 1);

					$entity->setMotion($motion->multiply($impact)->multiply($this->factor));
				}
			}
		}));
	}
}
