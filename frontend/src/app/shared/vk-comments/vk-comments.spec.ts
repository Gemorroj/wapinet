import { TestBed } from '@angular/core/testing';

import { VkComments } from './vk-comments';

describe('VkComments', () => {
    afterEach(() => {
        delete window.VK;
    });

    it('initializes VK once and renders the widget for the page identifier', async () => {
        const init = vi.fn();
        const comments = vi.fn();

        window.VK = { init, Widgets: { Comments: comments } };

        const fixture = TestBed.createComponent(VkComments);

        fixture.componentRef.setInput('xid', 'file-42');
        await fixture.whenStable();
        fixture.detectChanges();

        expect(init).toHaveBeenCalledWith({ apiId: 7855495, onlyWidgets: true });
        expect(comments).toHaveBeenCalledWith(expect.any(String), { limit: 10 }, 'file-42');
        expect(fixture.nativeElement.querySelector('.vk-comments').id).toMatch(/^vk-comments-\d+$/);

        fixture.destroy();
    });

    it('does not reinitialize VK when the API is already initialized', async () => {
        const init = vi.fn();
        const comments = vi.fn();

        window.VK = { _apiId: 7855495, init, Widgets: { Comments: comments } };

        const fixture = TestBed.createComponent(VkComments);

        fixture.componentRef.setInput('xid', 'file-7');
        await fixture.whenStable();
        fixture.detectChanges();

        expect(init).not.toHaveBeenCalled();
        expect(comments).toHaveBeenCalledWith(expect.any(String), { limit: 10 }, 'file-7');

        fixture.destroy();
    });
});
