<?php

namespace SkyblockCore;

use pocketmine\player\Player;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\item\Item;

class QuestManager {

    private Main $plugin;
    private array $activeQuests = [];

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        $this->plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
    }

    public function handleBlockBreak(BlockBreakEvent $event): void {
        $player = $event->getPlayer();
        $block = $event->getBlock();

        foreach($this->getPlayerQuests($player) as $questName => $progress) {
            $quest = $this->plugin->getConfig()->get("quests")[$questName];
            if($quest["objective"]["type"] === "block_break" &&
                $block->getTypeId() === Item::fromString($quest["objective"]["block"])->getBlock()->getTypeId()) {
                $this->updateQuestProgress($player, $questName, $progress + 1);
            }
        }
    }

    public function handleEntityKill(EntityDeathEvent $event): void {
        $player = $event->getEntity()->getLastDamageCause()->getEntity();
        if($player instanceof Player) {
            $entityType = $event->getEntity()::getNetworkTypeId();

            foreach($this->getPlayerQuests($player) as $questName => $progress) {
                $quest = $this->plugin->getConfig()->get("quests")[$questName];
                if($quest["objective"]["type"] === "entity_kill" &&
                    in_array($entityType, explode("|", $quest["objective"]["entity"]))) {
                    $this->updateQuestProgress($player, $questName, $progress + 1);
                }
            }
        }
    }

    private function updateQuestProgress(Player $player, string $questName, int $newProgress): void {
        $required = $this->plugin->getConfig()->get("quests")[$questName]["objective"]["amount"];

        if($newProgress >= $required) {
            $this->completeQuest($player, $questName);
        } else {
            $this->activeQuests[$player->getName()][$questName] = $newProgress;
            $player->sendMessage("Quest Progress: $newProgress/$required");
        }
    }

    private function completeQuest(Player $player, string $questName): void {
        $quest = $this->plugin->getConfig()->get("quests")[$questName];

        // Give rewards
        $this->plugin->getLevelManager()->addXP($player, $quest["reward"]["xp"]);
        $this->plugin->getEconomy()->addBalance($player, $quest["reward"]["money"]);

        // Update data
        $this->plugin->getDataManager()->completeQuest($player, $questName);
        unset($this->activeQuests[$player->getName()][$questName]);

        $player->sendMessage("Quest Completed! +{$quest["reward"]["xp"]} XP +{$quest["reward"]["money"]} coins");
    }

    public function getAvailableQuests(Player $player): array {
        $completed = $this->plugin->getDataManager()->getCompletedQuests($player);
        $allQuests = array_keys($this->plugin->getConfig()->get("quests", []));

        return array_filter($allQuests, function($quest) use ($completed) {
            return !isset($completed[$quest]) ||
                (time() - $completed[$quest]) > $this->plugin->getConfig()->get("quests")[$quest]["cooldown"];
        });
    }

    public function getPlayerQuests(Player $player): array {
        return $this->activeQuests[$player->getName()] ?? [];
    }
}