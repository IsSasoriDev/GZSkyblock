<?php

namespace SkyblockCore;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\player\Player;

class Main extends PluginBase implements Listener {

    private EconomyManager $economy;
    private IslandManager $island;
    private DataManager $data;
    private QuestManager $quests;
    private LevelManager $levels;

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        // Initialize core components
        $this->data = new DataManager($this);
        $this->economy = new EconomyManager($this);
        $this->island = new IslandManager($this);
        $this->quests = new QuestManager($this);
        $this->levels = new LevelManager($this);

        // Generate world if needed
        $this->island->generateWorld();
    }

    public function onJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        $this->data->initializePlayer($player);

        if(!$this->data->getIslandData($player)) {
            $this->economy->setBalance($player, $this->getConfig()->get("start_balance"));
        }
    }

    public function onBlockBreak(BlockBreakEvent $event): void {
        $this->quests->handleBlockBreak($event);
    }

    public function onEntityDeath(EntityDeathEvent $event): void {
        $this->quests->handleEntityKill($event);
    }

    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool {
        if(!$sender instanceof Player) {
            $sender->sendMessage("This command must be used in-game!");
            return false;
        }

        switch(strtolower($cmd->getName())) {
            case "island":
                return $this->handleIslandCommand($sender, $args);
            case "balance":
                $balance = $this->economy->getBalance($sender);
                $sender->sendMessage("Balance: " . $this->getConfig()->get("currency_symbol") . $balance);
                return true;
            case "quests":
                $this->showQuestInterface($sender);
                return true;
            case "level":
                $level = $this->levels->getIslandLevel($sender);
                $sender->sendMessage("Island Level: " . $level);
                return true;
        }
        return false;
    }

    private function handleIslandCommand(Player $player, array $args): bool {
        if(empty($args)) {
            $player->sendMessage("Usage: /island <create|teleport|info>");
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
                $info = $this->island->getIslandInfo($player);
                $player->sendMessage($info);
                return true;
            default:
                $player->sendMessage("Invalid subcommand!");
                return false;
        }
    }

    private function showQuestInterface(Player $player): void {
        $stats = $this->data->getPlayerStats($player);
        $message = "§6§lQuests§r\n";
        $message .= "§eLevel: §f" . $stats['level'] . "\n";
        $message .= "§eXP: §f" . $stats['xp'] . "\n\n";

        // Active quests
        $message .= "§aActive Quests:\n";
        foreach($stats['active_quests'] as $questId => $progress) {
            $quest = $this->getConfig()->get("quests")[$questId];
            $message .= "§7- §f" . $quest['description'] . " (§e$progress/" . $quest['objective']['amount'] . "§f)\n";
        }

        // Available quests
        $message .= "\n§bAvailable Quests:\n";
        foreach($this->quests->getAvailableQuests($player) as $questId) {
            $quest = $this->getConfig()->get("quests")[$questId];
            $message .= "§7- §f" . $quest['description'] . " (§a/" . $questId . "§f)\n";
        }

        $player->sendMessage($message);
    }

    public function onDisable(): void {
        $this->data->saveAll();
    }

    // Getters
    public function getEconomyManager(): EconomyManager { return $this->economy; }
    public function getIslandManager(): IslandManager { return $this->island; }
    public function getDataManager(): DataManager { return $this->data; }
    public function getQuestManager(): QuestManager { return $this->quests; }
    public function getLevelManager(): LevelManager { return $this->levels; }
}