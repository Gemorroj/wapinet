import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { provideTaiga } from '@taiga-ui/core';

import { FilesApi } from '../../../core/api/files-api';
import { FileStatistic } from './statistic';

describe('FileStatistic', () => {
    const statistics = vi.fn();

    beforeEach(async () => {
        statistics.mockReset();

        await TestBed.configureTestingModule({
            imports: [FileStatistic],
            providers: [{ provide: FilesApi, useValue: { statistics } }, provideRouter([]), provideTaiga()],
        }).compileComponents();
    });

    it('renders the totals and the active users', async () => {
        statistics.mockResolvedValue({
            categories: { video: 15 },
            countFiles: 15,
            countViews: 120,
            sizeFiles: 2048,
            users: [{ username: 'alice', uploads: 4 }],
        });

        const fixture = TestBed.createComponent(FileStatistic);

        await fixture.whenStable();
        fixture.detectChanges();

        const text = fixture.nativeElement.textContent;

        expect(text).toContain('Файлов');
        expect(text).toContain('15');
        expect(text).toContain('Объём');
        expect(text).toContain('2.0 КБ');
        expect(text).toContain('Просмотров');
        expect(text).toContain('120');
        expect(text).toContain('Активные пользователи');
        expect(text).toContain('alice');
        expect(text).toContain('4');
    });

    it('hides the users block when there are no uploads', async () => {
        statistics.mockResolvedValue({ categories: {}, countFiles: 0, countViews: 0, sizeFiles: 0, users: [] });

        const fixture = TestBed.createComponent(FileStatistic);

        await fixture.whenStable();
        fixture.detectChanges();

        expect(fixture.nativeElement.textContent).not.toContain('Активные пользователи');
    });

    it('shows the api error', async () => {
        statistics.mockRejectedValue(new Error('Статистика недоступна'));

        const fixture = TestBed.createComponent(FileStatistic);

        await fixture.whenStable();
        fixture.detectChanges();

        expect(fixture.nativeElement.textContent).toContain('Статистика недоступна');
    });
});
