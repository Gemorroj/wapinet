#!/usr/bin/env php
<?php

declare(strict_types=1);

/*
 * Generates small placeholder files for file records imported from the
 * production database. The real uploads are not part of the dump, so lists
 * and detail pages would otherwise show broken links and empty previews.
 *
 * Usage:
 *   php bin/generate-file-placeholders.php [--force] [--limit=N] [--only=image,video]
 *
 * Placeholders are written to public/static/file/{Y/m/d}/{file_name}, the same
 * layout VichUploader uses. Existing files are kept unless --force is passed.
 */

use App\Enum\FileMime;

require \dirname(__DIR__).'/vendor/autoload.php';

$root = \dirname(__DIR__);
$templatesDir = $root.'/var/file-placeholders';
$targetDir = $root.'/public/static/file';

$argv = $_SERVER['argv'] ?? [];
$force = \in_array('--force', $argv, true);
$limit = null;
$only = [];

foreach ($argv as $argument) {
    if (\str_starts_with($argument, '--limit=')) {
        $limit = \max(0, (int) \substr($argument, 8));
    }

    if (\str_starts_with($argument, '--only=')) {
        $only = \array_filter(\explode(',', \substr($argument, 7)));
    }
}

if (!\function_exists('imagecreatetruecolor')) {
    \fwrite(\STDERR, "The GD extension is required.\n");
    exit(1);
}

if (!\class_exists(ZipArchive::class)) {
    \fwrite(\STDERR, "The zip extension is required.\n");
    exit(1);
}

foreach ([$templatesDir, $targetDir] as $directory) {
    if (!\is_dir($directory) && !\mkdir($directory, 0o777, true) && !\is_dir($directory)) {
        \fwrite(\STDERR, "Unable to create $directory\n");
        exit(1);
    }
}

$ffmpeg = \findBinary('ffmpeg');
$sevenZip = \findBinary('7z') ?: (\is_file('C:/Program Files/7-Zip/7z.exe') ? 'C:/Program Files/7-Zip/7z.exe' : null);

$builder = new PlaceholderBuilder($templatesDir, $ffmpeg, $sevenZip);
$templates = $builder->build();

$pdo = \connect($root);

$sql = 'SELECT id, file_name, original_file_name, mime_type, created_at FROM file ORDER BY id';
if (null !== $limit && $limit > 0) {
    $sql .= ' LIMIT '.$limit;
}

$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
$stats = [];
$written = 0;
$skipped = 0;

foreach ($rows as $row) {
    $kind = \detectKind((string) $row['mime_type'], (string) $row['file_name']);

    if ([] !== $only && !\in_array($kind, $only, true)) {
        continue;
    }

    $createdAt = new DateTimeImmutable((string) $row['created_at']);
    $relativePath = $createdAt->format('Y/m/d').'/'.(string) $row['file_name'];
    $destination = $targetDir.'/'.$relativePath;

    if (!$force && \is_file($destination)) {
        ++$skipped;
        continue;
    }

    $directory = \dirname($destination);

    if (!\is_dir($directory) && !\mkdir($directory, 0o777, true) && !\is_dir($directory)) {
        \fwrite(\STDERR, "Unable to create $directory\n");
        continue;
    }

    \writePlaceholder($templates, $destination, $kind, (string) $row['file_name'], (string) $row['original_file_name'], (string) $row['mime_type'], (int) $row['id']);

    $stats[$kind] = ($stats[$kind] ?? 0) + 1;
    ++$written;

    if (0 === $written % 250) {
        echo '.';
    }
}

echo "\n";

foreach ($stats as $kind => $count) {
    echo \str_pad($kind, 12).$count."\n";
}

echo "written: $written, skipped: $skipped\n";

/**
 * @return array<string, list<string>> kind => template candidates
 */
