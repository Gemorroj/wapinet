import { TestBed } from '@angular/core/testing';
import { provideTaiga } from '@taiga-ui/core';

import { FileSource } from './file-source';

function dragEvent(type: string, files: File[]): DragEvent {
    const event = new Event(type, { bubbles: true, cancelable: true }) as unknown as DragEvent;

    Object.defineProperty(event, 'dataTransfer', {
        value: { files, types: ['Files'], dropEffect: 'none' },
    });

    return event;
}

describe('FileSource', () => {
    beforeEach(async () => {
        await TestBed.configureTestingModule({
            imports: [FileSource],
            providers: [provideTaiga()],
        }).compileComponents();
    });

    it('switches between file and url sources', () => {
        const fixture = TestBed.createComponent(FileSource);

        fixture.detectChanges();

        const buttons = fixture.nativeElement.querySelectorAll('button');

        expect(fixture.nativeElement.querySelector('input[type="file"]')).not.toBeNull();

        buttons[1].click();
        fixture.detectChanges();

        expect(fixture.nativeElement.querySelector('input[type="file"]')).toBeNull();
        expect(fixture.nativeElement.querySelector('input[type="url"]')).not.toBeNull();
    });

    it('shows the selected file name and size', () => {
        const fixture = TestBed.createComponent(FileSource);

        fixture.componentRef.setInput('files', [new File(['abc'], 'demo.txt')]);
        fixture.detectChanges();

        const text = fixture.nativeElement.textContent as string;

        expect(text).toContain('demo.txt');
        expect(text).toContain('3 Б');
    });

    it('toggles multiple selection with the input flag', () => {
        const fixture = TestBed.createComponent(FileSource);

        fixture.componentRef.setInput('multiple', true);
        fixture.componentRef.setInput('files', [new File(['a'], 'one.txt'), new File(['b'], 'two.txt')]);
        fixture.detectChanges();

        const input = fixture.nativeElement.querySelector('input[type="file"]') as HTMLInputElement;
        const text = fixture.nativeElement.textContent as string;

        expect(input.multiple).toBe(true);
        expect(text).toContain('one.txt');
        expect(text).toContain('two.txt');
    });

    it('blocks the controls while loading', () => {
        const fixture = TestBed.createComponent(FileSource);

        fixture.componentRef.setInput('loading', true);
        fixture.detectChanges();

        const input = fixture.nativeElement.querySelector('input[type="file"]') as HTMLInputElement;

        expect(input.disabled).toBe(true);
        expect(fixture.nativeElement.textContent).toContain('Загружаем…');
    });

    it('emits the entered url', () => {
        const fixture = TestBed.createComponent(FileSource);
        const urls: string[] = [];

        fixture.componentInstance.urlChange.subscribe((value) => urls.push(value));
        fixture.detectChanges();

        fixture.nativeElement.querySelectorAll('button')[1].click();
        fixture.detectChanges();

        const input = fixture.nativeElement.querySelector('input[type="url"]') as HTMLInputElement;

        input.value = 'https://example.com/file.zip';
        input.dispatchEvent(new Event('input'));
        fixture.detectChanges();

        expect(urls).toContain('https://example.com/file.zip');
    });

    it('accepts a dropped file in single mode', () => {
        const fixture = TestBed.createComponent(FileSource);
        const files: File[][] = [];

        fixture.componentInstance.filesChange.subscribe((value) => files.push(value));
        fixture.detectChanges();

        const zone = fixture.nativeElement.querySelector('.file-source__drop') as HTMLElement;
        const first = new File(['a'], 'one.zip');
        const second = new File(['b'], 'two.zip');

        zone.dispatchEvent(dragEvent('drop', [first, second]));

        expect(files).toEqual([[first]]);
    });

    it('accepts multiple dropped files in multiple mode', () => {
        const fixture = TestBed.createComponent(FileSource);
        const files: File[][] = [];

        fixture.componentRef.setInput('multiple', true);
        fixture.componentInstance.filesChange.subscribe((value) => files.push(value));
        fixture.detectChanges();

        const zone = fixture.nativeElement.querySelector('.file-source__drop') as HTMLElement;
        const first = new File(['a'], 'one.zip');
        const second = new File(['b'], 'two.zip');

        zone.dispatchEvent(dragEvent('drop', [first, second]));

        expect(files).toEqual([[first, second]]);
    });

    it('highlights the drop zone while dragging', () => {
        const fixture = TestBed.createComponent(FileSource);

        fixture.detectChanges();

        const zone = fixture.nativeElement.querySelector('.file-source__drop') as HTMLElement;

        zone.dispatchEvent(dragEvent('dragenter', []));
        fixture.detectChanges();

        expect(zone.className).toContain('file-source__drop_dragging');

        zone.dispatchEvent(dragEvent('dragleave', []));
        fixture.detectChanges();

        expect(zone.className).not.toContain('file-source__drop_dragging');
    });

    it('ignores a drop while loading', () => {
        const fixture = TestBed.createComponent(FileSource);
        const files: File[][] = [];

        fixture.componentRef.setInput('loading', true);
        fixture.componentInstance.filesChange.subscribe((value) => files.push(value));
        fixture.detectChanges();

        const zone = fixture.nativeElement.querySelector('.file-source__drop') as HTMLElement;

        zone.dispatchEvent(dragEvent('drop', [new File(['a'], 'one.zip')]));

        expect(files).toEqual([]);
    });
});
