import { TestBed } from '@angular/core/testing';
import { ActivatedRoute, Router, provideRouter } from '@angular/router';
import { provideTaiga } from '@taiga-ui/core';
import { BehaviorSubject } from 'rxjs';

import { DEFAULT_HASH_SELECTION, HASH_FILE_LIMIT } from './hash-codec';
import { HashApi } from '../../core/api/hash-api';
import { HashTool } from './hash';

describe('HashTool', () => {
    const hashUrl = vi.fn();
    let routeData: BehaviorSubject<Record<string, unknown>>;

    beforeEach(async () => {
        hashUrl.mockReset();
        routeData = new BehaviorSubject<Record<string, unknown>>({ tab: 'tool' });

        await TestBed.configureTestingModule({
            imports: [HashTool],
            providers: [
                provideRouter([]),
                provideTaiga(),
                { provide: HashApi, useValue: { hashUrl } },
                { provide: ActivatedRoute, useValue: { data: routeData } },
            ],
        }).compileComponents();

        vi.spyOn(TestBed.inject(Router), 'navigateByUrl').mockResolvedValue(true);
    });

    it('hashes typed text with the default algorithms', async () => {
        const fixture = TestBed.createComponent(HashTool);
        const component = fixture.componentInstance as unknown as { setText(value: string): Promise<void> };

        fixture.detectChanges();
        await component.setText('abc');
        fixture.detectChanges();

        const text = fixture.nativeElement.textContent as string;

        expect(text).toContain('MD5');
        expect(text).toContain('900150983cd24fb0d6963f7d28e17f72');
        expect(text).toContain('ba7816bf8f01cfea414140de5dae2223b00361a396177a9cb410ff61f20015ad');
        expect(text).not.toContain('352441c2');
        expect(text).not.toContain('23097d223405d8228642a477bda255b32aadbce4bda0b3f7e36c9da7');
    });

    it('recomputes when an algorithm is toggled', async () => {
        const fixture = TestBed.createComponent(HashTool);
        const component = fixture.componentInstance as unknown as {
            setText(value: string): Promise<void>;
            toggleAlgorithm(id: string): void;
        };

        fixture.detectChanges();
        await component.setText('abc');
        component.toggleAlgorithm('sha224');
        fixture.detectChanges();

        expect(fixture.nativeElement.textContent).toContain('23097d223405d8228642a477bda255b32aadbce4bda0b3f7e36c9da7');

        component.toggleAlgorithm('md5');
        fixture.detectChanges();

        expect(fixture.nativeElement.textContent).not.toContain('900150983cd24fb0d6963f7d28e17f72');
    });

    it('hashes the selected file', async () => {
        const fixture = TestBed.createComponent(HashTool);
        const component = fixture.componentInstance as unknown as {
            useFile(): void;
            setFiles(files: File[]): Promise<void>;
        };

        fixture.detectChanges();
        component.useFile();
        await component.setFiles([new File(['abc'], 'abc.txt')]);
        fixture.detectChanges();

        expect(fixture.nativeElement.textContent).toContain('900150983cd24fb0d6963f7d28e17f72');
    });

    it('hashes a remote file by url with the selected algorithms', async () => {
        hashUrl.mockResolvedValue({
            hashes: {
                md5: '900150983cd24fb0d6963f7d28e17f72',
                sha1: 'a9993e364706816aba3e25717850c26c9cd0d89d',
                sha256: 'ba7816bf8f01cfea414140de5dae2223b00361a396177a9cb410ff61f20015ad',
                sha512: 'sha512-value',
                crc32: '352441c2',
            },
        });

        const fixture = TestBed.createComponent(HashTool);
        const component = fixture.componentInstance as unknown as {
            useFile(): void;
            setUrl(url: string): void;
            hashUrl(): Promise<void>;
        };

        fixture.detectChanges();
        component.useFile();
        component.setUrl('https://example.com/abc.txt');
        fixture.detectChanges();

        expect(fixture.nativeElement.textContent).toContain('Посчитать хэш');

        await component.hashUrl();
        fixture.detectChanges();

        expect(hashUrl).toHaveBeenCalledWith('https://example.com/abc.txt', DEFAULT_HASH_SELECTION);
        expect(fixture.nativeElement.textContent).toContain('900150983cd24fb0d6963f7d28e17f72');
    });

    it('shows the api error for a blocked url', async () => {
        hashUrl.mockRejectedValue(new Error('blocked'));

        const fixture = TestBed.createComponent(HashTool);
        const component = fixture.componentInstance as unknown as {
            useFile(): void;
            setUrl(url: string): void;
            hashUrl(): Promise<void>;
        };

        fixture.detectChanges();
        component.useFile();
        component.setUrl('http://127.0.0.1/secret.txt');
        await component.hashUrl();
        fixture.detectChanges();

        expect(fixture.nativeElement.textContent).toContain('blocked');
    });

    it('reports files that do not fit into memory', async () => {
        const fixture = TestBed.createComponent(HashTool);
        const component = fixture.componentInstance as unknown as {
            useFile(): void;
            setFiles(files: File[]): Promise<void>;
        };

        fixture.detectChanges();
        component.useFile();
        await component.setFiles([{ size: HASH_FILE_LIMIT + 1 } as File]);
        fixture.detectChanges();

        expect(fixture.nativeElement.textContent).toContain('Файл больше 256 МБ');
    });

    it('renders the about tab from the route', () => {
        const fixture = TestBed.createComponent(HashTool);

        fixture.detectChanges();
        routeData.next({ tab: 'about' });
        fixture.detectChanges();

        const text = fixture.nativeElement.textContent as string;

        expect(text).toContain('Что такое хэш');
        expect(text).toContain('Какой алгоритм выбрать');
        expect(text).toContain('Чего хэш не делает');
        expect(text).toContain('О хэшах');
        expect(fixture.nativeElement.querySelector('app-breadcrumbs')?.textContent).toContain('Хэши');
    });

    it('clears the result when the text is emptied', async () => {
        const fixture = TestBed.createComponent(HashTool);
        const component = fixture.componentInstance as unknown as { setText(value: string): Promise<void> };

        fixture.detectChanges();
        await component.setText('abc');
        await component.setText('');
        fixture.detectChanges();

        expect(fixture.nativeElement.textContent).not.toContain('900150983cd24fb0d6963f7d28e17f72');
    });
});