final class PlaceholderBuilder
{
    private const string RAR_SAMPLE = 'UmFyIRoHAQDz4YLrCwEFBwAGAQGAgIAAjKGOdyYCAwu3AASAECCi5rfFgAMACnN0ZXN0MS50eHQKAwKAzCCX5ijMAcSqNEQ0JPoy60f6xE19veYFgIIKjMEDKpXKIptQGpPdQPwAQAIA1GAENu0V+bdUgypkTs18H+CU/AuTNQIDGoAABIAQIAAAAACAAAAKc3Rlc3QyLnR4dAoDAoDMIJfmKMwBDgUFAApzdGVzdDEudHh0RRTsnTUCAxqAAASAECAAAAAAgAAACnN0ZXN0My50eHQKAwKAzCCX5ijMAQ4FBQAKc3Rlc3QxLnR4dHKOW7U1AgMagAAEgBAgAAAAAIAAAApzdGVzdDQudHh0CgMCgMwgl+YozAEOBQUACnN0ZXN0MS50eHSjZry7NQIDGoAABIAQIAAAAACAAAAKc3Rlc3Q1LnR4dAoDAoDMIJfmKMwBDgUFAApzdGVzdDEudHh00F+UqDUCAxqAAASAECAAAAAAgAAACnN0ZXN0Ni50eHQKAwKAzCCX5ijMAQ4FBQAKc3Rlc3QxLnR4dAG3c6Y1AgMagAAEgBAgAAAAAIAAAApzdGVzdDcudHh0CgMCgMwgl+YozAEOBQUACnN0ZXN0MS50eHS+a/v5NQIDGoAABIAQIAAAAACAAAAKc3Rlc3Q4LnR4dAoDAoDMIJfmKMwBDgUFAApzdGVzdDEudHh0b4Mc9zUCAxqAAASAECAAAAAAgAAACnN0ZXN0OS50eHQKAwKAzCCX5ijMAQ4FBQAKc3Rlc3QxLnR4dB13VlEDBQQA';

    /** @var array<string, list<string>> */
    private array $templates = [];

    public function __construct(
        private readonly string $directory,
        private readonly ?string $ffmpeg,
        private readonly ?string $sevenZip,
    ) {
    }

    /**
     * @return array<string, list<string>>
     */
    public function build(): array
    {
        $this->buildImages();
        $this->buildPsd();
        $this->buildAi();
        $this->buildVideo();
        $this->buildAudio();
        $this->buildPreviews();
        $this->buildDocuments();
        $this->buildArchives();
        $this->buildOther();

        return $this->templates;
    }

    private function add(string $kind, string $path): void
    {
        $this->templates[$kind][] = $path;
    }

