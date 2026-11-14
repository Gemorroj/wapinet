import { By } from '@angular/platform-browser';
import { TestBed } from '@angular/core/testing';
import { ActivatedRoute, Data, Router, provideRouter } from '@angular/router';
import { provideTaiga } from '@taiga-ui/core';
import { BehaviorSubject } from 'rxjs';

import { ValidatorApi } from '../../core/api/validator-api';
import { CodeSourcePanel } from '../../shared/code-source/code-source';
import { ValidatorTool } from './validator';

describe('ValidatorTool', () => {
    const validate = vi.fn();
    const phpInfo = vi.fn().mockResolvedValue({ phpVersion: '8.5.12' });
    let routeData: BehaviorSubject<Data>;

    beforeEach(async () => {
        validate.mockReset();
        routeData = new BehaviorSubject<Data>({ tool: 'php', tab: 'tool' });

        await TestBed.configureTestingModule({
            imports: [ValidatorTool],
            providers: [
                provideRouter([]),
                provideTaiga(),
                { provide: ValidatorApi, useValue: { validate, phpInfo } },
                { provide: ActivatedRoute, useValue: { data: routeData } },
            ],
        }).compileComponents();

        vi.spyOn(TestBed.inject(Router), 'navigateByUrl').mockResolvedValue(true);
    });

    it('sends PHP code and renders errors', async () => {
        validate.mockResolvedValue({
            valid: false,
            source: '<?php\nif (',
            phpVersion: '8.5.12',
            messages: [
                {
                    severity: 'error',
                    type: 'Parse error',
                    line: 2,
                    column: null,
                    message: 'Unclosed "("',
                    extract: null,
                },
            ],
        });

        const fixture = TestBed.createComponent(ValidatorTool);
        const component = fixture.componentInstance as unknown as {
            code: { set(value: string): void };
            validate(): Promise<void>;
        };

        fixture.detectChanges();
        await fixture.whenStable();
        component.code.set('<?php\nif (');
        fixture.detectChanges();
        await component.validate();
        fixture.detectChanges();

        expect(validate).toHaveBeenCalledWith('php', { code: '<?php\nif (' }, undefined);
        expect(fixture.nativeElement.textContent).toContain('Найдены ошибки: 1.');
        expect(fixture.nativeElement.textContent).toContain('Unclosed "("');
        expect(fixture.nativeElement.textContent).toContain('Строка 2');
        expect(fixture.nativeElement.querySelector('app-source-view')?.textContent).toContain('if (');
        expect(fixture.nativeElement.querySelector('#validator-line-2')).toBeTruthy();
    });

    it('filters messages and shows result metadata', async () => {
        routeData.next({ tool: 'html', tab: 'tool' });
        validate.mockResolvedValue({
            valid: false,
            source: '<html>\n<body>\n<div>',
            documentType: 'HTML5',
            encoding: 'UTF-8',
            messages: [
                { severity: 'error', type: null, line: 1, column: 2, message: 'Bad error', extract: null },
                { severity: 'warning', type: null, line: 3, column: null, message: 'Bad warning', extract: null },
            ],
        });

        const fixture = TestBed.createComponent(ValidatorTool);
        const component = fixture.componentInstance as unknown as {
            code: { set(value: string): void };
            filter: { set(value: string): void };
            validate(): Promise<void>;
        };

        await fixture.whenStable();
        fixture.detectChanges();
        component.code.set('<html>\n<body>\n<div>');
        fixture.detectChanges();
        await component.validate();
        fixture.detectChanges();

        expect(fixture.nativeElement.textContent).toContain('Тип документа');
        expect(fixture.nativeElement.textContent).toContain('HTML5');
        expect(fixture.nativeElement.textContent).toContain('Кодировка');
        expect(fixture.nativeElement.textContent).toContain('UTF-8');
        expect(fixture.nativeElement.textContent).toContain('Все (2)');
        expect(fixture.nativeElement.textContent).toContain('Bad error');
        expect(fixture.nativeElement.textContent).toContain('Bad warning');

        component.filter.set('warning');
        fixture.detectChanges();

        expect(fixture.nativeElement.textContent).toContain('Bad warning');
        expect(fixture.nativeElement.textContent).not.toContain('Bad error');
    });

    it('passes CSS profile and warning options', async () => {
        routeData.next({ tool: 'css', tab: 'tool' });
        validate.mockResolvedValue({ valid: true, messages: [] });

        const fixture = TestBed.createComponent(ValidatorTool);
        const component = fixture.componentInstance as unknown as {
            code: { set(value: string): void };
            validate(): Promise<void>;
        };

        await fixture.whenStable();
        fixture.detectChanges();
        component.code.set('body { color: red; }');
        fixture.detectChanges();
        await component.validate();
        fixture.detectChanges();

        expect(validate).toHaveBeenCalledWith('css', { code: 'body { color: red; }' }, { profile: 'css3', warnings: '1' });
        expect(fixture.nativeElement.textContent).toContain('Ошибок не найдено.');
    });

    it('sends a URL as the source', async () => {
        validate.mockResolvedValue({ valid: true, messages: [] });

        const fixture = TestBed.createComponent(ValidatorTool);
        const component = fixture.componentInstance as unknown as { validate(): Promise<void> };

        fixture.detectChanges();

        const panel = fixture.debugElement.query(By.directive(CodeSourcePanel)).componentInstance as unknown as {
            mode: { set(value: 'file'): void };
            url: { set(value: string): void };
        };

        panel.mode.set('file');
        panel.url.set('https://example.com/a.php');
        await component.validate();

        expect(validate).toHaveBeenCalledWith('php', { url: 'https://example.com/a.php' }, undefined);
    });

    it('reads a selected file and sends its text', async () => {
        validate.mockResolvedValue({ valid: true, messages: [] });

        const fixture = TestBed.createComponent(ValidatorTool);
        const component = fixture.componentInstance as unknown as { validate(): Promise<void> };

        fixture.detectChanges();

        const panel = fixture.debugElement.query(By.directive(CodeSourcePanel)).componentInstance as unknown as {
            mode: { set(value: 'file'): void };
            files: { set(value: File[]): void };
        };

        panel.mode.set('file');
        panel.files.set([new File(['<?php echo 1;'], 'a.php', { type: 'text/plain' })]);
        await component.validate();

        expect(validate).toHaveBeenCalledWith('php', { code: '<?php echo 1;' }, undefined);
    });

    it('shows the help tab and breadcrumbs', async () => {
        routeData.next({ tool: 'html', tab: 'about' });

        const fixture = TestBed.createComponent(ValidatorTool);

        await fixture.whenStable();
        fixture.detectChanges();

        expect(fixture.nativeElement.textContent).toContain('W3C HTML Validator');
        expect(fixture.nativeElement.querySelector('app-breadcrumbs')?.textContent).toContain('Справка');
    });

    it('shows the PHP version in the PHP help tab', async () => {
        routeData.next({ tool: 'php', tab: 'about' });

        const fixture = TestBed.createComponent(ValidatorTool);

        await fixture.whenStable();
        fixture.detectChanges();

        expect(phpInfo).toHaveBeenCalled();
        expect(fixture.nativeElement.textContent).toContain('PHP');
        expect(fixture.nativeElement.textContent).toContain('8.5');
    });

    it('navigates when a tab is selected', async () => {
        const fixture = TestBed.createComponent(ValidatorTool);
        const component = fixture.componentInstance as unknown as { selectTab(index: number): void };

        fixture.detectChanges();
        component.selectTab(1);

        expect(TestBed.inject(Router).navigateByUrl).toHaveBeenCalledWith('/php_validator/about');
    });
});
