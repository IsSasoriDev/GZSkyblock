<?php

namespace SkyblockCore;

use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\player\Player;
use pocketmine\item\Item;

class QuestManager implements Listener { // Implement Listener interface

    private Main $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        // Register events for THIS manager
        $plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
    }

    public function handleBlockBreak(BlockBreakEvent $event): void {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        
        foreach($this->plugin->getDataManager()->getPlayerStats($player)['active_quests'] as $questId => $progress) {
            $quest = $this->plugin->getConfig()->get("quests")[$questId];
            if($quest["objective"]["type"] === "block_break" && 
               $block->getTypeId() === Item::fromString($quest["objective"]["block"])->getBlock()->getTypeId()) {
                $this->updateQuestProgress($player, $questId, $progress + 1);
            }
        }
    }

    public function handleEntityKill(EntityDeathEvent $event): void {
        $player = $event->getEntity()->getLastDamageCause()->getEntity();
        if($player instanceof Player) {
            $entityType = $event->getEntity()::getNetworkTypeId();
            
            foreach($this->plugin->getDataManager()->getPlayerStats($player)['active_quests'] as $questId => $progress) {
                $quest = $this->plugin->getConfig()->get("quests")[$questId];
                if($quest["objective"]["type"] === "entity_kill" && 
                   in_array($entityType, explode("|", $quest["objective"]["entity"]))) {
                    $this->updateQuestProgress($player, $questId, $progress + 1);
                }
            }
        }
    }

    private function updateQuestProgress(Player $player, string $questId, int $newProgress): void {
        $required = $this->plugin->getConfig()->get("quests")[$questId]["objective"]["amount"];
        
        if($newProgress >= $required) {
            $this->completeQuest($player, $questId);
        } else {
            $this->plugin->getDataManager()->updateQuestProgress($player, $questId, $newProgress);
            $player->sendMessage("Quest Progress: $newProgress/$required");
        }
    }

    private function completeQuest(Player $player, string $questId): void {
        $quest = $this->plugin->getConfig()->get("quests")[$questId];
        $this->plugin->getLevelManager()->addXP($player, $quest["reward"]["xp"]);
        $this->plugin->getDataManager()->completeQuest($player, $questId);
        $player->sendMessage("Quest Completed! +{$quest["reward"]["xp"]} XP");
    }

    public function getAvailableQuests(Player $player): array {
        $completed = $this->plugin->getDataManager()->getCompletedQuests($player);
        return array_filter(
            array_keys($this->plugin->getConfig()->get("quests", [])),
            fn($quest) => !isset($completed[$quest]) || 
                          (time() - $completed[$quest]) > $this->plugin->getConfig()->get("quests.$quest.cooldown")
        );
    }
}
