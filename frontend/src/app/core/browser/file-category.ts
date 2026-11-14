const CATEGORY_LABELS: Record<string, string> = {
    image: 'Изображение',
    video: 'Видео',
    audio: 'Аудио',
    text: 'Текст',
    office: 'Офис',
    archive: 'Архив',
    android: 'Android',
    java: 'Java',
    windows: 'Windows',
    playlist: 'Плейлист',
    torrent: 'Торрент',
    other: 'Другое',
};

export function fileCategoryLabel(category: string): string {
    return CATEGORY_LABELS[category] ?? category;
}
