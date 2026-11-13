import { BrowserGeoInfo } from '../api/generated/models';

export function countryFlagUrl(country: string): string {
    return `/resources/taiga-ui/icons/flags/${country.toLowerCase()}.svg`;
}

export function geoCountryLabel(geo?: BrowserGeoInfo | null): string | null {
    if (!geo) {
        return null;
    }

    if (geo.countryCode) {
        return geo.country ? `${geo.country} (${geo.countryCode})` : geo.countryCode;
    }

    return geo.country ?? null;
}
