import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { provideTaiga } from '@taiga-ui/core';

import { FilesApi } from '../../../core/files-api';
import { FileCategories } from './categories';

describe('FileCategories', () => {
    const statistics = vi.fn();

    beforeEach(async () => {
        statistics.mockReset();

        await TestBed.configureTestingModule({
            imports: [FileCategories],
            providers: [{ provide: FilesApi, useValue: { statistics } }, provideRouter([]), provideTaiga()],
        }).compileComponents();
    });

    it('renders the categories with their counts', async () => {
        statistics.mockResolvedValue({
            categories: { video: 12, audio: 3 },
            countFiles: 15,
            countViews: 7,
            sizeFiles: 2048,
            users: [],
        });

        const fixture = TestBed.createComponent(FileCategories);

        await fixture.whenStable();
        fixture.detectChanges();

        expect(fixture.nativeElement.textContent).toContain('Видео');
        expect(fixture.nativeElement.textContent).toContain('12');
        expect(fixture.nativeElement.textContent).toContain('Аудио');
        expect(fixture.nativeElement.textContent).toContain('3');
        expect(fixture.nativeElement.querySelector('a[href*="category=video"]')).not.toBeNull();
    });

    it('shows the api error', async () => {
        statistics.mockRejectedValue(new Error('Статистика недоступна'));

        const fixture = TestBed.createComponent(FileCategories);

        await fixture.whenStable();
        fixture.detectChanges();

        expect(fixture.nativeElement.textContent).toContain('Статистика недоступна');
    });
});