    private function buildImages(): void
    {
        $variants = [
            [640, 360, [32, 86, 140]],
            [360, 640, [120, 52, 130]],
            [480, 480, [34, 120, 80]],
            [800, 450, [150, 90, 30]],
            [450, 800, [40, 60, 120]],
            [320, 240, [120, 40, 40]],
        ];

        foreach (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'avif'] as $extension) {
            foreach ($variants as $index => [$width, $height, $color]) {
                $path = $this->directory.'/image-'.$extension.'-'.$index.'.'.$extension;

                if (!\is_file($path)) {
                    $this->image($path, $extension, $width, $height, $color, $index);
                }

                $this->add('image', $path);
                $this->add('image-'.$extension, $path);
            }
        }
    }

    private function image(string $path, string $extension, int $width, int $height, array $color, int $index): void
    {
        $image = \imagecreatetruecolor($width, $height);
        $background = \imagecolorallocate($image, ...$color);
        \imagefilledrectangle($image, 0, 0, $width, $height, $background);

        $accent = \imagecolorallocate($image, (int) \min(255, $color[0] + 50), (int) \min(255, $color[1] + 50), (int) \min(255, $color[2] + 50));

        for ($x = 0; $x < $width; $x += 40) {
            \imageline($image, $x, 0, $x + 20, $height, $accent);
        }

        $white = \imagecolorallocate($image, 255, 255, 255);
        $label = 'PLACEHOLDER '.\strtoupper($extension).' #'.$index;
        $font = 5;
        $textWidth = \imagefontwidth($font) * \strlen($label);
        \imagestring($image, $font, \max(8, (int) (($width - $textWidth) / 2)), \max(8, (int) ($height / 2) - 10), $label, $white);
        \imagestring($image, 3, 12, $height - 24, $width.'x'.$height.' wapinet.ru', $white);

        match ($extension) {
            'jpg', 'jpeg' => \imagejpeg($image, $path, 85),
            'png' => \imagepng($image, $path, 7),
            'gif' => \imagegif($image, $path),
            'bmp' => \imagebmp($image, $path),
            'webp' => \imagewebp($image, $path, 80),
            'avif' => \imageavif($image, $path, 70),
            default => \imagepng($image, $path),
        };
    }

    private function buildPsd(): void
    {
        $path = $this->directory.'/image.psd';

        if (!\is_file($path)) {
            $width = 320;
            $height = 180;
            $header = '8BPS'.\pack('n', 1).\str_repeat("\0", 6).\pack('n', 3).\pack('N', $height).\pack('N', $width).\pack('n', 8).\pack('n', 3);
            $sections = \pack('N', 0).\pack('N', 0).\pack('N', 0).\pack('n', 0);
            $r = $g = $b = '';

            for ($y = 0; $y < $height; ++$y) {
                for ($x = 0; $x < $width; ++$x) {
                    $r .= \chr((int) (($x / $width) * 255));
                    $g .= \chr((int) (($y / $height) * 255));
                    $b .= \chr(120);
                }
            }

            \file_put_contents($path, $header.$sections.$r.$g.$b);
        }

        $this->add('psd', $path);
    }

    private function buildAi(): void
    {
        $path = $this->directory.'/image.ai';

        if (!\is_file($path)) {
            \file_put_contents($path, <<<'PS'
                %!PS-Adobe-3.0
                %%BoundingBox: 0 0 640 360
                %%Title: wapinet.ru placeholder
                /Helvetica findfont 24 scalefont setfont
                72 300 moveto (Placeholder AI) show
                72 260 moveto (wapinet.ru) show
                showpage
                %%EOF
                PS);
        }

        $this->add('ai', $path);
    }

    private function buildVideo(): void
    {
        if (null === $this->ffmpeg) {
            return;
        }

        $common = ['-f', 'lavfi', '-i', 'testsrc=size=320x180:rate=15', '-f', 'lavfi', '-i', 'sine=frequency=440:sample_rate=44100', '-t', '2'];

        $this->ffmpegTemplate('video.mp4', \array_merge($common, ['-c:v', 'libx264', '-pix_fmt', 'yuv420p', '-c:a', 'aac']));
        $this->ffmpegTemplate('video.3gp', \array_merge($common, ['-c:v', 'h263', '-s', '176x144', '-c:a', 'aac', '-b:a', '32k']));
        $this->ffmpegTemplate('video.avi', \array_merge($common, ['-c:v', 'mpeg4', '-c:a', 'libmp3lame']));
        $this->ffmpegTemplate('video.wmv', \array_merge($common, ['-c:v', 'wmv2', '-c:a', 'wmav2']));
        $this->ffmpegTemplate('video.mov', \array_merge($common, ['-c:v', 'libx264', '-c:a', 'aac']));
        $this->ffmpegTemplate('video.flv', \array_merge($common, ['-c:v', 'flv', '-c:a', 'libmp3lame']));
        $this->ffmpegTemplate('video.webm', \array_merge($common, ['-c:v', 'libvpx-vp9', '-c:a', 'libopus']));
        $this->ffmpegTemplate('video.ogv', \array_merge($common, ['-c:v', 'libtheora', '-c:a', 'libvorbis']));

        if (\is_file($this->directory.'/video.wmv')) {
            \copy($this->directory.'/video.wmv', $this->directory.'/video.asf');
            \copy($this->directory.'/video.wmv', $this->directory.'/video.wm');
        }

        if (\is_file($this->directory.'/video.mp4')) {
            \copy($this->directory.'/video.mp4', $this->directory.'/video.m4v');
        }

        foreach (['mp4', '3gp', '3gpp', 'avi', 'wmv', 'asf', 'mov', 'flv', 'webm', 'ogv', 'm4v'] as $extension) {
            $path = $this->directory.'/video.'.$extension;

            if (\is_file($path)) {
                $this->add('video', $path);
                $this->add('video-'.$extension, $path);
            }
        }
    }

    private function ffmpegTemplate(string $name, array $arguments): void
    {
        $path = $this->directory.'/'.$name;

        if (\is_file($path)) {
            return;
        }

        $command = \escapeshellarg($this->ffmpeg).' -y -hide_banner -loglevel error '.\implode(' ', \array_map('escapeshellarg', $arguments)).' '.\escapeshellarg($path);
        \exec($command, $output, $code);

        if (0 !== $code && \is_file($path)) {
            @\unlink($path);
        }
    }

    private function buildAudio(): void
    {
        if (null === $this->ffmpeg) {
            return;
        }

        $common = ['-f', 'lavfi', '-i', 'sine=frequency=440:sample_rate=44100', '-t', '2'];

        $this->ffmpegTemplate('audio.mp3', \array_merge($common, ['-c:a', 'libmp3lame', '-b:a', '64k']));
        $this->ffmpegTemplate('audio.wav', \array_merge($common, ['-c:a', 'pcm_s16le']));
        $this->ffmpegTemplate('audio.m4a', \array_merge($common, ['-c:a', 'aac']));
        $this->ffmpegTemplate('audio.ogg', \array_merge($common, ['-c:a', 'libvorbis']));
        $this->ffmpegTemplate('audio.mp4', \array_merge($common, ['-c:a', 'aac']));
        $this->ffmpegTemplate('audio.flac', \array_merge($common, ['-c:a', 'flac']));

        foreach (['mp3', 'wav', 'm4a', 'ogg', 'mp4', 'flac'] as $extension) {
            $path = $this->directory.'/audio.'.$extension;

            if (\is_file($path)) {
                $this->add('audio', $path);
                $this->add('audio-'.$extension, $path);
            }
        }

        $midi = 'C:/Windows/Media/flourish.mid';
        $midiPath = $this->directory.'/audio.mid';

        if (!\is_file($midiPath)) {
            if (\is_file($midi)) {
                \copy($midi, $midiPath);
            } else {
                \file_put_contents($midiPath, "MThd\0\0\0\x06\0\0\0\x01\0\x60MTrk\0\0\0\x04\0\xff/\0");
            }
        }

        $this->add('audio', $midiPath);
        $this->add('audio-mid', $midiPath);
    }

    private function buildPreviews(): void
    {
        $videoColors = [[68, 147, 248], [63, 185, 80], [210, 153, 34], [163, 113, 247]];

        foreach ($videoColors as $index => $accentColor) {
            $video = $this->directory.'/preview-video-'.$index.'.jpg';

            if (!\is_file($video)) {
                $image = \imagecreatetruecolor(640, 360);
                $background = \imagecolorallocate($image, 20, 28, 38);
                \imagefilledrectangle($image, 0, 0, 640, 360, $background);
                $white = \imagecolorallocate($image, 240, 246, 252);
                $accent = \imagecolorallocate($image, ...$accentColor);
                \imagefilledpolygon($image, [280, 130, 280, 230, 370, 180], $accent);
                \imagestring($image, 5, 250, 260, 'VIDEO PREVIEW', $white);
                \imagejpeg($image, $video, 85);
            }

            $this->add('preview-video', $video);
        }

        $audioColors = [[63, 185, 80], [68, 147, 248], [210, 153, 34], [219, 109, 40]];

        foreach ($audioColors as $index => $accentColor) {
            $audio = $this->directory.'/preview-audio-'.$index.'.jpg';

            if (!\is_file($audio)) {
                $image = \imagecreatetruecolor(400, 400);
                $background = \imagecolorallocate($image, 38, 44, 54);
                \imagefilledrectangle($image, 0, 0, 400, 400, $background);
                $white = \imagecolorallocate($image, 240, 246, 252);
                $accent = \imagecolorallocate($image, ...$accentColor);
                \imagefilledellipse($image, 200, 200, 220, 220, $accent);
                \imagefilledellipse($image, 200, 200, 70, 70, $background);
                \imagestring($image, 5, 148, 340, 'AUDIO', $white);
                \imagejpeg($image, $audio, 85);
            }

            $this->add('preview-audio', $audio);
        }

        $app = $this->directory.'/preview-app.png';

        if (!\is_file($app)) {
            $image = \imagecreatetruecolor(192, 192);
            $background = \imagecolorallocate($image, 33, 40, 48);
            \imagefilledrectangle($image, 0, 0, 192, 192, $background);
            $accent = \imagecolorallocate($image, 68, 147, 248);
            \imagefilledrectangle($image, 32, 32, 160, 160, $accent);
            $white = \imagecolorallocate($image, 240, 246, 252);
            \imagestring($image, 5, 74, 88, 'APP', $white);
            \imagepng($image, $app);
        }

        $this->add('preview-app', $app);
    }

    private function buildArchives(): void
    {
        $source = $this->directory.'/archive-src';
        $this->resetDirectory($source);

        $directories = ['docs', 'docs/notes', 'images', 'data', 'src'];

        foreach ($directories as $directory) {
            if (!\is_dir($source.'/'.$directory)) {
                \mkdir($source.'/'.$directory, 0o777, true);
            }
        }

        $files = [
            'readme.txt' => "wapinet.ru placeholder archive\n\nThis archive is generated for local development.\n",
            'docs/changelog.txt' => "1.0.0\n- Initial placeholder release\n",
            'docs/license.txt' => "Placeholder license text.\n",
            'docs/notes/todo.txt' => "Check file previews\nCheck archive listing\nCheck downloads\n",
            'data/data.csv' => "id,name,size\n1,cover.jpg,18467\n2,preview.png,2604\n",
            'data/config.json' => "{\n    \"name\": \"wapinet-placeholder\",\n    \"version\": \"1.0.0\"\n}\n",
            'src/index.php' => "<?php\n\necho 'wapinet.ru placeholder';\n",
            'src/styles.css' => "body {\n    font-family: system-ui, sans-serif;\n}\n",
            'src/script.js' => "console.log('wapinet.ru placeholder');\n",
        ];

        foreach ($files as $relative => $content) {
            \file_put_contents($source.'/'.$relative, $content);
        }

        $copies = [
            'docs/guide.pdf' => $this->directory.'/document.pdf',
            'images/cover.jpg' => $this->directory.'/image-jpg-0.jpg',
            'images/preview.png' => $this->directory.'/image-png-0.png',
        ];

        foreach ($copies as $relative => $from) {
            if (\is_file($from)) {
                \copy($from, $source.'/'.$relative);
            }
        }

        $zip = $this->directory.'/archive.zip';

        if (\is_file($zip)) {
            \unlink($zip);
        }

        $archive = new ZipArchive();
        $archive->open($zip, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($directories as $directory) {
            $archive->addEmptyDir($directory);
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile()) {
                continue;
            }

            $relative = \str_replace('\\', '/', \substr($file->getPathname(), \strlen($source) + 1));
            $archive->addFile($file->getPathname(), $relative);
        }

        $archive->close();
        $this->add('zip', $zip);

        $rar = $this->directory.'/archive.rar';

        if (!\is_file($rar)) {
            \file_put_contents($rar, \base64_decode(self::RAR_SAMPLE));
        }

        $this->add('rar', $rar);

        if (null !== $this->sevenZip) {
            $seven = $this->directory.'/archive.7z';

            if (\is_file($seven)) {
                \unlink($seven);
            }

            $cwd = \getcwd();
            \chdir($source);
            \exec(\escapeshellarg($this->sevenZip).' a -t7z -bd -y '.\escapeshellarg($seven).' .', $output, $code);
            \chdir(false !== $cwd ? $cwd : $this->directory);

            if (\is_file($seven)) {
                $this->add('7z', $seven);
            }
        }

        $gz = $this->directory.'/archive.gz';

        if (!\is_file($gz)) {
            \file_put_contents($gz, \gzencode("wapinet.ru placeholder archive\n", 6));
        }

        $this->add('gzip', $gz);
    }

    private function resetDirectory(string $directory): void
    {
        if (\is_dir($directory)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST,
            );

            foreach ($iterator as $file) {
                if (!$file instanceof SplFileInfo) {
                    continue;
                }

                $file->isDir() ? \rmdir($file->getPathname()) : \unlink($file->getPathname());
            }

            \rmdir($directory);
        }

        \mkdir($directory, 0o777, true);
    }

    private function buildDocuments(): void
    {
        $this->add('pdf', $this->pdf($this->directory.'/document.pdf'));

        $this->add('docx', $this->office($this->directory.'/document.docx', 'docx'));
        $this->add('xlsx', $this->office($this->directory.'/document.xlsx', 'xlsx'));
        $this->add('epub', $this->office($this->directory.'/document.epub', 'epub'));
        $this->add('apk', $this->office($this->directory.'/document.apk', 'apk'));
        $this->add('jar', $this->office($this->directory.'/document.jar', 'jar'));

        $doc = 'C:/Windows/System32/MSDRM/MsoIrmProtector.doc';
        $docPath = $this->directory.'/document.doc';

        if (!\is_file($docPath)) {
            if (\is_file($doc)) {
                \copy($doc, $docPath);
            } else {
                \file_put_contents($docPath, "WAPINET DOC PLACEHOLDER\n");
            }
        }

        $this->add('doc', $docPath);

        $xls = 'C:/Windows/System32/MSDRM/MsoIrmProtector.xls';
        $xlsPath = $this->directory.'/document.xls';

        if (!\is_file($xlsPath)) {
            if (\is_file($xls)) {
                \copy($xls, $xlsPath);
            } else {
                \file_put_contents($xlsPath, "WAPINET XLS PLACEHOLDER\n");
            }
        }

        $this->add('xls', $xlsPath);
    }

    private function pdf(string $path): string
    {
        if (\is_file($path)) {
            return $path;
        }

        $text = 'wapinet.ru placeholder PDF';
        $content = "BT /F1 20 Tf 72 760 Td ($text) Tj ET";
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            '<< /Length '.\strlen($content)." >>\nstream\n$content\nendstream",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $index => $object) {
            $offsets[] = \strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n$object\nendobj\n";
        }

        $xref = \strlen($pdf);
        $pdf .= "xref\n0 ".(\count($objects) + 1)."\n0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= \sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= "trailer\n<< /Size ".(\count($objects) + 1)." /Root 1 0 R >>\nstartxref\n$xref\n%%EOF\n";

        \file_put_contents($path, $pdf);

        return $path;
    }

    private function office(string $path, string $kind): string
    {
        if (\is_file($path)) {
            return $path;
        }

        $archive = new ZipArchive();
        $archive->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        match ($kind) {
            'docx' => $this->docx($archive),
            'xlsx' => $this->xlsx($archive),
            'epub' => $this->epub($archive),
            'apk' => $this->apk($archive),
            'jar' => $this->jar($archive),
            default => throw new InvalidArgumentException('Unsupported office kind: '.$kind),
        };

        $archive->close();

        return $path;
    }

    private function docx(ZipArchive $archive): void
    {
        $archive->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
        $archive->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
        $archive->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>wapinet.ru placeholder document</w:t></w:r></w:p></w:body></w:document>');
    }

    private function xlsx(ZipArchive $archive): void
    {
        $archive->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $archive->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $archive->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheets><sheet name="Sheet1" sheetId="1" r:id="rId1" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"/></sheets></workbook>');
        $archive->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
        $archive->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData><row r="1"><c r="A1" t="inlineStr"><is><t>wapinet.ru placeholder</t></is></c></row></sheetData></worksheet>');
    }

    private function epub(ZipArchive $archive): void
    {
        $archive->addFromString('mimetype', 'application/epub+zip');
        $archive->addFromString('META-INF/container.xml', '<?xml version="1.0" encoding="UTF-8"?><container version="1.0" xmlns="urn:oasis:names:tc:opendocument:xmlns:container"><rootfiles><rootfile full-path="OEBPS/content.xhtml" media-type="application/xhtml+xml"/></rootfiles></container>');
        $archive->addFromString('OEBPS/content.xhtml', '<?xml version="1.0" encoding="UTF-8"?><html xmlns="http://www.w3.org/1999/xhtml"><head><title>wapinet.ru</title></head><body><h1>wapinet.ru placeholder</h1></body></html>');
    }

    private function apk(ZipArchive $archive): void
    {
        $archive->addFromString('AndroidManifest.xml', '<manifest package="ru.wapinet.placeholder"/>');
        $archive->addFile($this->directory.'/preview-app.png', 'res/drawable/icon.png');
    }

    private function jar(ZipArchive $archive): void
    {
        $archive->addFromString('META-INF/MANIFEST.MF', "Manifest-Version: 1.0\nMain-Class: ru.wapinet.Placeholder\n");
        $archive->addFile($this->directory.'/preview-app.png', 'icon.png');
    }

    private function buildOther(): void
    {
        $torrent = $this->directory.'/file.torrent';

        if (!\is_file($torrent)) {
            $name = 'wapinet-placeholder.bin';
            $piece = \sha1('wapinet.ru', true);
            $announce = 'http://bt.wapinet.ru/announce';
            $torrentData = 'd8:announce'.\strlen($announce).':'.$announce
                .'4:infod6:lengthi1024e4:name'.\strlen($name).':'.$name
                .'12:piece lengthi16384e6:pieces20:'.$piece.'ee';
            \file_put_contents($torrent, $torrentData);
        }

        $this->add('torrent', $torrent);

        $exe = 'C:/Windows/System32/where.exe';
        $exePath = $this->directory.'/file.exe';

        if (!\is_file($exePath)) {
            if (\is_file($exe)) {
                \copy($exe, $exePath);
            } else {
                \file_put_contents($exePath, "MZ wapinet placeholder\n");
            }
        }

        $this->add('exe', $exePath);

        $dll = 'C:/Windows/System32/version.dll';
        $dllPath = $this->directory.'/file.dll';

        if (!\is_file($dllPath)) {
            if (\is_file($dll)) {
                \copy($dll, $dllPath);
            } else {
                \file_put_contents($dllPath, "MZ wapinet placeholder\n");
            }
        }

        $this->add('dll', $dllPath);

        $bin = $this->directory.'/file.bin';

        if (!\is_file($bin)) {
            \file_put_contents($bin, \str_repeat("\0", 512)."wapinet.ru placeholder\n");
        }

        $this->add('bin', $bin);
        $this->add('msi', $bin);
        $this->add('accdb', $bin);
    }
}

