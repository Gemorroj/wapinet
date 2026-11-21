import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { provideTaiga } from '@taiga-ui/core';

import { FileInformation } from './information';

describe('FileInformation', () => {
    beforeEach(async () => {
        await TestBed.configureTestingModule({
            imports: [FileInformation],
            providers: [provideRouter([]), provideTaiga()],
        }).compileComponents();
    });

    it('renders the user agreement', () => {
        const fixture = TestBed.createComponent(FileInformation);

        fixture.detectChanges();

        const text = fixture.nativeElement.textContent;

        expect(text).toContain('Запрещается');
        expect(text).toContain('Права на информацию');
        expect(text).toContain('Ответственность');
        expect(text).toContain('Хранение файлов');
        expect(text).toContain('Конфиденциальность');
    });

    it('links the abuse email', () => {
        const fixture = TestBed.createComponent(FileInformation);

        fixture.detectChanges();

        expect(fixture.nativeElement.querySelector('a[href="mailto:admin@wapinet.ru"]')).not.toBeNull();
    });
});
