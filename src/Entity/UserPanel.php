<?php

namespace App\Entity;

use App\Repository\UserPanelRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table]
#[ORM\Entity(repositoryClass: UserPanelRepository::class)]
class UserPanel implements \Stringable, \JsonSerializable
{
    private const string ROUTE_FORUM = 'forum_index'; // hardcoded in base.html.twig
    private const string ROUTE_GUESTBOOK = 'guestbook_index';
    private const string ROUTE_GIST = 'gist_index';
    private const string ROUTE_FILE = 'file_index';
    private const string ROUTE_ARCHIVER = 'archiver_index';
    private const string ROUTE_HTTP = 'http_index';
    private const string ROUTE_WHOIS = 'whois_index';
    private const string ROUTE_PHP_VALIDATOR = 'php_validator_index';
    private const string ROUTE_HTML_VALIDATOR = 'html_validator_index';
    private const string ROUTE_CSS_VALIDATOR = 'css_validator_index';
    private const string ROUTE_PHP_OBFUSCATOR = 'php_obfuscator_index';
    private const string ROUTE_AUDIO_TAGS = 'audio_tags_index';
    private const string ROUTE_RENAME = 'rename_index';
    private const string ROUTE_EMAIL = 'email_index';
    private const string ROUTE_BROWSER_INFO = 'browser_info';
    private const string ROUTE_HASH = 'hash_index';
    private const string ROUTE_CODE = 'code_index';
    private const string ROUTE_UNICODE = 'unicode_index';
    private const string ROUTE_UNICODE_ICONS = 'unicode_icons';
    private const string ROUTE_POLITICS = 'politics';
    private const string ROUTE_RATES = 'rates_index';
    private const string ROUTE_MOBILE_CODE = 'mobile_code_index';
    private const string ROUTE_OPEN_SOURCE = 'open_source';
    private const string ROUTE_TEXTBOOK = 'textbook';
    private const string ROUTE_VIDEO_COURSES = 'video_courses';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $id = null;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => true])]
    private bool $forum = true;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $guestbook = false;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => true])]
    private bool $gist = true;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => true])]
    private bool $file = true;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $archiver = false;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $http = false;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $whois = false;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $phpValidator = false;
    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $htmlValidator = false;
    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $cssValidator = false;
    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $phpObfuscator = false;
    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $audioTags = false;
    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $rename = false;
    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $email = false;
    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $browserInfo = false;
    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $hash = false;
    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $code = false;
    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $unicode = false;
    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $unicodeIcons = false;
    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $politics = false;
    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $rates = false;
    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $mobileCode = false;
    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $openSource = false;
    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $textbook = false;
    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $videoCourses = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return \ArrayIterator<string, array{route: string, name: string, enabled: bool}>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator([
            self::ROUTE_FORUM => [
                'route' => self::ROUTE_FORUM,
                'name' => 'Форум',
                'enabled' => $this->isForum(),
            ],
            self::ROUTE_GUESTBOOK => [
                'route' => self::ROUTE_GUESTBOOK,
                'name' => 'Гостевая',
                'enabled' => $this->isGuestbook(),
            ],
            self::ROUTE_GIST => [
                'route' => self::ROUTE_GIST,
                'name' => 'Блоги',
                'enabled' => $this->isGist(),
            ],
            self::ROUTE_FILE => [
                'route' => self::ROUTE_FILE,
                'name' => 'Файлообменник',
                'enabled' => $this->isFile(),
            ],
            self::ROUTE_ARCHIVER => [
                'route' => self::ROUTE_ARCHIVER,
                'name' => 'Архиватор',
                'enabled' => $this->isArchiver(),
            ],
            self::ROUTE_HTTP => [
                'route' => self::ROUTE_HTTP,
                'name' => 'HTTP клиент',
                'enabled' => $this->isHttp(),
            ],
            self::ROUTE_WHOIS => [
                'route' => self::ROUTE_WHOIS,
                'name' => 'WHOIS/RDAP',
                'enabled' => $this->isWhois(),
            ],
            self::ROUTE_PHP_VALIDATOR => [
                'route' => self::ROUTE_PHP_VALIDATOR,
                'name' => 'PHP валидатор',
                'enabled' => $this->isPhpValidator(),
            ],
            self::ROUTE_HTML_VALIDATOR => [
                'route' => self::ROUTE_HTML_VALIDATOR,
                'name' => 'HTML валидатор',
                'enabled' => $this->isHtmlValidator(),
            ],
            self::ROUTE_CSS_VALIDATOR => [
                'route' => self::ROUTE_CSS_VALIDATOR,
                'name' => 'CSS валидатор',
                'enabled' => $this->isCssValidator(),
            ],
            self::ROUTE_PHP_OBFUSCATOR => [
                'route' => self::ROUTE_PHP_OBFUSCATOR,
                'name' => 'PHP обфускатор',
                'enabled' => $this->isPhpObfuscator(),
            ],
            self::ROUTE_AUDIO_TAGS => [
                'route' => self::ROUTE_AUDIO_TAGS,
                'name' => 'Редактор аудио тегов',
                'enabled' => $this->isAudioTags(),
            ],
            self::ROUTE_RENAME => [
                'route' => self::ROUTE_RENAME,
                'name' => 'Переименование файлов',
                'enabled' => $this->isRename(),
            ],
            self::ROUTE_EMAIL => [
                'route' => self::ROUTE_EMAIL,
                'name' => 'Отправка E-mail',
                'enabled' => $this->isEmail(),
            ],
            self::ROUTE_BROWSER_INFO => [
                'route' => self::ROUTE_BROWSER_INFO,
                'name' => 'Информация о браузере',
                'enabled' => $this->isBrowserInfo(),
            ],
            self::ROUTE_HASH => [
                'route' => self::ROUTE_HASH,
                'name' => 'Хэширование данных',
                'enabled' => $this->isHash(),
            ],
            self::ROUTE_CODE => [
                'route' => self::ROUTE_CODE,
                'name' => 'Конвертирование данных',
                'enabled' => $this->isCode(),
            ],
            self::ROUTE_UNICODE => [
                'route' => self::ROUTE_UNICODE,
                'name' => 'Конвертер в Unicode',
                'enabled' => $this->isUnicode(),
            ],
            self::ROUTE_UNICODE_ICONS => [
                'route' => self::ROUTE_UNICODE_ICONS,
                'name' => 'Пиктограммы в Unicode',
                'enabled' => $this->isUnicodeIcons(),
            ],
            self::ROUTE_POLITICS => [
                'route' => self::ROUTE_POLITICS,
                'name' => 'Политика',
                'enabled' => $this->isPolitics(),
            ],
            self::ROUTE_RATES => [
                'route' => self::ROUTE_RATES,
                'name' => 'Курсы валют',
                'enabled' => $this->isRates(),
            ],
            self::ROUTE_MOBILE_CODE => [
                'route' => self::ROUTE_MOBILE_CODE,
                'name' => 'Телефонные коды',
                'enabled' => $this->isMobileCode(),
            ],
            self::ROUTE_OPEN_SOURCE => [
                'route' => self::ROUTE_OPEN_SOURCE,
                'name' => 'Open source разработки',
                'enabled' => $this->isOpenSource(),
            ],
            self::ROUTE_TEXTBOOK => [
                'route' => self::ROUTE_TEXTBOOK,
                'name' => 'Учебники',
                'enabled' => $this->isTextbook(),
            ],
            self::ROUTE_VIDEO_COURSES => [
                'route' => self::ROUTE_VIDEO_COURSES,
                'name' => 'Видео-курсы',
                'enabled' => $this->isVideoCourses(),
            ],
        ]);
    }

    public function setForum(bool $forum): self
    {
        $this->forum = $forum;

        return $this;
    }

    public function isForum(): bool
    {
        return $this->forum;
    }

    public function setGuestbook(bool $guestbook): self
    {
        $this->guestbook = $guestbook;

        return $this;
    }

    public function isGuestbook(): bool
    {
        return $this->guestbook;
    }

    public function setArchiver(bool $archiver): self
    {
        $this->archiver = $archiver;

        return $this;
    }

    public function isArchiver(): bool
    {
        return $this->archiver;
    }

    public function setGist(bool $gist): self
    {
        $this->gist = $gist;

        return $this;
    }

    public function isGist(): bool
    {
        return $this->gist;
    }

    public function setFile(bool $file): self
    {
        $this->file = $file;

        return $this;
    }

    public function isFile(): bool
    {
        return $this->file;
    }

    public function isHttp(): bool
    {
        return $this->http;
    }

    public function setHttp(bool $http): self
    {
        $this->http = $http;

        return $this;
    }

    public function isWhois(): bool
    {
        return $this->whois;
    }

    public function setWhois(bool $whois): self
    {
        $this->whois = $whois;

        return $this;
    }

    public function isPhpValidator(): bool
    {
        return $this->phpValidator;
    }

    public function setPhpValidator(bool $phpValidator): self
    {
        $this->phpValidator = $phpValidator;

        return $this;
    }

    public function isHtmlValidator(): bool
    {
        return $this->htmlValidator;
    }

    public function setHtmlValidator(bool $htmlValidator): self
    {
        $this->htmlValidator = $htmlValidator;

        return $this;
    }

    public function isCssValidator(): bool
    {
        return $this->cssValidator;
    }

    public function setCssValidator(bool $cssValidator): self
    {
        $this->cssValidator = $cssValidator;

        return $this;
    }

    public function isPhpObfuscator(): bool
    {
        return $this->phpObfuscator;
    }

    public function setPhpObfuscator(bool $phpObfuscator): self
    {
        $this->phpObfuscator = $phpObfuscator;

        return $this;
    }

    public function isAudioTags(): bool
    {
        return $this->audioTags;
    }

    public function setAudioTags(bool $audioTags): self
    {
        $this->audioTags = $audioTags;

        return $this;
    }

    public function isRename(): bool
    {
        return $this->rename;
    }

    public function setRename(bool $rename): self
    {
        $this->rename = $rename;

        return $this;
    }

    public function isEmail(): bool
    {
        return $this->email;
    }

    public function setEmail(bool $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function isBrowserInfo(): bool
    {
        return $this->browserInfo;
    }

    public function setBrowserInfo(bool $browserInfo): self
    {
        $this->browserInfo = $browserInfo;

        return $this;
    }

    public function isHash(): bool
    {
        return $this->hash;
    }

    public function setHash(bool $hash): self
    {
        $this->hash = $hash;

        return $this;
    }

    public function isCode(): bool
    {
        return $this->code;
    }

    public function setCode(bool $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function isUnicode(): bool
    {
        return $this->unicode;
    }

    public function setUnicode(bool $unicode): self
    {
        $this->unicode = $unicode;

        return $this;
    }

    public function isUnicodeIcons(): bool
    {
        return $this->unicodeIcons;
    }

    public function setUnicodeIcons(bool $unicodeIcons): self
    {
        $this->unicodeIcons = $unicodeIcons;

        return $this;
    }

    public function isPolitics(): bool
    {
        return $this->politics;
    }

    public function setPolitics(bool $politics): self
    {
        $this->politics = $politics;

        return $this;
    }

    public function isRates(): bool
    {
        return $this->rates;
    }

    public function setRates(bool $rates): self
    {
        $this->rates = $rates;

        return $this;
    }

    public function isMobileCode(): bool
    {
        return $this->mobileCode;
    }

    public function setMobileCode(bool $mobileCode): self
    {
        $this->mobileCode = $mobileCode;

        return $this;
    }

    public function isOpenSource(): bool
    {
        return $this->openSource;
    }

    public function setOpenSource(bool $openSource): self
    {
        $this->openSource = $openSource;

        return $this;
    }

    public function isTextbook(): bool
    {
        return $this->textbook;
    }

    public function setTextbook(bool $textbook): self
    {
        $this->textbook = $textbook;

        return $this;
    }

    public function isVideoCourses(): bool
    {
        return $this->videoCourses;
    }

    public function setVideoCourses(bool $videoCourses): self
    {
        $this->videoCourses = $videoCourses;

        return $this;
    }

    public function __toString(): string
    {
        $result = '';
        foreach ($this->getIterator() as $item) {
            if (true === $item['enabled']) {
                $result .= $item['name'].', ';
            }
        }

        return \rtrim($result, ', ');
    }

    public function jsonSerialize(): array
    {
        return \get_object_vars($this);
    }
}