function writePlaceholder(array $templates, string $destination, string $kind, string $fileName, string $originalName, string $mimeType, int $id): void
{
    $extension = \strtolower(\pathinfo($fileName, \PATHINFO_EXTENSION));

    $copy = static function (string $key) use ($templates, $destination, $fileName): void {
        $candidates = $templates[$key] ?? [];

        if ([] === $candidates) {
            return;
        }

        $source = $candidates[\crc32($fileName) % \count($candidates)];

        if (!\is_file($source)) {
            return;
        }

        \copy($source, $destination);
    };

    $pick = static function (string $key) use ($templates, $fileName): ?string {
        $candidates = $templates[$key] ?? [];

        if ([] === $candidates) {
            return null;
        }

        $source = $candidates[\crc32($fileName) % \count($candidates)];

        return \is_file($source) ? $source : null;
    };

    switch ($kind) {
        case 'image':
            $copy('image-'.$extension);
            if (!\is_file($destination)) {
                $copy('jpeg' === $extension ? 'image-jpeg' : 'image-jpg');
            }
            if (!\is_file($destination)) {
                $copy('image');
            }
            break;

        case 'psd':
            $copy('psd');
            break;

        case 'ai':
            $copy('ai');
            break;

        case 'video':
            $copy('video-'.('3gpp' === $extension ? '3gp' : $extension));
            // Fall back to any video template when the exact container is missing.
            if (!\is_file($destination)) {
                $copy('video');
            }
            if (null !== ($preview = $pick('preview-video'))) {
                \copy($preview, $destination.'.jpg');
            }
            if (\in_array($extension, ['3gp', '3gpp', 'avi', 'wmv', 'asf', 'mov', 'flv'], true) && \is_file($templates['video-mp4'][0] ?? '')) {
                \copy($templates['video-mp4'][0], $destination.'.mp4');
            }
            break;

        case 'audio':
            $copy('audio-'.$extension);
            if (!\is_file($destination)) {
                $copy('audio');
            }
            if (null !== ($preview = $pick('preview-audio'))) {
                \copy($preview, $destination.'.jpg');
            }
            break;

        case 'apk':
        case 'jar':
            $copy($kind);
            if (null !== ($preview = $pick('preview-app'))) {
                \copy($preview, $destination.'.png');
            }
            break;

        case 'zip':
        case 'rar':
        case '7z':
        case 'gzip':
        case 'pdf':
        case 'doc':
        case 'docx':
        case 'xls':
        case 'xlsx':
        case 'epub':
        case 'exe':
        case 'dll':
        case 'msi':
        case 'accdb':
        case 'torrent':
            $copy($kind);
            break;

        case 'text':
        case 'playlist':
            \file_put_contents($destination, \textContent($extension, $fileName, $mimeType, $id));
            break;

        default:
            $copy('bin');
            break;
    }
}

