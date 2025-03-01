<?php

namespace SkyblockCore;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\player\Player;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDeathEvent;

class Main extends PluginBase implements Listener {

    private IslandManager $island;
    private DataManager $data;
    private QuestManager $quests;
    private LevelManager $levels;

    public function onEnable(): void {
        $this->saveDefaultConfig();
        
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

    public function onBlockBreak(BlockBreakEvent $event): void {
        $this->quests->handleBlockBreak($event);
    }

    public function onEntityDeath(EntityDeathEvent $event): void {
        $this->quests->handleEntityKill($event);
    }

    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool {
        $commandName = strtolower($cmd->getName());
        
        if (!$sender instanceof Player) {
            $sender->sendMessage("§cThis command must be used in-game!");
            return false;
        }

        switch($commandName) {
            case "island":
                if (!$sender->hasPermission("skyblock.command.island")) {
                    $sender->sendMessage("§cYou don't have permission to use this command!");
                    return false;
                }
                return $this->handleIslandCommand($sender, $args);

            case "quests":
                if (!$sender->hasPermission("skyblock.command.quests")) {
                    $sender->sendMessage("§cYou don't have permission to view quests!");
                    return false;
                }
                $this->showQuestInterface($sender);
                return true;

            case "level":
                if (!$sender->hasPermission("skyblock.command.level")) {
                    $sender->sendMessage("§cYou don't have permission to check levels!");
                    return false;
                }
                $level = $this->levels->getIslandLevel($sender);
                $sender->sendMessage("§aIsland Level: §e" . $level);
                return true;

            default:
                return false;
        }
    }

    private function handleIslandCommand(Player $player, array $args): bool {
        if (empty($args)) {
            $player->sendMessage("§cUsage: /island <create|teleport|info>");
            return false;
        }

        switch(strtolower($args[0])) {
            case "create":
                $this->island->createIsland($player);
                return true;

            case "teleport":
                $this->island->teleportToIsland($player);
                return true;

            case "info":
                $player->sendMessage($this->island->getIslandInfo($player));
                return true;

            default:
                $player->sendMessage("§cInvalid subcommand! Usage: /island <create|teleport|info>");
                return false;
        }
    }

    private function showQuestInterface(Player $player): void {
        $stats = $this->data->getPlayerStats($player);
        $message = [
            "§6§lSkyblock Quests",
            "§eLevel: §f" . $stats['level'],
            "§eXP: §f" . $stats['xp'],
            "",
            "§aActive Quests:"
        ];

        foreach($stats['active_quests'] as $questId => $progress) {
            $quest = $this->getConfig()->get("quests")[$questId];
            $message[] = "§7- §f" . $quest['description'] . " (§e$progress/" . $quest['objective']['amount'] . "§f)";
        }

        $message[] = "\n§bAvailable Quests:";
        foreach($this->quests->getAvailableQuests($player) as $questId) {
            $quest = $this->getConfig()->get("quests")[$questId];
            $message[] = "§7- §f" . $quest['description'];
        }

        $player->sendMessage(implode("\n", $message));
    }

    public function onDisable(): void {
        $this->data->saveAll();
        $this->getLogger()->info("SkyblockCore disabled!");
    }

    // Getters
    public function getIslandManager(): IslandManager {
        return $this->island;
    }

    public function getDataManager(): DataManager {
        return $this->data;
    }

    public function getQuestManager(): QuestManager {
        return $this->quests;
    }

    public function getLevelManager(): LevelManager {
        return $this->levels;
    }
}
