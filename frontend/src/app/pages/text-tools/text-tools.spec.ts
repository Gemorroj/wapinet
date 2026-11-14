import { TestBed } from '@angular/core/testing';
import { ActivatedRoute, Router, provideRouter } from '@angular/router';
import { provideTaiga } from '@taiga-ui/core';
import { of } from 'rxjs';

import { TextTools } from './text-tools';

describe('TextTools', () => {
    beforeEach(async () => {
        await TestBed.configureTestingModule({
            imports: [TextTools],
            providers: [
                provideRouter([]),
                provideTaiga(),
                {
                    provide: ActivatedRoute,
                    useValue: { data: of({ tab: 'encoding' }) },
                },
            ],
        }).compileComponents();

        vi.spyOn(TestBed.inject(Router), 'navigateByUrl').mockResolvedValue(true);
    });

    it('converts text with the default operation', () => {
        const fixture = TestBed.createComponent(TextTools);
        const component = fixture.componentInstance as unknown as { text: { set(value: string): void } };

        fixture.detectChanges();
        component.text.set('hello');
        fixture.detectChanges();

        expect(fixture.nativeElement.textContent).toContain('aGVsbG8=');
    });

    it('switches groups and operations', () => {
        const fixture = TestBed.createComponent(TextTools);
        const component = fixture.componentInstance as unknown as {
            text: { set(value: string): void };
            selectGroup(index: number): void;
            selectOperation(id: string): void;
        };

        fixture.detectChanges();
        component.selectGroup(2);
        component.selectOperation('upper');
        component.text.set('привет');
        fixture.detectChanges();

        expect(fixture.nativeElement.textContent).toContain('ПРИВЕТ');
    });

    it('updates breadcrumbs when group changes', () => {
        const fixture = TestBed.createComponent(TextTools);
        const component = fixture.componentInstance as unknown as { selectGroup(index: number): void };

        fixture.detectChanges();
        component.selectGroup(2);
        fixture.detectChanges();

        expect(fixture.nativeElement.textContent).toContain('Текст');
        expect(fixture.nativeElement.querySelector('app-breadcrumbs a[href="/text-tools"]')).not.toBeNull();
    });

    it('links to the Unicode inspector', () => {
        const fixture = TestBed.createComponent(TextTools);

        fixture.detectChanges();

        expect(fixture.nativeElement.querySelector('a[href="/unicode/inspector"]')).not.toBeNull();
    });

    it('shows a conversion error', () => {
        const fixture = TestBed.createComponent(TextTools);
        const component = fixture.componentInstance as unknown as {
            text: { set(value: string): void };
            selectOperation(id: string): void;
        };

        fixture.detectChanges();
        component.selectOperation('json-format');
        component.text.set('{');
        fixture.detectChanges();

        expect(fixture.nativeElement.textContent).toContain('Некорректный JSON');
    });
});
