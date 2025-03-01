<?php

namespace SkyblockCore;

use pocketmine\player\Player;
use pocketmine\utils\Config;

class DataManager {

    private Main $plugin;
    private Config $playerData;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        $this->playerData = new Config($plugin->getDataFolder() . "players.yml", Config::YAML);
    }

    public function initializePlayer(Player $player): void {
        if(!$this->playerData->exists($player->getName())) {
            $this->playerData->set($player->getName(), [
                'island' => null,
                'xp' => 0,
                'level' => 1,
                // Remove balance field
                'completed_quests' => [],
                'active_quests' => []
            ]);
            $this->playerData->save();
        }
    }
}
    public function getIslandData(Player $player): array {
        return $this->playerData->get($player->getName())['island'] ?? [];
    }

    public function saveIslandData(Player $player, array $data): void {
        $name = $player->getName();
        $current = $this->playerData->get($name, []);
        $current['island'] = array_merge($current['island'] ?? [], $data);
        $this->playerData->set($name, $current);
        $this->playerData->save();
    }

    public function completeQuest(Player $player, string $questId): void {
        $name = $player->getName();
        $data = $this->playerData->get($name);
        $data['completed_quests'][$questId] = time();
        unset($data['active_quests'][$questId]);
        $this->playerData->set($name, $data);
        $this->playerData->save();
    }

    public function updateQuestProgress(Player $player, string $questId, int $progress): void {
        $name = $player->getName();
        $data = $this->playerData->get($name);
        $data['active_quests'][$questId] = $progress;
        $this->playerData->set($name, $data);
        $this->playerData->save();
    }

    public function getPlayerStats(Player $player): array {
        return $this->playerData->get($player->getName(), [
            'xp' => 0,
            'level' => 1,
            'balance' => 0.0,
            'completed_quests' => [],
            'active_quests' => []
        ]);
    }

    public function saveAll(): void {
        $this->playerData->save();
    }
}
