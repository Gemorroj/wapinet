import { Component, ElementRef, Input, OnDestroy, OnInit, effect, signal, viewChild } from '@angular/core';
import { TuiCardLarge } from '@taiga-ui/layout';

const VK_API_ID = 7855495;
const VK_SCRIPT_URL = 'https://vk.ru/js/api/openapi.js?169';

interface VkApi {
    _apiId?: number;
    Widgets: {
        Comments(elementId: string, options: { limit?: number }, pageId?: string): void;
    };
    init(config: { apiId: number; onlyWidgets: boolean }): void;
}

declare global {
    interface Window {
        VK?: VkApi;
    }
}

let loading: Promise<VkApi | null> | null = null;
let widgets = 0;

function loadVk(): Promise<VkApi | null> {
    if (window.VK) {
        return Promise.resolve(window.VK);
    }

    // The API script is not needed until comments are actually displayed.
    loading ??= new Promise<VkApi | null>((resolve) => {
        const script = document.createElement('script');

        script.async = true;
        script.src = VK_SCRIPT_URL;
        script.addEventListener('load', () => resolve(window.VK ?? null));
        script.addEventListener('error', () => resolve(null));
        document.head.append(script);
    });

    return loading;
}

/**
 * VK comments widget. The widget is bound to a page identifier, so the SPA and
 * the legacy pages share the same comment threads.
 *
 * Nothing is rendered when the VK API is unavailable (blocked or offline).
 */
@Component({
    imports: [TuiCardLarge],
    selector: 'app-vk-comments',
    styleUrl: './vk-comments.css',
    templateUrl: './vk-comments.html',
})
export class VkComments implements OnInit, OnDestroy {
    /** Widget page identifier, for example `file-42`. */
    @Input({ required: true }) xid = '';

    protected readonly ready = signal(false);

    private readonly container = viewChild<ElementRef<HTMLElement>>('container');
    private vk: VkApi | null = null;
    private rendered = false;
    private destroyed = false;

    constructor() {
        effect(() => {
            if (!this.ready()) {
                return;
            }

            const container = this.container()?.nativeElement;

            if (!container || this.rendered || null === this.vk) {
                return;
            }

            this.rendered = true;
            // A fresh id on every mount, otherwise VK reuses the previous iframe after navigation.
            container.id = `vk-comments-${++widgets}`;
            this.vk.Widgets.Comments(container.id, { limit: 10 }, this.xid);
        });
    }

    async ngOnInit(): Promise<void> {
        const vk = await loadVk();

        if (null === vk || this.destroyed) {
            return;
        }

        if (!vk._apiId) {
            vk.init({ apiId: VK_API_ID, onlyWidgets: true });
        }

        this.vk = vk;
        this.ready.set(true);
    }

    ngOnDestroy(): void {
        this.destroyed = true;
        this.container()?.nativeElement.replaceChildren();
    }
}
