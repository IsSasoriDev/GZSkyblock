<?php

namespace SkyblockCore;

use pocketmine\player\Player;

class LevelManager {

    private Main $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function addXP(Player $player, int $xp): void {
        $data = $this->plugin->getDataManager()->getPlayerStats($player);
        $newXP = $data['xp'] + $xp;
        $newLevel = $this->calculateLevel($newXP);
        
        $this->plugin->getDataManager()->savePlayerData($player, [
            'xp' => $newXP,
            'level' => $newLevel
        ]);
        
        if($newLevel > $data['level']) {
            $player->sendMessage("§aIsland Level Up! §eNew Level: $newLevel");
        }
    }

    private function calculateLevel(int $xp): int {
        return (int) floor(sqrt($xp / $this->plugin->getConfig()->get("leveling.xp_per_level")));
    }

    public function getIslandLevel(Player $player): int {
        return $this->plugin->getDataManager()->getPlayerStats($player)['level'];
    }
}
