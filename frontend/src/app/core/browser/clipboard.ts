/**
 * Copies text using the Clipboard API when it is available. Plain HTTP
 * origins (for example the local domain used during development) do not
 * expose `navigator.clipboard`, so the legacy textarea fallback is kept.
 */
export async function copyText(text: string): Promise<boolean> {
    if (navigator.clipboard?.writeText) {
        try {
            await navigator.clipboard.writeText(text);

            return true;
        } catch {
            // Fall through to the legacy path.
        }
    }

    const textarea = document.createElement('textarea');

    textarea.value = text;
    textarea.setAttribute('readonly', '');
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();

    let copied = false;

    try {
        copied = document.execCommand('copy');
    } catch {
        copied = false;
    } finally {
        textarea.remove();
    }

    return copied;
}
