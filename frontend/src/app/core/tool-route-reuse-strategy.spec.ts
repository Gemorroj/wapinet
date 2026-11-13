import { ActivatedRouteSnapshot } from '@angular/router';

import { ToolRouteReuseStrategy } from './tool-route-reuse-strategy';

describe('ToolRouteReuseStrategy', () => {
    const strategy = new ToolRouteReuseStrategy();
    const route = (reuse?: string, config: object = {}): ActivatedRouteSnapshot =>
        ({ data: undefined === reuse ? {} : { reuse }, routeConfig: config }) as ActivatedRouteSnapshot;

    it('reuses the same route configuration', () => {
        const config = { path: 'hash' };

        expect(strategy.shouldReuseRoute(route(undefined, config), route(undefined, config))).toBe(true);
    });

    it('reuses different tab routes with the same key', () => {
        expect(strategy.shouldReuseRoute(route('hash'), route('hash'))).toBe(true);
    });

    it('does not reuse tabs of different tools', () => {
        expect(strategy.shouldReuseRoute(route('hash'), route('validator-php'))).toBe(false);
    });

    it('does not reuse routes without a key', () => {
        expect(strategy.shouldReuseRoute(route(), route())).toBe(false);
    });

    it('does not detach or attach components', () => {
        expect(strategy.shouldDetach()).toBe(false);
        expect(strategy.shouldAttach()).toBe(false);
        expect(strategy.retrieve()).toBeNull();
    });
});
