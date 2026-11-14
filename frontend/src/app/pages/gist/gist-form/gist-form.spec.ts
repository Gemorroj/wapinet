import { TestBed } from '@angular/core/testing';
import { ActivatedRoute, convertToParamMap, provideRouter } from '@angular/router';
import { provideTaiga } from '@taiga-ui/core';

import { GistsApi } from '../../../core/api/gists-api';
import { GistForm } from './gist-form';

describe('GistForm', () => {
    const create = vi.fn().mockResolvedValue({ id: 5 });
    const route = { snapshot: { paramMap: convertToParamMap({}) } };

    beforeEach(async () => {
        create.mockClear();

        await TestBed.configureTestingModule({
            imports: [GistForm],
            providers: [
                { provide: GistsApi, useValue: { create, show: vi.fn(), update: vi.fn() } },
                provideRouter([]),
                { provide: ActivatedRoute, useValue: route },
                provideTaiga(),
            ],
        }).compileComponents();
    });

    it('creates a blog post', async () => {
        const fixture = TestBed.createComponent(GistForm);

        await fixture.whenStable();
        fixture.detectChanges();

        const subject = fixture.nativeElement.querySelector('input[name="subject"]') as HTMLInputElement;
        const body = fixture.nativeElement.querySelector('textarea[name="body"]') as HTMLTextAreaElement;

        subject.value = 'Тема';
        subject.dispatchEvent(new Event('input'));
        body.value = 'Текст';
        body.dispatchEvent(new Event('input'));
        await fixture.whenStable();

        fixture.nativeElement.querySelector('form').dispatchEvent(new Event('submit'));
        await fixture.whenStable();

        expect(create).toHaveBeenCalledWith({
            subject: 'Тема',
            body: 'Текст',
            bCheck: null,
        });

        fixture.destroy();
    });
});
