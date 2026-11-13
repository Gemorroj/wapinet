import { Injectable, inject } from '@angular/core';

import { Api } from '../api/generated/api';
import {
    deleteApiUsersFriendsDelete,
    getApiUsersEvents,
    getApiUsersFriends,
    getApiUsersList,
    getApiUsersShow,
    getApiUsersSubscriber,
    postApiAuthRegister,
    postApiUsersFriendsAdd,
    putApiUsersProfileUpdate,
    putApiUsersSubscriberUpdate,
} from '../api/generated/functions';
import {
    EventListResponse,
    ProfileUpdateRequest,
    RegisterRequest,
    RegisterResponse,
    SubscriberResponse,
    SubscriberUpdateRequest,
    UserFilterQuery,
    UserListResponse,
    UserProfileResponse,
} from '../api/generated/models';

@Injectable({ providedIn: 'root' })
export class UsersApi {
    private readonly api = inject(Api);

    list(params: { filter?: UserFilterQuery; page?: number } = {}): Promise<UserListResponse> {
        return this.api.invoke(getApiUsersList, params);
    }

    profile(username: string): Promise<UserProfileResponse> {
        return this.api.invoke(getApiUsersShow, { username });
    }

    friends(username: string, page = 1): Promise<UserListResponse> {
        return this.api.invoke(getApiUsersFriends, { username, page });
    }

    events(page = 1): Promise<EventListResponse> {
        return this.api.invoke(getApiUsersEvents, { page });
    }

    subscriber(): Promise<SubscriberResponse> {
        return this.api.invoke(getApiUsersSubscriber, {});
    }

    updateSubscriber(body: SubscriberUpdateRequest): Promise<SubscriberResponse> {
        return this.api.invoke(putApiUsersSubscriberUpdate, { body });
    }

    updateProfile(body: ProfileUpdateRequest): Promise<UserProfileResponse> {
        return this.api.invoke(putApiUsersProfileUpdate, { body });
    }

    async addFriend(username: string): Promise<void> {
        await this.api.invoke(postApiUsersFriendsAdd, { username });
    }

    async removeFriend(username: string): Promise<void> {
        await this.api.invoke(deleteApiUsersFriendsDelete, { username });
    }

    register(body: RegisterRequest): Promise<RegisterResponse> {
        return this.api.invoke(postApiAuthRegister, { body });
    }
}
