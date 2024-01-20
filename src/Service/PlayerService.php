<?php

namespace App\Service;

use App\Repository\UserRepository;

class PlayerService{

    private UserRepository $playerRepository;

    public function __construct(UserRepository $playerRepository)
    {
        $this->playerRepository = $playerRepository;
    }
    
    public function getPlayerByToken(string $token){
        $explodedToken = explode('_', $token);

        if(count($explodedToken) === 2){
            $playerId = $explodedToken[1];
            $player = $this->playerRepository->getPlayerByToken($playerId);

            return $player;
        }else{
            return null;
        }
    }

}