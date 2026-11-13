// jsdom does not implement matchMedia, which Taiga UI uses for the color scheme.
if (!window.matchMedia) {
    window.matchMedia = (query: string): MediaQueryList =>
        ({
            matches: false,
            media: query,
            onchange: null,
            addListener: () => undefined,
            removeListener: () => undefined,
            addEventListener: () => undefined,
            removeEventListener: () => undefined,
            dispatchEvent: () => false,
        }) as MediaQueryList;
}