function textContent(string $extension, string $fileName, string $mimeType, int $id): string
{
    $header = "wapinet.ru placeholder\nfile: $fileName\nmime: $mimeType\nid: $id\n\n";

    return match ($extension) {
        'csv' => "id,name,mime\n$id,\"$fileName\",\"$mimeType\"\n",
        'json' => \json_encode(['id' => $id, 'file' => $fileName, 'mime' => $mimeType], \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE)."\n",
        'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<file id=\"$id\"><name>".\htmlspecialchars($fileName, \ENT_XML1)."</name><mime>$mimeType</mime></file>\n",
        'html' => "<!doctype html>\n<html lang=\"ru\"><meta charset=\"utf-8\"><title>$fileName</title><body><h1>".\htmlspecialchars($fileName)."</h1><p>wapinet.ru placeholder</p></body></html>\n",
        'sql' => "-- wapinet.ru placeholder\nSELECT $id AS id, '$fileName' AS name, '$mimeType' AS mime;\n",
        'php' => "<?php\n\n// wapinet.ru placeholder: $fileName\n",
        'm3u', 'm3u8' => "#EXTM3U\n#EXTINF:2,Placeholder\nhttp://example.com/placeholder.mp3\n",
        'fb2' => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<FictionBook xmlns=\"http://www.gribuser.ru/xml/fictionbook/2.0\"><description><title-info><book-title>".\htmlspecialchars($fileName, \ENT_XML1)."</book-title></title-info></description><body><section><p>wapinet.ru placeholder</p></section></body></FictionBook>\n",
        'rtf' => "{\\rtf1\\ansi wapinet.ru placeholder\\par $fileName}",
        default => $header.\str_repeat("Placeholder text for $fileName.\n", 12),
    };
}

