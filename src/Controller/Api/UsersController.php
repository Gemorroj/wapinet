<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\Api\User\EventListResponse;
use App\Dto\Api\User\ProfileUpdateRequest;
use App\Dto\Api\User\SubscriberResponse;
use App\Dto\Api\User\SubscriberUpdateRequest;
use App\Dto\Api\User\UserListQuery;
use App\Dto\Api\User\UserListResponse;
use App\Dto\Api\User\UserProfileResponse;
use App\Entity\Event;
use App\Entity\User;
use App\Entity\UserFriend;
use App\Exception\PublicHttpException;
use App\Repository\EventRepository;
use App\Repository\UserRepository;
use App\Service\Api\User\UserPresenter;
use App\Service\Api\User\UserSearch;
use App\Service\Api\User\UserService;
use App\Service\Paginate;
use App\Service\Search\SearchUnavailableException;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/users', name: 'api_users_')]
#[OA\Tag(name: 'Users')]
final class UsersController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Users online or found by search',
        content: new Model(type: UserListResponse::class),
    )]
    #[OA\Response(response: 503, description: 'Search engine is unavailable')]
    public function list(
        #[MapQueryString]
        UserListQuery $query,
        UserRepository $userRepository,
        UserSearch $userSearch,
        Paginate $paginate,
        UserPresenter $presenter,
    ): JsonResponse {
        $page = \max(1, $query->page);
        $search = \trim((string) $query->filter->search);

        if ('' !== $search) {
            try {
                $pager = $userSearch->search($search, $page);
            } catch (SearchUnavailableException $exception) {
                throw new PublicHttpException(503, 'Search is unavailable.', $exception);
            }
        } else {
            $pager = $paginate->paginate($userRepository->getOnlineUsersQuery(), $page);
        }

        $items = [];

        foreach ($pager as $user) {
            $items[] = $presenter->listItem($user);
        }

        return $this->json(new UserListResponse(
            $items,
            $pager->getNbResults(),
            $pager->getCurrentPage(),
            $pager->getMaxPerPage(),
        ));
    }

    #[Route('/events', name: 'events', methods: ['GET'])]
    #[OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1))]
    #[OA\Response(
        response: 200,
        description: 'Events of the current user',
        content: new Model(type: EventListResponse::class),
    )]
    public function events(
        Request $request,
        EventRepository $eventRepository,
        Paginate $paginate,
        UserPresenter $presenter,
        #[CurrentUser]
        User $user,
    ): JsonResponse {
        $page = \max(1, (int) $request->query->get('page', 1));
        $pager = $paginate->paginate($eventRepository->findEventsQuery($user), $page);

        $items = [];

        foreach ($pager as $event) {
            \assert($event instanceof Event);
            $items[] = $presenter->event($event);
        }

        return $this->json(new EventListResponse(
            $items,
            $pager->getNbResults(),
            $pager->getCurrentPage(),
            $pager->getMaxPerPage(),
        ));
    }

    #[Route('/subscriber', name: 'subscriber', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'E-mail subscription settings of the current user',
        content: new Model(type: SubscriberResponse::class),
    )]
    public function subscriber(#[CurrentUser] User $user): JsonResponse
    {
        $subscriber = $user->getSubscriber();

        return $this->json(new SubscriberResponse(
            $subscriber?->isEmailNews() ?? false,
            $subscriber?->isEmailFriends() ?? false,
        ));
    }

    #[Route('/subscriber', name: 'subscriber_update', methods: ['PUT'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: new Model(type: SubscriberUpdateRequest::class)),
    )]
    #[OA\Response(
        response: 200,
        description: 'Updated subscription settings',
        content: new Model(type: SubscriberResponse::class),
    )]
    public function updateSubscriber(
        #[CurrentUser]
        User $user,
        #[MapRequestPayload]
        SubscriberUpdateRequest $payload,
        UserService $userService,
    ): JsonResponse {
        $userService->updateSubscriber($user, $payload);

        return $this->subscriber($user);
    }

    #[Route('/profile', name: 'profile_update', methods: ['PUT'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: new Model(type: ProfileUpdateRequest::class)),
    )]
    #[OA\Response(
        response: 200,
        description: 'Updated profile of the current user',
        content: new Model(type: UserProfileResponse::class),
    )]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function updateProfile(
        #[CurrentUser]
        User $user,
        #[MapRequestPayload]
        ProfileUpdateRequest $payload,
        UserService $userService,
        UserPresenter $presenter,
    ): JsonResponse {
        $userService->updateProfile($user, $payload);

        return $this->json($presenter->profile($user, $user));
    }

    #[Route('/{username}/friends', name: 'friends', methods: ['GET'], requirements: ['username' => '[^/]+'])]
    #[OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1))]
    #[OA\Response(
        response: 200,
        description: 'Friends of the user',
        content: new Model(type: UserListResponse::class),
    )]
    #[OA\Response(response: 404, description: 'User not found')]
    public function friends(
        Request $request,
        string $username,
        UserRepository $userRepository,
        \App\Repository\UserFriendRepository $friendRepository,
        Paginate $paginate,
        UserPresenter $presenter,
    ): JsonResponse {
        $user = $this->findUser($userRepository, $username);
        $page = \max(1, (int) $request->query->get('page', 1));
        $pager = $paginate->paginate($friendRepository->getFriendsQuery($user), $page);

        $items = [];

        foreach ($pager as $friend) {
            \assert($friend instanceof UserFriend);
            $items[] = $presenter->listItem($friend->getFriend());
        }

        return $this->json(new UserListResponse(
            $items,
            $pager->getNbResults(),
            $pager->getCurrentPage(),
            $pager->getMaxPerPage(),
        ));
    }

    #[Route('/{username}/friends', name: 'friends_add', methods: ['POST'], requirements: ['username' => '[^/]+'])]
    #[OA\Response(response: 204, description: 'Friend added')]
    #[OA\Response(response: 404, description: 'User not found')]
    #[OA\Response(response: 409, description: 'User is already a friend')]
    public function addFriend(
        string $username,
        UserRepository $userRepository,
        UserService $userService,
        #[CurrentUser]
        User $user,
    ): JsonResponse {
        $userService->addFriend($user, $this->findUser($userRepository, $username));

        return $this->json(null, 204);
    }

    #[Route('/{username}/friends', name: 'friends_delete', methods: ['DELETE'], requirements: ['username' => '[^/]+'])]
    #[OA\Response(response: 204, description: 'Friend removed')]
    #[OA\Response(response: 404, description: 'User not found')]
    #[OA\Response(response: 409, description: 'User is not a friend')]
    public function removeFriend(
        string $username,
        UserRepository $userRepository,
        UserService $userService,
        #[CurrentUser]
        User $user,
    ): JsonResponse {
        $userService->removeFriend($user, $this->findUser($userRepository, $username));

        return $this->json(null, 204);
    }

    #[Route('/{username}', name: 'show', methods: ['GET'], requirements: ['username' => '[^/]+'])]
    #[OA\Response(
        response: 200,
        description: 'User profile',
        content: new Model(type: UserProfileResponse::class),
    )]
    #[OA\Response(response: 404, description: 'User not found')]
    public function show(
        string $username,
        UserRepository $userRepository,
        UserPresenter $presenter,
        #[CurrentUser]
        User $viewer,
    ): JsonResponse {
        return $this->json($presenter->profile($this->findUser($userRepository, $username), $viewer));
    }

    private function findUser(UserRepository $userRepository, string $username): User
    {
        $user = $userRepository->findOneBy(['username' => $username]);

        if (null === $user) {
            throw new PublicHttpException(404, 'Пользователь не найден.');
        }

        return $user;
    }
}
