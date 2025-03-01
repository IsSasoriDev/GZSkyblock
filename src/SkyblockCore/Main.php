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
use cooldogedev\BedrockEconomy\api\BedrockEconomyAPI;

class Main extends PluginBase implements Listener {

    private EconomyManager $economy;
    private IslandManager $island;
    private DataManager $data;
    private QuestManager $quests;
    private LevelManager $levels;

    public function onEnable(): void {
        $this->saveDefaultConfig();

        // Verify BedrockEconomy exists first
        if (!class_exists(BedrockEconomyAPI::class)) {
            $this->getLogger()->error("BedrockEconomy not found! Disabling plugin...");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }

        // Initialize core components
        $this->data = new DataManager($this);
        $this->economy = new EconomyManager();
        $this->island = new IslandManager($this);
        $this->quests = new QuestManager($this);
        $this->levels = new LevelManager($this);

        // Register events and generate world
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->island->generateWorld();
        
        $this->getLogger()->info("SkyblockCore enabled!");
    }

    public function onJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        $this->data->initializePlayer($player);
        
        if (!$this->data->getIslandData($player)) {
            $this->economy->setBalance($player, $this->getConfig()->get("start_balance", 1000));
        }
    }

    public function onBlockBreak(BlockBreakEvent $event): void {
        $this->quests->handleBlockBreak($event);
    }

    public function onEntityDeath(EntityDeathEvent $event): void {
        $this->quests->handleEntityKill($event);
    }

    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool {
        switch (strtolower($cmd->getName())) {
            case "island":
                if (!$sender instanceof Player) {
                    $sender->sendMessage("This command must be used in-game!");
                    return false;
                }
                return $this->handleIslandCommand($sender, $args);

            case "balance":
                if ($sender instanceof Player) {
                    $balance = $this->economy->getBalance($sender);
                    $formatted = $this->economy->format($balance);
                    $sender->sendMessage("Balance: " . $formatted);
                }
                return true;

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

    private function handleIslandCommand(Player $player, array $args): bool {
        if (empty($args)) {
            $player->sendMessage("Usage: /island <create|teleport|info>");
            return false;
        }

        switch (strtolower($args[0])) {
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
                $player->sendMessage("Invalid subcommand!");
                return false;
        }
    }

    private function showQuestInterface(Player $player): void {
        $stats = $this->data->getPlayerStats($player);
        $message = [
            "§6§lSkyblock Quests§r",
            "§eLevel: §f" . $stats['level'],
            "§eXP: §f" . $stats['xp'],
            "",
            "§aActive Quests:"
        ];

        foreach ($stats['active_quests'] as $questId => $progress) {
            $quest = $this->getConfig()->get("quests")[$questId];
            $message[] = "§7- §f" . $quest['description'] . " (§e$progress/" . $quest['objective']['amount'] . "§f)";
        }

        $message[] = "\n§bAvailable Quests:";
        foreach ($this->quests->getAvailableQuests($player) as $questId) {
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
    public function getEconomy(): EconomyManager {
        return $this->economy;
    }

    public function getIslandManager(): IslandManager {
        return $this->island;
    }

    public function getDataManager(): DataManager {
        return $this->data;
    }
}