function detectKind(string $mimeType, string $fileName): string
{
    $extension = \strtolower(\pathinfo($fileName, \PATHINFO_EXTENSION));

    if (\in_array($mimeType, FileMime::TORRENT, true)) {
        return 'torrent';
    }

    if (\in_array($mimeType, FileMime::PLAYLIST, true) || \in_array($extension, ['m3u', 'm3u8'], true)) {
        return 'playlist';
    }

    if (\in_array($mimeType, FileMime::ANDROID, true)) {
        return 'apk';
    }

    if (\in_array($mimeType, FileMime::JAVA, true)) {
        return 'jar';
    }

    if (\in_array($mimeType, FileMime::PDF, true)) {
        return 'pdf';
    }

    if (\in_array($mimeType, FileMime::WORD, true)) {
        return 'docx' === $extension ? 'docx' : 'doc';
    }

    if (\in_array($mimeType, FileMime::EXCEL, true)) {
        return 'xlsx' === $extension ? 'xlsx' : 'xls';
    }

    if ('application/epub+zip' === $mimeType) {
        return 'epub';
    }

    if (\in_array($mimeType, FileMime::ARCHIVE, true)) {
        return match ($extension) {
            'rar' => 'rar',
            '7z' => '7z',
            'gz' => 'gzip',
            default => 'zip',
        };
    }

    if ('application/gzip' === $mimeType) {
        return 'gzip';
    }

    if ('application/x-msi' === $mimeType) {
        return 'msi';
    }

    if ('application/x-msaccess' === $mimeType) {
        return 'accdb';
    }

    if ('application/postscript' === $mimeType || 'application/illustrator' === $mimeType || 'application/vnd.adobe.illustrator' === $mimeType) {
        return 'ai';
    }

    if (\str_starts_with($mimeType, 'image/')) {
        return 'psd' === $extension ? 'psd' : 'image';
    }

    if (\str_starts_with($mimeType, 'video/') || \in_array($mimeType, FileMime::VIDEO_EXTRA, true)) {
        return 'video';
    }

    if (\str_starts_with($mimeType, 'audio/')) {
        return 'audio';
    }

    if ('application/x-dosexec' === $mimeType || 'application/x-msdownload' === $mimeType || 'application/vnd.microsoft.portable-executable' === $mimeType) {
        return 'dll' === $extension ? 'dll' : 'exe';
    }

    if (\str_starts_with($mimeType, 'text/') || \in_array($extension, ['txt', 'csv', 'xml', 'html', 'json', 'sql', 'php', 'm3u', 'm3u8', 'fb2', 'rtf', 'css', 'js', 'md'], true)) {
        return 'text';
    }

    return 'bin';
}

function connect(string $root): PDO
{
    $url = null;

    foreach ([$root.'/.env.local', $root.'/.env', $root.'/.env.dist'] as $file) {
        if (!\is_file($file)) {
            continue;
        }

        foreach (\file($file, \FILE_IGNORE_NEW_LINES) as $line) {
            if (\preg_match('/^DATABASE_URL="?([^"]+)"?$/', \trim($line), $matches)) {
                $url = $matches[1];
                break 2;
            }
        }
    }

    if (null === $url) {
        throw new RuntimeException('DATABASE_URL is not configured.');
    }

    $parts = \parse_url($url);

    if (false === $parts || !isset($parts['host'], $parts['path'])) {
        throw new RuntimeException('Unable to parse DATABASE_URL.');
    }

    $dsn = \sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $parts['host'],
        $parts['port'] ?? 3306,
        \ltrim($parts['path'], '/'),
    );

    return new PDO($dsn, $parts['user'] ?? 'root', $parts['pass'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
}

function findBinary(string $name): ?string
{
    $output = [];
    \exec('where '.\escapeshellarg($name).' 2>NUL', $output, $code);

    if (0 === $code && [] !== $output) {
        return \trim($output[0]);
    }

    return null;
}
