<?php

namespace SkyblockCore;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\player\Player;

class Main extends PluginBase implements Listener {

    private IslandManager $island;
    private DataManager $data;
    private QuestManager $quests;
    private LevelManager $levels;

    public function onEnable(): void {
        $this->saveDefaultConfig();
        
        // Initialize core components
        $this->data = new DataManager($this);
        $this->island = new IslandManager($this);
        $this->quests = new QuestManager($this);
        $this->levels = new LevelManager($this);

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->island->generateWorld();
        
        $this->getLogger()->info("SkyblockCore enabled!");
    }

    public function onJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        $this->data->initializePlayer($player);
    }

    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool {
        switch (strtolower($cmd->getName())) {
            case "island":
                if (!$sender instanceof Player) {
                    $sender->sendMessage("This command must be used in-game!");
                    return false;
                }
                return $this->handleIslandCommand($sender, $args);

            // Remove balance command case
            case "quests":
                if ($sender instanceof Player) {
                    $this->showQuestInterface($sender);
                }
                return true;

            case "level":
                if ($sender instanceof Player) {
                    $level = $this->levels->getIslandLevel($sender);
                    $sender->sendMessage("Island Level: " . $level);
                }
                return true;

            default:
                return false;
        }
    }

    // Remove all economy-related methods
    // Keep other methods unchanged
}
