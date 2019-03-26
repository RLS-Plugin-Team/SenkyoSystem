<?php

namespace senkyo;

use pocketmine\utils\Config;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

class senkyo extends PluginBase implements Listener{
  
  public function onEnable(){
    if(!file_exists($this->getDataFolder())){
      mkdir($this->getDataFolder(), 0744,true);
    }
    
    $this->botan = new Config($this->getDataFolder() ."botan.yml", Config::YAML,
    array(
      "senkyo" => "off"
    ));
    $this->rikkouho = new Config($this->getDataFolder() ."rikkouho.yml",Config::YAML,array());
    $this->yuuken = new Config($this->getDataFolder() ."yuuken.yml",Config::YAML,array());
    $this->botan->save();
    $this->rikkouho->save();
    $this->yuuken->save();
    $this->getServer()->getPluginManager()->registerEvents($this,$this);
  }
  
  public function onJoin(PlayerJoinEvent $ev){
    $player = $ev->getPlayer();
    if($this->botan->get("senkyo") == "on"){
      $player->sendMessage("§e【選挙】 >>> §b現在投票期間中です。 §e/senkyos §bで立候補者を確認できます。");
    }
  }
  
  public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
    switch($command->getName()){
      case "senkyo":
        if(isset($args[0])){
          switch($args[0]){
            case "on":
              $this->botan->set("senkyo","on");
              $this->botan->save();
              $this->getServer()->broadcastMessage("§b[運営]: §a選挙が開始されました。");
              return true;
              break;
            
            case "off":
              $this->botan->set("senkyo","off");
              $this->botan->save();
              $this->getServer()->broadcastMessage("§b[運営]: §a選挙が終了しました。");
              return true;
              break;
            
            default:
              $sender->sendMessage("§e【選挙】 >>> onかoffかを選択してください。");
              return true;
              break;
          }
        }else{
          $sender->sendMessage("§e【選挙】 >>> onかoffか選択してください。");
        }
        return true;
        break;
      
      case "senkyori":
        if($this->botan->get("senkyo") == "on"){
          $name = $sender->getName();
          if($this->rikkouho->exists($name)){
            $sender->sendMessage("§e【選挙】 >>> §fあなたはすでに立候補しています。");
          }else{
            $this->rikkouho->set($name,0);
            $this->rikkouho->save();
            $sender->sendMessage("§e【選挙】 >>> §a立候補しました。");
          }
        }else{
          $sender->sendMessage("§e【選挙】 >>> §f現在選挙は行っておりません。");
        }
        return true;
        break;
        
      case "senkyode":
        if($this->botan->get("senkyo") == "on"){
          $name = $sender->getName();
          if($this->rikkouho->exists($name)){
            $this->rikkouho->remove($name);
            $this->rikkouho->save();
            $sender->sendMessage("§e【選挙】 >>> §a立候補を取り下げました。");
          }else{
            $sender->sendMessage("§e【選挙】 >>> §fあなたは立候補していません。");
          }
        }else{
          $sender->sendMessage("§e【選挙】 >>> §f現在選挙は行っておりません。");
        }
        return true;
        break;
      
      case "senkyot":
        if($this->botan->get("senkyo") == "on"){
          if($this->yuuken->get($sender->getName()) == 0){
            if(isset($args[0]) && isset($args[1])){
              $name1 = $args[0];
	      $name2 = $args[1];
              if($name1 == $sender->getName()){
                $sender->sendMessage("§e【選挙】 >>> §c自分に投票することはできません。");
	      }elseif($name2 == $sender->getName()){
		$sender->sendMessage("§e【選挙】 >>> §c自分に投票することはできません。");
	      }elseif($name1 == $name2){
		$sender->sendMessage("§e【選挙】 >>> §c同じ人には投票できません。");
              }else{
                if(!$this->rikkouho->exists($name1)){
		  $sender->sendMessage("§e【選挙】 >>> §c".$name1."は立候補していません。");
		}elseif(!$this->rikkouho->exists($name2)){
		  $sender->sendMessage("§e【選挙】 >>> §c".$name2."は立候補していません。");
		}else{
	          $kazu1 = $this->rikkouho->get($name1);
                  $kaz1 = $kazu1 + 1;
		  $kazu2 = $this->rikkouho->get($name2);
		  $kaz2 = $kazu2 + 1;
                  $this->rikkouho->set($name1,$kaz1);
		  $this->rikkouho->set($name2,$kaz2);
                  $this->rikkouho->save();
                  $sender->sendMessage("§e【選挙】 >>> ".$name1."§a さんと".$name2."さんに投票しました。");
                  $this->yuuken->set($sender->getName(),1);
                  $this->yuuken->save();
                }
              }
            }else{
              $sender->sendMessage("§e【選挙】 >>> §f立候補者から2人を投票してください。");
            }
          }else{
            $sender->sendMessage("§e【選挙】 >>> §fあなたはすでに投票しています。");
          }
        }else{
          $sender->sendMessage("§e【選挙】 >>> §f現在選挙は行っておりません。");
        }
        return true; 
        break;
      
      case "senkyodelall":
        if($sender->isOp()){
          foreach($this->rikkouho->getAll(true) as $r){
            $this->rikkouho->remove($r);
            $this->rikkouho->save();
          }
          foreach($this->yuuken->getAll(true) as $y){
            $this->yuuken->remove($y);
            $this->yuuken->save();
          }
          $this->getLogger()->notice("選挙のデータを削除しました");
          $sender->sendMessage(TextFormat::BLUE."選挙のデータを削除しました");
        }else{
          $sender->sendMessage("§cコマンドを実行する権限がありません");
        }
        return true;
        break;
      
      case "senkyos":
        if($this->rikkouho->getAll() !=null){
          if($this->botan->get("senkyo") == "on"){
            if($sender->isOp()){
              $data = $this->rikkouho->getAll(true);
              foreach($data as $player){
                $sender->sendMessage("§e【選挙】 >>> ".$player." ");
              }
            }else{
              $data = $this->rikkouho->getAll(true);
              foreach($data as $player){
                $sender->sendMessage("[§a立候補者§f] ".$player." ");
              }
              $sender->sendMessage("§e【選挙】 >>> /senkyotで投票しましょう。");
            }
          }else{
            $data = $this->rikkouho->getAll(true);
            foreach($data as $player){
              $sender->sendMessage("[§b結果§f]: §e".$player."§b: §a".$this->rikkouho->get($player)."票 ");
            }
          }
        }else{
          $sender->sendMessage("§e【選挙】 >>> まだ立候補者はいません。");
        }          
        return true;
        break;
    }
  }                                      
}
