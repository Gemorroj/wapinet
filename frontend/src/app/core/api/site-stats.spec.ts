import { TestBed } from '@angular/core/testing';

import { HomeApi } from './home-api';
import { SiteStats } from './site-stats';

describe('SiteStats', () => {
    let stats: ReturnType<typeof vi.fn>;

    beforeEach(() => {
        stats = vi.fn().mockResolvedValue({
            files: 12,
            gists: 34,
            guestbook: 7538,
            online: 5,
            newsLastDate: null,
            forumTopics: 1670,
            forumPosts: 24027,
        });

        TestBed.configureTestingModule({
            providers: [{ provide: HomeApi, useValue: { stats } }],
        });
    });

    it('loads counters once and shares them', async () => {
        const service = TestBed.inject(SiteStats);

        expect(service.value()).toBeNull();

        await Promise.all([service.load(), service.load()]);
        await service.load();

        expect(stats).toHaveBeenCalledTimes(1);
        expect(service.value()?.online).toBe(5);
    });
});
