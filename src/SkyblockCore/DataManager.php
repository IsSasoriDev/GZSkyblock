<?php

namespace SkyblockCore;

use pocketmine\player\Player;
use pocketmine\utils\Config;

class DataManager {

    private Main $plugin;
    private Config $playerData;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        $this->playerData = new Config($this->plugin->getDataFolder() . "players.yml", Config::YAML);
    }

    public function initializePlayer(Player $player): void {
        $name = $player->getName();
        if(!$this->playerData->exists($name)) {
            $this->playerData->set($name, [
                'island' => null,
                'xp' => 0,
                'level' => 1,
                'completed_quests' => [],
                'active_quests' => []
            ]);
            $this->playerData->save();
        }
    }

    public function getIslandData(Player $player): array {
        return $this->playerData->get($player->getName())['island'] ?? [];
    }

    public function saveIslandData(Player $player, array $data): void {
        $current = $this->playerData->get($player->getName());
        $current['island'] = $data;
        $this->playerData->set($player->getName(), $current);
        $this->playerData->save();
    }

    public function getPlayerStats(Player $player): array {
        return $this->playerData->get($player->getName());
    }

    public function saveAll(): void {
        $this->playerData->save();
    }
}
