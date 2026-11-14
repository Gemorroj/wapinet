import { Injectable } from '@angular/core';
import { ActivatedRouteSnapshot, DetachedRouteHandle, RouteReuseStrategy } from '@angular/router';

/**
 * Keeps a tool component alive while switching between its tabs.
 * Routes opt in with `data.reuse`; the same key must be used by all tabs of one tool.
 */
@Injectable({ providedIn: 'root' })
export class ToolRouteReuseStrategy implements RouteReuseStrategy {
    shouldDetach(): boolean {
        return false;
    }

    store(): void {
        // Reused routes stay attached; detached snapshots are not needed.
    }

    shouldAttach(): boolean {
        return false;
    }

    retrieve(): DetachedRouteHandle | null {
        return null;
    }

    shouldReuseRoute(future: ActivatedRouteSnapshot, current: ActivatedRouteSnapshot): boolean {
        if (future.routeConfig === current.routeConfig) {
            return true;
        }

        const key = future.data['reuse'];

        return 'string' === typeof key && key === current.data['reuse'];
    }
}
