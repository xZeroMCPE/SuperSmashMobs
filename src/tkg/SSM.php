<?php
namespace tkg;

use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\AddPlayerPacket;
use pocketmine\network\protocol\MoveEntityPacket;
use pocketmine\network\protocol\MovePlayerPacket;
use pocketmine\network\protocol\RemoveEntityPacket;
use pocketmine\network\protocol\RemovePlayerPacket;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;

class SSM extends PluginBase implements Listener, CommandExecutor{
    /** @var  SSMSession[] */
    public $e;
    /** @var  MobStore */
    private $mobStore;
    public function onEnable(){
        $this->e = [];
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->mobStore = new MobStore($this);
    }
    public function onCommand(CommandSender $sender, Command $cmd, $label, array $args){
        if(isset($args[1])){
            if($sender->hasPermission("ssm.other")){
                if(($p = $this->getServer()->getPlayer($args[1])) instanceof Player){
                    if($this->isMobDisguised($p->getID())){
                        $this->destroyMobDisguise($p->getID());
                        $sender->sendMessage("Mob Disguise closed for " . $p->getName());
                        $p->sendMessage("Your mob disguise has been closed.");
                        return true;
                    }
                    else{
                        if(is_numeric($args[0])) {
                            $s = new SMMSession($p, $args[0]);
                            $this->e[$p->getID()] = $s;
                            $sender->sendMessage("Mob Disguise activated for " . $p->getName());
                            $p->sendMessage("You now have a mob disguise.");
                        }
                        elseif(($mob = $this->getMobStore()->getMobId($args[0])) !== false){
                            $s = new SSMSession($p, $mob);
                            $this->e[$p->getID()] = $s;
                            $sender->sendMessage("Mob Disguise activated for " . $p->getName());
                            $p->sendMessage("You now have a mob disguise.");
                        }
                        else{
                            $sender->sendMessage("No mob found with that name.");
                        }
                        return true;
                    }
                }
                else{
                    $sender->sendMessage("Player not found.");
                    return true;
                }
            }
            else{
                $sender->sendMessage("You do not have permission to disguise others.");
                return true;
            }
        }
        else{
            if($sender instanceof Player){
                if($this->isMobDisguised($sender->getID())){
                    $this->destroyMobDisguise($sender->getID());
                    $sender->sendMessage("Mob Disguise closed.");
                    return true;
                }
                else{
                    if(isset($args[0])){
                        if(is_numeric($args[0])) {
                            $s = new SSMSession($sender, $args[0]);
                            $this->e[$sender->getID()] = $s;
                            $sender->sendMessage("Mob Disguise activated.");
                        }
                        elseif(($mob = $this->getMobStore()->getMobId($args[0])) !== false){
                            $s = new SSMSession($sender, $mob);
                            $this->e[$sender->getID()] = $s;
                            $sender->sendMessage("Mob Disguise activated.");
                        }
                        else{
                            $sender->sendMessage("No mob found with that name.");
                        }
                        return true;
                    }
                    else{
                        $sender->sendMessage("You need to specify a mob.");
                        return true;
                    }
                }
            }
            else{
                $sender->sendMessage("You need to specify a player.");
                return true;
            }
        }
    }
    public function onPacketSend(DataPacketSendEvent $event){
        if(isset($event->getPacket()->eid)){
            if($this->isMobDisguised($event->getPacket()->eid) && !$event->getPlayer()->hasPermission("ssm.exempt")){
              if($event->getPacket() instanceof MovePlayerPacket){
                      $pk = new MoveEntityPacket;
                      $pk->entities = [[$event->getPacket()->eid, $event->getPacket()->x, $event->getPacket()->y, $event->getPacket()->z, $event->getPacket()->yaw, $event->getPacket()->pitch]];
                      $event->getPlayer()->dataPacket($pk);
                      $event->setCancelled();
              }
              elseif($event->getPacket() instanceof AddPlayerPacket){
                      $pk = new AddEntityPacket;
                      $pk->eid = $event->getPacket()->eid;
                      $pk->type = $this->e[$event->getPacket()->eid]->getType();
                      $pk->x = $event->getPacket()->x;
                      $pk->y = $event->getPacket()->y;
                      $pk->z = $event->getPacket()->z;
                      $pk->pitch = $event->getPacket()->pitch;
                      $pk->yaw = $event->getPacket()->yaw;
                      $pk->metadata = [];
                      $event->getPlayer()->dataPacket($pk);
                      $event->setCancelled();
              }
              elseif($event->getPacket() instanceof RemovePlayerPacket){
                      $pk = new RemoveEntityPacket;
                      $pk->eid = $event->getPacket()->eid;
                      $event->getPlayer()->dataPacket($pk);
                      $event->setCancelled();
              }
           }
        }
    }
    public function isMobDisguised($eid){
        return (isset($this->e[$eid]));
    }
    public function onQuit(PlayerQuitEvent $event){
        if($this->isMobDisguised($event->getPlayer()->getID())){
            $this->destroyMobDisguise($event->getPlayer()->getID());
        }
    }
    public function onDisable(){
        $this->getLogger()->info("Closing ssm sessions.");
        foreach($this->e as $eid => $s){
            $this->destroyMobDisguise($eid);
        }
    }
    public function destroyMobDisguise($i){
        if(isset($this->e[$i])){
            $this->e[$i]->despawnMobDisguise();
            $this->e[$i]->revertNameTag();
            $p = $this->e[$i]->getPlayer();
            unset($this->e[$i]);
            $p->spawnToAll();
        }
    }

    /**
     * @return MobStore
     */
    public function getMobStore(){
        return $this->mobStore;
    }
    public function getResourcePath(){
        return $this->getFile() . "/resources/";
    }
}
