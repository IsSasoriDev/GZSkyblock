<?php

namespace SkyblockCore;

use pocketmine\player\Player;
use pocketmine\world\World;
use pocketmine\world\generator\GeneratorManager;
use pocketmine\math\Vector3;

class IslandManager {

    private Main $plugin;
    private array $islandPositions = [];

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function generateWorld(): void {
        $worldName = $this->plugin->getConfig()->get("island_world");
        if(!$this->plugin->getServer()->getWorldManager()->isWorldGenerated($worldName)) {
            $this->plugin->getServer()->getWorldManager()->generateWorld(
                $worldName,
                GeneratorManager::getInstance()->getGenerator("void")
            );
        }
    }

    public function createIsland(Player $player): void {
        $world = $this->getSkyblockWorld();
        $position = $this->getNextPosition();

        // Generate island structure
        $world->setBlock($position, BlockFactory::get(Block::GRASS));
        // Add more island generation logic

        $this->plugin->getDataManager()->saveIslandData($player, [
            "x" => $position->x,
            "y" => $position->y,
            "z" => $position->z,
            "created" => time()
        ]);

        $player->teleport($position);
        $player->sendMessage("Your island has been created!");
    }

    private function getNextPosition(): Vector3 {
        $distance = $this->plugin->getConfig()->get("island_distance");
        $count = count($this->islandPositions);
        return new Vector3($count * $distance, 64, $count * $distance);
    }

    public function teleportToIsland(Player $player): void {
        $data = $this->plugin->getDataManager()->getIslandData($player);
        $pos = new Vector3($data["x"], $data["y"], $data["z"]);
        $player->teleport($pos);
    }

    public function getIslandInfo(Player $player): string {
        $data = $this->plugin->getDataManager()->getIslandData($player);
        return "Island created: " . date("Y-m-d H:i", $data["created"]);
    }
}