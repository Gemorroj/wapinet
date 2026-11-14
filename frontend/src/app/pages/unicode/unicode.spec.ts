import { TestBed } from '@angular/core/testing';
import { ActivatedRoute, Router, convertToParamMap, provideRouter } from '@angular/router';
import { provideTaiga } from '@taiga-ui/core';
import { of } from 'rxjs';

import { UNICODE_SECTIONS } from './unicode-symbols';
import { UnicodeTool } from './unicode';

describe('Unicode', () => {
    const route = {
        data: of({}),
        snapshot: { data: {} },
        queryParamMap: of(convertToParamMap({})),
    };

    const format = async (data: Record<string, unknown> = {}): Promise<void> => {
        TestBed.resetTestingModule();

        await TestBed.configureTestingModule({
            imports: [UnicodeTool],
            providers: [
                provideRouter([]),
                provideTaiga(),
                {
                    provide: ActivatedRoute,
                    useValue: { ...route, data: of(data), snapshot: { data } },
                },
            ],
        }).compileComponents();
    };

    it('converts the text on the fly', async () => {
        await format();

        const fixture = TestBed.createComponent(UnicodeTool);
        const component = fixture.componentInstance as unknown as { text: { set: (value: string) => void } };

        component.text.set('Привет');

        fixture.detectChanges();
        await fixture.whenStable();
        fixture.detectChanges();

        const text = fixture.nativeElement.textContent as string;

        expect(text).toContain('&#x041f;&#x0440;&#x0438;&#x0432;&#x0435;&#x0442;');
    });

    it('shows the pictograms tab for the icons route', async () => {
        await format({ tab: 'icons' });

        const fixture = TestBed.createComponent(UnicodeTool);

        fixture.detectChanges();
        await fixture.whenStable();
        fixture.detectChanges();

        const text = fixture.nativeElement.textContent as string;

        expect(text).toContain('Справочник символов');
        expect(text).toContain(UNICODE_SECTIONS[0].title);
        expect(fixture.nativeElement.querySelector('app-breadcrumbs a[href="/unicode"]')).not.toBeNull();
    });

    it('filters sections by name and by hex code', async () => {
        await format({ tab: 'icons' });

        const fixture = TestBed.createComponent(UnicodeTool);
        const component = fixture.componentInstance as unknown as { query: { set: (value: string) => void } };

        fixture.detectChanges();
        await fixture.whenStable();

        component.query.set('тире');
        fixture.detectChanges();

        const text = fixture.nativeElement.textContent as string;

        expect(text).toContain('тире длины N');
        expect(text).not.toContain('неразрывный пробел');

        component.query.set('2190');
        fixture.detectChanges();

        const result = fixture.nativeElement.textContent as string;

        expect(result).toContain('&#8592;');
    });

    it('keeps the whole section when its title matches the query', async () => {
        await format({ tab: 'icons' });

        const fixture = TestBed.createComponent(UnicodeTool);
        const component = fixture.componentInstance as unknown as { query: { set: (value: string) => void } };

        fixture.detectChanges();
        await fixture.whenStable();

        component.query.set('стрелка');
        fixture.detectChanges();

        const text = fixture.nativeElement.textContent as string;

        expect(text).toContain('Стрелки и указатели');
        expect(text).toContain('&larr;');
    });

    it('navigates between the tabs', async () => {
        await format();

        const fixture = TestBed.createComponent(UnicodeTool);
        const router = TestBed.inject(Router);
        const navigate = vi.spyOn(router, 'navigateByUrl').mockResolvedValue(true);

        fixture.detectChanges();
        await fixture.whenStable();
        fixture.detectChanges();

        const buttons = [...fixture.nativeElement.querySelectorAll('button[tuiTab]')] as HTMLButtonElement[];

        expect(buttons).toHaveLength(4);

        buttons[1].click();
        expect(navigate).toHaveBeenCalledWith('/unicode/icons');

        buttons[2].click();
        expect(navigate).toHaveBeenCalledWith('/unicode/inspector');

        buttons[3].click();
        expect(navigate).toHaveBeenCalledWith('/unicode/about');
    });

    it('decodes entities on the fly', async () => {
        await format();

        const fixture = TestBed.createComponent(UnicodeTool);
        const component = fixture.componentInstance as unknown as {
            mode: { set: (value: string) => void };
            text: { set: (value: string) => void };
        };

        fixture.detectChanges();
        await fixture.whenStable();

        component.mode.set('decode');
        component.text.set('&#x041f;&#x0440;&#x0438;');
        fixture.detectChanges();
        await fixture.whenStable();
        fixture.detectChanges();

        expect(fixture.nativeElement.textContent).toContain('При');
    });

    it('shows character details in the inspector', async () => {
        await format({ tab: 'inspector' });

        const fixture = TestBed.createComponent(UnicodeTool);
        const component = fixture.componentInstance as unknown as { inspectText: { set: (value: string) => void } };

        fixture.detectChanges();
        await fixture.whenStable();

        component.inspectText.set('😀');
        fixture.detectChanges();
        await fixture.whenStable();
        fixture.detectChanges();

        const text = fixture.nativeElement.textContent as string;

        expect(text).toContain('U+1F600');
        expect(text).toContain('F0 9F 98 80');
    });

    it('renders the unicode overview', async () => {
        await format({ tab: 'about' });

        const fixture = TestBed.createComponent(UnicodeTool);

        fixture.detectChanges();
        await fixture.whenStable();
        fixture.detectChanges();

        expect(fixture.nativeElement.textContent).toContain('Что такое Unicode');
        expect(fixture.nativeElement.textContent).toContain('UTF-8');
    });
});
