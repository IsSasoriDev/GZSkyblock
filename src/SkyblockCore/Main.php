<?php

namespace SkyblockCore;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\player\Player;

class Main extends PluginBase implements Listener {

    private EconomyManager $economy;
    // ... other properties

    public function onEnable(): void {
        if (!class_exists(BedrockEconomy::class)) {
            $this->getLogger()->error("BedrockEconomy not found! Disable plugin.");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }

        $this->economy = new EconomyManager($this);
        // ... rest of initialization
    }

    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool {
        switch(strtolower($cmd->getName())) {
            case "balance":
                if ($sender instanceof Player) {
                    $balance = $this->economy->getBalance($sender);
                    $formatted = $this->economy->format($balance);
                    $sender->sendMessage("Balance: " . $formatted);
                }
                return true;
            // ... other commands
        }
        return false;
    }
}
