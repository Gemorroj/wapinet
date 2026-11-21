import { HttpErrorResponse } from '@angular/common/http';
import { TestBed } from '@angular/core/testing';
import { ActivatedRoute, Router, convertToParamMap, provideRouter } from '@angular/router';
import { of } from 'rxjs';
import { provideTaiga } from '@taiga-ui/core';

import { ArchiveResponse } from '../../api/generated/models';
import { ArchiverApi } from '../../core/api/archiver-api';
import { Archiver } from './archiver';

describe('Archiver', () => {
    const response = {
        archive: 'archive0123456789abcdef',
        items: [{ path: 'hello.txt', size: 5, isDirectory: false, isEncrypted: false }],
        size: 5,
        format: 'zip',
        sourceSize: 2048,
    } as ArchiveResponse;
    const create = vi.fn().mockResolvedValue(response);
    const extract = vi.fn().mockResolvedValue(response);
    const show = vi.fn().mockResolvedValue(response);
    const add = vi.fn().mockResolvedValue(response);
    const remove = vi.fn().mockResolvedValue({ ...response, items: [], size: 0 });
    const removeArchive = vi.fn().mockResolvedValue(undefined);
    const download = vi.fn().mockResolvedValue(new Blob(['zip']));
    const downloadFile = vi.fn().mockResolvedValue(new Blob(['hello']));
    const api = { create, extract, show, add, remove, removeArchive, download, downloadFile };

    function configure(data: Record<string, unknown> = {}, params: Record<string, string> = {}): Promise<void> {
        return TestBed.configureTestingModule({
            imports: [Archiver],
            providers: [
                provideRouter([]),
                provideTaiga(),
                { provide: ArchiverApi, useValue: api },
                {
                    provide: ActivatedRoute,
                    useValue: { snapshot: { data, paramMap: convertToParamMap(params) }, paramMap: of(convertToParamMap(params)) },
                },
            ],
        }).compileComponents();
    }

    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('renders the mode chooser', async () => {
        await configure();

        const fixture = TestBed.createComponent(Archiver);
        fixture.detectChanges();

        const text = fixture.nativeElement.textContent as string;

        expect(text).toContain('Создать архив');
        expect(text).toContain('Распаковать архив');
        expect(show).not.toHaveBeenCalled();
    });

    it('creates an archive and navigates to it', async () => {
        await configure({ mode: 'create' });

        const router = TestBed.inject(Router);
        const navigate = vi.spyOn(router, 'navigate').mockResolvedValue(true);
        const fixture = TestBed.createComponent(Archiver);
        const component = fixture.componentInstance as unknown as {
            sourceFiles: { set(value: File[]): void };
            submit(): Promise<void>;
        };

        fixture.detectChanges();

        component.sourceFiles.set([new File(['hello'], 'hello.txt')]);
        await component.submit();

        expect(create).toHaveBeenCalledWith(expect.objectContaining({ files: [expect.any(File)], url: null }));
        expect(navigate).toHaveBeenCalledWith(['/archiver/edit', response.archive], { replaceUrl: true });
    });

    it('loads an existing archive and deletes an entry', async () => {
        await configure({}, { archive: response.archive });

        const fixture = TestBed.createComponent(Archiver);
        const component = fixture.componentInstance as unknown as {
            askDelete(path: string): void;
            confirmDelete(path: string): Promise<void>;
        };

        fixture.detectChanges();
        await fixture.whenStable();
        fixture.detectChanges();

        expect(show).toHaveBeenCalledWith(response.archive);
        expect(fixture.nativeElement.textContent).toContain('hello.txt');
        expect(fixture.nativeElement.textContent).toContain('Формат: ZIP');

        component.askDelete('hello.txt');
        fixture.detectChanges();
        expect(fixture.nativeElement.textContent).toContain('Удалить «hello.txt»?');

        await component.confirmDelete('hello.txt');

        expect(remove).toHaveBeenCalledWith(response.archive, 'hello.txt');
    });

    it('deletes the whole archive and returns to the tool', async () => {
        await configure({}, { archive: response.archive });

        const router = TestBed.inject(Router);
        const navigate = vi.spyOn(router, 'navigate').mockResolvedValue(true);
        const fixture = TestBed.createComponent(Archiver);
        const component = fixture.componentInstance as unknown as {
            deleteArchive(): Promise<void>;
        };

        fixture.detectChanges();
        await fixture.whenStable();

        await component.deleteArchive();

        expect(removeArchive).toHaveBeenCalledWith(response.archive);
        expect(navigate).toHaveBeenCalledWith(['/archiver']);
    });

    it('shows a load error', async () => {
        await configure({}, { archive: 'archiveffffffffffffffff' });
        show.mockRejectedValueOnce(new HttpErrorResponse({ status: 404 }));

        const fixture = TestBed.createComponent(Archiver);

        fixture.detectChanges();
        await fixture.whenStable();
        fixture.detectChanges();

        expect(fixture.nativeElement.textContent).toContain('Архив не найден');
    });
});
