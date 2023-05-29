<?php

namespace App\Controller\Games;

use App\Repository\GameRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;

class PlayGameController extends AbstractController
{
    const CHOICES = ['rock', 'paper', 'scissors'];
    const WIN_RIGHT = 'winRight';
    const WIN_LEFT = 'winLeft';
    const DRAW = 'draw';

    public function __construct(
        private readonly GameRepository $gameRepository,
        private readonly UserRepository $userRepository
    ) {}

    #[Route(
        path: '/game/{id}',
        name: 'play_game_by_id',
        methods: ['PATCH']
    )]
    public function play(Request $request, $id): JsonResponse
    {
        $currentUserId = $request->headers->get('X-User-Id');
        if (!$currentUserId || !ctype_digit($currentUserId)) {
            return new JsonResponse('User not found', 401);
        }

        $currentUser = $this->userRepository->findOneBy(['id' => $currentUserId]);
        if (!$currentUser) {
            return new JsonResponse('User not found', 401);
        }

        if (ctype_digit($id) === false) {
            return new JsonResponse('Game not found', 404);
        }

        $game = $this->gameRepository->findOneBy(['id' => $id]);
        if (!$game) {
            return new JsonResponse('Game not found', 404);
        }

        $userIsPlayerLeft = $game->getPlayerLeft()?->getId() === $currentUser->getId();
        $userIsPlayerRight = $game->getPlayerRight()?->getId() === $currentUser->getId();

        if (!$userIsPlayerLeft && !$userIsPlayerRight) {
            return new JsonResponse('You are not a player of this game', 403);
        }

        if($game->getState() === 'finished' || $game->getState() === 'pending'){
            return new JsonResponse('Game not started', 409);
        }

        $form = $this->createFormBuilder()
            ->add('choice', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ])
            ->getForm();

        $choice = json_decode($request->getContent(), true);

        $form->submit($choice);

        if(!$form->isValid()) {
            return new JsonResponse('Invalid choice', 400);
        }

        $data = $form->getData();

        if (!in_array($data['choice'], PlayGameController::CHOICES)) {
            return new JsonResponse('Invalid choice', 400);
        }

        if ($userIsPlayerLeft) {
            $leftPlayerPlay = $data['choice'];
            $rightPlayerPlay = $game->getPlayRight();
            $game->setPlayLeft($leftPlayerPlay);
        } else {
            $leftPlayerPlay = $game->getPlayLeft();
            $rightPlayerPlay = $data['choice'];
            $game->setPlayRight($rightPlayerPlay);
        }

        $result = $this->comparePlays($leftPlayerPlay, $rightPlayerPlay);

        if ($result) {
            $game->setResult($result);
            $game->setState('finished');
        }
        $this->gameRepository->save($game, flush: true);

        return $this->json(
            $game,
            headers: ['Content-Type' => 'application/json;charset=UTF-8']
        );
    }

    /**
     * @param string | null $leftPlayerPlay
     * @param string | null $rightPlayerPlay
     * @return string | null
     */
    function comparePlays(?string $leftPlayerPlay, ?string $rightPlayerPlay): ?string
    {
        if ($leftPlayerPlay === $rightPlayerPlay) {
            return PlayGameController::DRAW;
        }

        if (!$leftPlayerPlay || !$rightPlayerPlay) {
            return null;
        }

        return match ($leftPlayerPlay) {
            'rock' => $rightPlayerPlay === 'paper' ? PlayGameController::WIN_RIGHT : PlayGameController::WIN_LEFT,
            'paper' => $rightPlayerPlay === 'scissors' ? PlayGameController::WIN_RIGHT : PlayGameController::WIN_LEFT,
            'scissors' => $rightPlayerPlay === 'rock' ? PlayGameController::WIN_RIGHT : PlayGameController::WIN_LEFT,
        };
    }
}
