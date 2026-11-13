import { HttpErrorResponse, HttpHeaders } from '@angular/common/http';
import { By } from '@angular/platform-browser';
import { TestBed } from '@angular/core/testing';
import { ActivatedRoute, Router, provideRouter } from '@angular/router';
import { provideTaiga } from '@taiga-ui/core';
import { BehaviorSubject } from 'rxjs';

import { ObfuscatorApi } from '../../../core/obfuscator-api';
import { CodeSourcePanel } from '../../../shared/code-source/code-source';
import { PhpObfuscator } from './php-obfuscator';

describe('PhpObfuscator', () => {
    const obfuscate = vi.fn();
    const info = vi.fn();
    let routeData: BehaviorSubject<Record<string, unknown>>;

    beforeEach(async () => {
        obfuscate.mockReset();
        info.mockReset();
        info.mockResolvedValue({ phpVersion: '8.5.12', maxCodeSize: 262144 });
        routeData = new BehaviorSubject<Record<string, unknown>>({ tab: 'tool' });

        await TestBed.configureTestingModule({
            imports: [PhpObfuscator],
            providers: [
                provideRouter([]),
                provideTaiga(),
                { provide: ObfuscatorApi, useValue: { obfuscate, info } },
                { provide: ActivatedRoute, useValue: { data: routeData } },
            ],
        }).compileComponents();

        vi.spyOn(TestBed.inject(Router), 'navigateByUrl').mockResolvedValue(true);
    });

    it('sends the code to the api and renders the result', async () => {
        obfuscate.mockResolvedValue({ code: '<?php echo base64_decode("SGVsbG8=");' });

        const fixture = TestBed.createComponent(PhpObfuscator);
        const component = fixture.componentInstance as unknown as {
            code: { set(value: string): void };
            obfuscate(): Promise<void>;
        };

        fixture.detectChanges();
        component.code.set('<?php echo "Hello";');
        fixture.detectChanges();
        await component.obfuscate();
        fixture.detectChanges();

        expect(obfuscate).toHaveBeenCalledWith({ code: '<?php echo "Hello";' }, true, 'max');
        expect(fixture.nativeElement.textContent).toContain('base64_decode');
    });

    it('sends the selected level', async () => {
        obfuscate.mockResolvedValue({ code: '<?php echo 1;', sourceSize: 10, resultSize: 20 });

        const fixture = TestBed.createComponent(PhpObfuscator);
        const component = fixture.componentInstance as unknown as {
            code: { set(value: string): void };
            level: { set(value: string): void };
            obfuscate(): Promise<void>;
        };

        fixture.detectChanges();
        component.code.set('<?php echo "Hello";');
        component.level.set('light');
        fixture.detectChanges();
        await component.obfuscate();

        expect(obfuscate).toHaveBeenCalledWith({ code: '<?php echo "Hello";' }, true, 'light');
    });

    it('shows the size change', async () => {
        obfuscate.mockResolvedValue({ code: '<?php echo 1;', sourceSize: 1000, resultSize: 1500 });

        const fixture = TestBed.createComponent(PhpObfuscator);
        const component = fixture.componentInstance as unknown as {
            code: { set(value: string): void };
            obfuscate(): Promise<void>;
        };

        fixture.detectChanges();
        component.code.set('<?php echo "Hello";');
        fixture.detectChanges();
        await component.obfuscate();
        fixture.detectChanges();

        expect(fixture.nativeElement.textContent).toContain('+50%');
    });

    it('sends a URL as the source', async () => {
        obfuscate.mockResolvedValue({ code: '<?php echo 1;' });

        const fixture = TestBed.createComponent(PhpObfuscator);
        const component = fixture.componentInstance as unknown as { obfuscate(): Promise<void> };

        fixture.detectChanges();

        const panel = fixture.debugElement.query(By.directive(CodeSourcePanel)).componentInstance as unknown as {
            mode: { set(value: 'file'): void };
            url: { set(value: string): void };
        };

        panel.mode.set('file');
        panel.url.set('https://example.com/a.php');
        await component.obfuscate();

        expect(obfuscate).toHaveBeenCalledWith({ url: 'https://example.com/a.php' }, true, 'max');
    });

    it('reads a selected file and sends its text', async () => {
        obfuscate.mockResolvedValue({ code: '<?php echo 1;' });

        const fixture = TestBed.createComponent(PhpObfuscator);
        const component = fixture.componentInstance as unknown as { obfuscate(): Promise<void> };

        fixture.detectChanges();

        const panel = fixture.debugElement.query(By.directive(CodeSourcePanel)).componentInstance as unknown as {
            mode: { set(value: 'file'): void };
            files: { set(value: File[]): void };
        };

        panel.mode.set('file');
        panel.files.set([new File(['<?php echo "Hello";'], 'a.php', { type: 'text/plain' })]);
        await component.obfuscate();

        expect(obfuscate).toHaveBeenCalledWith({ code: '<?php echo "Hello";' }, true, 'max');
    });

    it('shows the api error', async () => {
        obfuscate.mockRejectedValue(new Error('Некорректный PHP'));

        const fixture = TestBed.createComponent(PhpObfuscator);
        const component = fixture.componentInstance as unknown as {
            code: { set(value: string): void };
            obfuscate(): Promise<void>;
        };

        fixture.detectChanges();
        component.code.set('<?php if (');
        fixture.detectChanges();
        await component.obfuscate();
        fixture.detectChanges();

        expect(fixture.nativeElement.textContent).toContain('Некорректный PHP');
    });

    it('formats the rate limit message from the Retry-After header', async () => {
        obfuscate.mockRejectedValue(
            new HttpErrorResponse({
                status: 429,
                headers: new HttpHeaders({ 'Retry-After': '300' }),
            }),
        );

        const fixture = TestBed.createComponent(PhpObfuscator);
        const component = fixture.componentInstance as unknown as {
            code: { set(value: string): void };
            obfuscate(): Promise<void>;
        };

        fixture.detectChanges();
        component.code.set('<?php echo 1;');
        fixture.detectChanges();
        await component.obfuscate();
        fixture.detectChanges();

        expect(fixture.nativeElement.textContent).toContain('Слишком много запросов. Попробуйте через 5 минут.');
    });

    it('does nothing for empty code', async () => {
        const fixture = TestBed.createComponent(PhpObfuscator);
        const component = fixture.componentInstance as unknown as { obfuscate(): Promise<void> };

        fixture.detectChanges();
        await component.obfuscate();

        expect(obfuscate).not.toHaveBeenCalled();
    });

    it('shows the supported php version and limits in the about tab', async () => {
        const fixture = TestBed.createComponent(PhpObfuscator);

        fixture.detectChanges();
        routeData.next({ tab: 'about' });
        await fixture.whenStable();
        fixture.detectChanges();

        const text = fixture.nativeElement.textContent as string;

        expect(info).toHaveBeenCalled();
        expect(text).toContain('Чего она не делает');
        expect(text).toContain('PHP до 8.5');
        expect(text).toContain('256 КБ');
        expect(fixture.nativeElement.querySelector('app-breadcrumbs')?.textContent).toContain('Об обфускации');
    });

    it('navigates when a tab is selected', () => {
        const fixture = TestBed.createComponent(PhpObfuscator);
        const component = fixture.componentInstance as unknown as { selectTab(index: number): void };

        fixture.detectChanges();
        component.selectTab(1);

        expect(TestBed.inject(Router).navigateByUrl).toHaveBeenCalledWith('/php_obfuscator/about');
    });
});
