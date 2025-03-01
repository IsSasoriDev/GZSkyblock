<?php

namespace SkyblockCore;

use pocketmine\player\Player;

class LevelManager {

    private Main $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function addXP(Player $player, int $xp): void {
        $data = $this->plugin->getDataManager()->getIslandData($player);
        $newXP = $data["xp"] + $xp;
        $newLevel = $this->calculateLevel($newXP);

        $this->plugin->getDataManager()->saveIslandData($player, [
            "xp" => $newXP,
            "level" => $newLevel
        ]);

        if($newLevel > $data["level"]) {
            $player->sendMessage("Island Level Up! New Level: $newLevel");
        }
    }

    private function calculateLevel(int $xp): int {
        $config = $this->plugin->getConfig()->get("leveling");
        return (int) floor(sqrt($xp / $config["xp_per_level"]));
    }

    public function getLevelReward(int $level): float {
        $config = $this->plugin->getConfig()->get("leveling");
        return $config["base_reward"] * pow(1.1, $level);
    }

    public function getIslandLevel(Player $player): int {
        return $this->plugin->getDataManager()->getIslandData($player)["level"] ?? 0;
    }
}