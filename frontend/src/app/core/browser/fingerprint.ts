export interface CanvasFingerprint {
    readonly hash: string;
    readonly image: string;
    readonly size: number;
}

export interface AudioFingerprint {
    readonly hash: string;
    readonly sampleRate: number;
    readonly value: number;
}

export interface WebRtcFingerprint {
    readonly local: readonly string[];
    readonly relay: readonly string[];
    readonly stun: readonly string[];
}

const STUN_URL = 'stun:stun.l.google.com:19302';

export function hashString(value: string): string {
    let h1 = 0xdeadbeef;
    let h2 = 0x41c6ce57;

    for (let index = 0; index < value.length; index++) {
        const code = value.charCodeAt(index);

        h1 = Math.imul(h1 ^ code, 2654435761);
        h2 = Math.imul(h2 ^ code, 1597334677);
    }

    h1 = Math.imul(h1 ^ (h1 >>> 16), 2246822507) ^ Math.imul(h2 ^ (h2 >>> 13), 3266489909);
    h2 = Math.imul(h2 ^ (h2 >>> 16), 2246822507) ^ Math.imul(h1 ^ (h1 >>> 13), 3266489909);

    return (h2 >>> 0).toString(16).padStart(8, '0') + (h1 >>> 0).toString(16).padStart(8, '0');
}

export function canvasFingerprint(): CanvasFingerprint | null {
    try {
        const canvas = document.createElement('canvas');

        canvas.width = 320;
        canvas.height = 60;

        const context = canvas.getContext('2d');

        if (null === context) {
            return null;
        }

        context.textBaseline = 'top';
        context.fillStyle = '#f60';
        context.fillRect(0, 0, canvas.width, canvas.height);

        context.fillStyle = '#069';
        context.font = '14px Arial';
        context.fillText('Wapinet fingerprint 🐾', 4, 8);

        context.fillStyle = 'rgba(102, 204, 0, 0.7)';
        context.font = '16px "Times New Roman"';
        context.fillText('Wapinet fingerprint 🐾', 6, 28);

        const gradient = context.createLinearGradient(0, 0, canvas.width, 0);

        gradient.addColorStop(0, '#00f');
        gradient.addColorStop(1, '#0f0');
        context.fillStyle = gradient;
        context.fillRect(0, 48, canvas.width, 12);

        const image = canvas.toDataURL();

        return {
            hash: hashString(image),
            image,
            size: image.length,
        };
    } catch {
        return null;
    }
}

export async function audioFingerprint(): Promise<AudioFingerprint | null> {
    try {
        if (!window.OfflineAudioContext) {
            return null;
        }

        const context = new OfflineAudioContext(1, 44100, 44100);
        const oscillator = context.createOscillator();
        const compressor = context.createDynamicsCompressor();

        oscillator.type = 'triangle';
        oscillator.frequency.value = 10000;
        compressor.threshold.value = -50;
        compressor.knee.value = 40;
        compressor.ratio.value = 12;
        compressor.attack.value = 0;
        compressor.release.value = 0.25;

        oscillator.connect(compressor);
        compressor.connect(context.destination);
        oscillator.start(0);

        const buffer = await context.startRendering();
        const data = buffer.getChannelData(0);
        let value = 0;

        for (let index = 0; index < data.length; index++) {
            value += Math.abs(data[index]);
        }

        return {
            hash: hashString(value.toString()),
            sampleRate: buffer.sampleRate,
            value,
        };
    } catch {
        return null;
    }
}

export async function webrtcFingerprint(timeout = 3000): Promise<WebRtcFingerprint | null> {
    if ('undefined' === typeof RTCPeerConnection) {
        return null;
    }

    const connection = new RTCPeerConnection({ iceServers: [{ urls: STUN_URL }] });
    const local = new Set<string>();
    const publicAddresses = new Set<string>();
    const relay = new Set<string>();

    try {
        connection.addEventListener('icecandidate', (event) => {
            const candidate = event.candidate;

            if (null === candidate) {
                return;
            }

            const address = (candidate as RTCIceCandidate & { address?: string | null }).address ?? candidate.candidate.split(' ')[4];

            if (!address) {
                return;
            }

            const type = (candidate as RTCIceCandidate & { type?: string }).type ?? candidate.candidate.split(' ')[7];

            if ('host' === type) {
                local.add(address);
            } else if ('srflx' === type || 'prflx' === type) {
                publicAddresses.add(address);
            } else if ('relay' === type) {
                relay.add(address);
            }
        });

        connection.createDataChannel('probe');
        const offer = await connection.createOffer();

        await connection.setLocalDescription(offer);
        await new Promise<void>((resolve) => {
            const timer = setTimeout(resolve, timeout);

            connection.addEventListener('icegatheringstatechange', () => {
                if ('complete' === connection.iceGatheringState) {
                    clearTimeout(timer);
                    resolve();
                }
            });
        });

        return {
            local: [...local],
            relay: [...relay],
            stun: [...publicAddresses],
        };
    } catch {
        return null;
    } finally {
        connection.close();
    }
}
