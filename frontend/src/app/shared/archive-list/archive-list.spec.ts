import { TestBed } from '@angular/core/testing';
import { provideTaiga } from '@taiga-ui/core';

import { ArchiveEntryView, ArchiveList } from './archive-list';

describe('ArchiveList', () => {
    beforeEach(async () => {
        await TestBed.configureTestingModule({
            imports: [ArchiveList],
            providers: [provideTaiga()],
        }).compileComponents();
    });

    it('renders a tree with a summary and emits downloads', () => {
        const fixture = TestBed.createComponent(ArchiveList);
        const entries: ArchiveEntryView[] = [
            { path: 'docs', size: 0, isDirectory: true, isEncrypted: false },
            { path: 'docs/guide.pdf', size: 601, isDirectory: false, isEncrypted: false },
            { path: 'readme.txt', size: 81, isDirectory: false, isEncrypted: false },
        ];
        const downloads: ArchiveEntryView[] = [];

        fixture.componentRef.setInput('entries', entries);
        fixture.componentInstance.entryAction.subscribe((entry) => downloads.push(entry));
        fixture.detectChanges();

        const text = fixture.nativeElement.textContent as string;

        expect(text).toContain('2 файла');
        expect(text).toContain('1 папка');
        expect(text).toContain('682 Б');
        expect(text).toContain('guide.pdf');
        expect(text).toContain('readme.txt');
        expect(fixture.nativeElement.querySelectorAll('.archive-list__row')).toHaveLength(3);

        const buttons = fixture.nativeElement.querySelectorAll('button') as NodeListOf<HTMLButtonElement>;

        expect(buttons).toHaveLength(2);
        buttons[0].click();
        expect(downloads).toHaveLength(1);
        expect(downloads[0].path).toBe('docs/guide.pdf');
    });

    it('can hide downloads and shows an empty state', () => {
        const fixture = TestBed.createComponent(ArchiveList);

        fixture.componentRef.setInput('entries', []);
        fixture.detectChanges();

        expect(fixture.nativeElement.textContent).toContain('Архив пуст.');

        fixture.componentRef.setInput('entries', [{ path: 'file.txt', size: 10, isDirectory: false, isEncrypted: false }]);
        fixture.componentRef.setInput('showAction', false);
        fixture.detectChanges();

        expect(fixture.nativeElement.querySelectorAll('button')).toHaveLength(0);
    });
});
