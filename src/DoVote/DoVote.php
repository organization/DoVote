<?php

namespace DoVote;

use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class DoVote extends PluginBase {
	private $configData, $permission;
	private $vote = false;
	public function onLoad() {
		$this->getLogger ()->info ( TextFormat::GRAY . "> " . TextFormat::GREEN . "Plugin being prepared..." );
		$this->getLogger ()->info ( TextFormat::GRAY . "> " . TextFormat::GREEN . "Plugin prepared." );
	}
	public function onEnable() {
		@mkdir ( $this->getDataFolder () );
		$this->configData = (new Config ( $this->getDataFolder () . "vote.yml", Config::YAML, [ ] ))->getAll();
		$this->getLogger ()->info ( TextFormat::GRAY . "> " . TextFormat::GREEN . "Plugin enabled." );
	}
	public function onDisable() {
		$this->saveYml ();
	}
	public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
		if (! isset ( $args [0] )) {
			$this->getHelper ( $sender, "[투표]" );
			return;
		}
		switch (strtolower ( $args [0] )) {
			case "추가" :
				
				if (! $sender->isOp ())
					return;
				if (! isset ( $args [1] )) {
					$sender->sendMessage ( "[투표] -/투표 추가 <이름>" );
					return;
				}
				$this->configData [strtolower ( $args [1] )] = 0;
				$sender->sendMessage ( TextFormat::GREEN . "[투표] 투표 리스트에 첨부 : {$args[1]}" );
				
				break;
			case "삭제" :
				
				if (! $sender->isOp ())
					return;
				if (! isset ( $args [1] )) {
					$sender->sendMessage ( "[투표] -/투표 제거 <이름>" );
					return;
				}
				if (! array_key_exists ( strtolower ( $args [1] ), $this->configData )) {
					$sender->sendMessage ( "[투표] 투표 리스트 중 {$args[1]} 은 없습니다!" );
					return;
				}
				foreach ( $this->configData as $key => $value ) {
					if (strtolower ( $args [1] ) == $key) {
						unset ( $this->configData [$key] );
						$sender->sendMessage ( TextFormat::GREEN . "[투표] 투표 리스트 제거 : {$key}" );
					}
				}
				
				break;
			case "시작" :
				
				if (! $sender->isOp ())
					return;
				if ($this->vote) {
					$sender->sendMessage ( "[투표] 투표가 이미 시작된 상태입니다" );
					return;
				}
				$this->vote = true;
				foreach ( $this->getServer ()->getOnlinePlayers () as $player ) {
					$this->permission [strtolower ( $player->getName () )] = true;
				}
				$this->getServer ()->broadcastMessage ( TextFormat::GRAY . "[투표] 투표가 시작되었습니다!" );
				
				break;
			case "종료" :
				
				if (! $sender->isOp ())
					return;
				if (! $this->vote) {
					$sender->sendMessage ( "[투표] 투표가 이미 중단된 상태 입니다!" );
					return;
				}
				$this->vote = false;
				$this->getServer ()->broadcastMessage ( TextFormat::GRAY . "[투표] 투표가 종료되었습니다" );
				
				break;
			case "플레이어" :
				
				if (! $this->vote) {
					$sender->sendMessage ( "[투표] 투표가 현재 중단된 상태 입니다!" );
					return;
				}
				if (! isset ( $this->permission [strtolower ( $sender->getName () )] )) {
					$sender->sendMessage ( "[투표] 당신은 투표를 할수 없습니다" );
					return;
				}
				if (! isset ( $args [1] )) {
					$sender->sendMessage ( "[투표] 제대로 투표를 해주시길 바랍니다" );
					return;
				}
				if (! array_key_exists ( strtolower ( $args [1] ), $this->configData )) {
					$sender->sendMessage ( "[투표] 투표 리스트 중  {$args[1]} 는 없습니다!" );
					return;
				}
				foreach ( $this->configData as $key => $value ) {
					if (strtolower ( $args [1] ) == $key) {
						++ $this->configData [$key];
						$sender->sendMessage ( TextFormat::GREEN . "[투표] 당신은  {$args[1]} 을 투표하였습니다!" );
						unset ( $this->permission [strtolower ( $sender->getName () )] );
					}
				}
				
				break;
			case "결과" :
				
				if ($this->vote) {
					$sender->sendMessage ( "[투표] 투표가 진행중 입니다!" );
					return;
				}
				$page = 1;
				
				if (! isset ( $args [1] ) or ! is_numeric ( $args [1] )) {
					$sender->sendMessage ( TextFormat::RED . "[투표] -/투표 결과 <페이지>" );
					return;
				}
				if (count ( $this->configData ) < 1) {
					$sender->sendMessage ( "[투표] 현재 투표 데이터가 없습니다" );
					return;
				}
				
				$chunk = array_chunk ( $this->configData, 5, true );
				$count = count ( $chunk );
				if ($args [1] > $count)
					$page = $count;
				else
					$page = $args [1];
				$sender->sendMessage ( TextFormat::GREEN . "-=-=-=-=-=-=-= 투표 결과 ({$page}/{$count})-=-=-=-=-=-=-=" );
				$num = ($page - 1) * 5;
				foreach ( $chunk [$page - 1] as $key => $value ) {
					++ $num;
					$sender->sendMessage ( TextFormat::DARK_GREEN . "[{$num}] {$key} => {$value}" );
				}
				
				break;
			case "목록초기화" :
				
				if (! $sender->isOp ())
					return;
				if ($this->vote) {
					$sender->sendMessage ( "[투표] 현재 투표가 진행중입니다" );
					return;
				}
				$this->configData = [ ];
				$this->getServer ()->broadcastMessage ( TextFormat::GREEN . "[투표] {$sender->getName()} 님으로 인하여 투표 목록이 초기화 되었습니다" );
				
				break;
			case "득표초기화" :
				
				if (! $sender->isOp ())
					return;
				if ($this->vote) {
					$sender->sendMessage ( "[투표] 현재 투표가 진행중입니다" );
					return;
				}
				foreach ( $this->configData as $key => $value ) {
					$this->configData [$key] = 0;
				}
				$this->getServer ()->broadcastMessage ( TextFormat::GREEN . "[투표] {$sender->getName()} 님으로 인하여 득표가 초기화 되었숩니다" );
				
				break;
			default :
				$this->getHelper ( $sender, "[투표]" );
				return;
				break;
		}
		$this->saveYml ();
	}
	public function saveYml() {
		arsort ( $this->configData );
		$config = new Config($this->getDataFolder()."vote.yml", Config::YAML);
		$config->setAll($this->configData);
		$config->save();
	}
	public function getHelper(CommandSender $sender, $prefix) {
		$sender->sendMessage ( TextFormat::GREEN . "{$prefix} /투표 추가 <이름> - 이름 을 투표목록에 추가합니다" );
		$sender->sendMessage ( TextFormat::GREEN . "{$prefix} /투표 삭제 <이름> - 이름 을 투표목록에서 제거합니다" );
		$sender->sendMessage ( TextFormat::GREEN . "{$prefix} /투표 시작 - 투표를 시작합니다" );
		$sender->sendMessage ( TextFormat::GREEN . "{$prefix} /투표 종료 - 투표를 종료합니다" );
		$sender->sendMessage ( TextFormat::GREEN . "{$prefix} /투표 플레이어 <이름> - 이름 을 투표합니다" );
		$sender->sendMessage ( TextFormat::GREEN . "{$prefix} /투표 결과 - 투표 결과를 확인합니다" );
		$sender->sendMessage ( TextFormat::GREEN . "{$prefix} /투표 목록초기화 - 투표목록을 리셋합니다" );
		$sender->sendMessage ( TextFormat::GREEN . "{$prefix} /투표 득표초기화 - 모든 득표를 리셋합니다" );
	}
}

?>