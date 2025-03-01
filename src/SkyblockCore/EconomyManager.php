<?php

namespace SkyblockCore;

use pocketmine\player\Player;
use cooldogedev\BedrockEconomy\api\BedrockEconomyAPI;

class EconomyManager {

    private BedrockEconomyAPI $economy;

    public function __construct() {
        $this->economy = BedrockEconomyAPI::getInstance();
    }

    public function getBalance(Player $player): int {
        return $this->economy->getPlayerBalance($player->getName());
    }

    public function setBalance(Player $player, int $amount): void {
        $this->economy->setPlayerBalance($player->getName(), $amount);
    }

    public function addBalance(Player $player, int $amount): void {
        $this->economy->addToPlayerBalance($player->getName(), $amount);
    }

    public function reduceBalance(Player $player, int $amount): bool {
        $current = $this->getBalance($player);
        if ($current >= $amount) {
            $this->economy->subtractFromPlayerBalance($player->getName(), $amount);
            return true;
        }
        return false;
    }

    public function format(int $amount): string {
        return $this->economy->getCurrency()->format($amount);
    }
}
