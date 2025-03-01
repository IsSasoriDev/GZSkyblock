<?php

namespace SkyblockCore;

use pocketmine\player\Player;
use onebone\economyapi\EconomyAPI;

class EconomyManager {

    private Main $plugin;
    private EconomyAPI $economyAPI;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        $this->economyAPI = EconomyAPI::getInstance();
    }

    public function getBalance(Player $player): float {
        return $this->economyAPI->myMoney($player);
    }

    public function setBalance(Player $player, float $amount): void {
        $this->economyAPI->setMoney($player, $amount);
    }

    public function addBalance(Player $player, float $amount): void {
        $this->economyAPI->addMoney($player, $amount);
    }

    public function reduceBalance(Player $player, float $amount): bool {
        if($this->getBalance($player) >= $amount) {
            $this->economyAPI->reduceMoney($player, $amount);
            return true;
        }
        return false;
    }
}