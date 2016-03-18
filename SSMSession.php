<?php

namespace tkg;


use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\RemovePlayerPacket;
use pocketmine\network\protocol\SetEntityMotionPacket;
use pocketmine\Player;
use pocketmine\Server;

class 	SSMSession {
    private $p, $type;
    public function __construct(Player $player, $type){
        $this->p = $player;
        $this->type = $type;
        $this->startDisguise();
    }
    public function startMobDisguise(){
        $pk = new RemovePlayerPacket;
        $pk->eid = $this->p->getID();
        $pk->clientID = 0;

        $pk2 = new AddEntityPacket;
        $pk2->eid = $this->p->getID();
        $pk2->type = $this->type;
        $pk2->x = $this->p->getX();
        $pk2->y = $this->p->getY();
        $pk2->z = $this->p->getZ();
        $pk2->pitch = $this->p->pitch;
        $pk2->yaw = $this->p->yaw;
        $pk2->metadata = [];

        $pk3 = new SetEntityMotionPacket;
        $pk3->entities = [
            [$this->p->getID(), $this->p->motionX, $this->p->motionY, $this->p->motionZ]
        ];

        foreach(Server::getInstance()->getOnlinePlayers() as $p){
            if($p->canSee($this->p) && !$p->hasPermission("ssm.exempt") && $p->getName() !== $this->p->getName()){
                $p->dataPacket($pk);
                $p->dataPacket($pk2);
                $p->dataPacket($pk3);
            }
        }
    //    $this->p->setNameTag($this->p->getNameTag() . "\n [Disguised]");
    }
    public function despawnMobDisguise(){
        $this->p->despawnFromAll();
    }
    /*public function revertNameTag(){
        $this->p->setNameTag(str_replace("\n [Disguised]", "", $this->p->getNameTag()));
    }
    */
    public function getType(){
        return $this->type;
    }
    public function getPlayer(){
        return $this->p;
    }
} 
