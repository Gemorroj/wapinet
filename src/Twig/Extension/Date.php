<?php

namespace App\Twig\Extension;

use App\Service\Timezone;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class Date extends AbstractExtension
{
    public function __construct(private readonly Timezone $timezoneHelper)
    {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('wapinet_date', $this->getDate(...)),
            new TwigFilter('wapinet_time', $this->getTime(...)),
            new TwigFilter('wapinet_datetime', $this->getDateTime(...)),
            new TwigFilter('wapinet_length', $this->getLength(...)),
        ];
    }

    public function getDate(\DateTimeInterface|array|null $originalDatetime): string
    {
        if (!$originalDatetime) {
            return '';
        }

        if (\is_array($originalDatetime)) {
            $datetime = new \DateTime($originalDatetime['date'], new \DateTimeZone($originalDatetime['timezone']));
        } else {
            $datetime = \DateTime::createFromInterface($originalDatetime);
        }
        $timezone = $this->timezoneHelper->getTimezone();
        if ($timezone) {
            $datetime->setTimezone($timezone);
        }

        $today = new \DateTime('today', $timezone);
        $yesterday = new \DateTime('yesterday', $timezone);
        $dayBeforeYesterday = new \DateTime('yesterday - 1 day', $timezone);
        $tomorrow = new \DateTime('tomorrow', $timezone);
        $dayAfterTomorrow = new \DateTime('tomorrow + 1 day', $timezone);

        if ($datetime->format('Ymd') === $today->format('Ymd')) {
            return 'Сегодня';
        }
        if ($datetime->format('Ymd') === $yesterday->format('Ymd')) {
            return 'Вчера';
        }
        if ($datetime->format('Ymd') === $dayBeforeYesterday->format('Ymd')) {
            return 'Позавчера';
        }
        if ($datetime->format('Ymd') === $tomorrow->format('Ymd')) {
            return 'Завтра';
        }
        if ($datetime->format('Ymd') === $dayAfterTomorrow->format('Ymd')) {
            return 'Послезавтра';
        }

        return $datetime->format('d.m.Y');
    }

    public function getDateTime(\DateTimeInterface|array|null $originalDatetime): string
    {
        if (!$originalDatetime) {
            return '';
        }

        if (\is_array($originalDatetime)) {
            $datetime = new \DateTime($originalDatetime['date'], new \DateTimeZone($originalDatetime['timezone']));
        } else {
            $datetime = \DateTime::createFromInterface($originalDatetime);
        }
        $timezone = $this->timezoneHelper->getTimezone();
        if ($timezone) {
            $datetime->setTimezone($timezone);
        }

        $today = new \DateTime('today', $timezone);
        $yesterday = new \DateTime('yesterday', $timezone);
        $dayBeforeYesterday = new \DateTime('yesterday - 1 day', $timezone);
        $tomorrow = new \DateTime('tomorrow', $timezone);
        $dayAfterTomorrow = new \DateTime('tomorrow + 1 day', $timezone);

        if ($datetime->format('Ymd') === $today->format('Ymd')) {
            return 'Сегодня в '.$datetime->format('H:i');
        }
        if ($datetime->format('Ymd') === $yesterday->format('Ymd')) {
            return 'Вчера в '.$datetime->format('H:i');
        }
        if ($datetime->format('Ymd') === $dayBeforeYesterday->format('Ymd')) {
            return 'Позавчера в '.$datetime->format('H:i');
        }
        if ($datetime->format('Ymd') === $tomorrow->format('Ymd')) {
            return 'Завтра в '.$datetime->format('H:i');
        }
        if ($datetime->format('Ymd') === $dayAfterTomorrow->format('Ymd')) {
            return 'Послезавтра в '.$datetime->format('H:i');
        }

        // return $datetime->format('d.m.Y H:i:s');
        return $datetime->format('d.m.Y H:i');
    }

    public function getTime(\DateTimeInterface|array|null $originalDatetime): string
    {
        if (!$originalDatetime) {
            return '';
        }

        if (\is_array($originalDatetime)) {
            $datetime = new \DateTime($originalDatetime['date'], new \DateTimeZone($originalDatetime['timezone']));
        } else {
            $datetime = \DateTime::createFromInterface($originalDatetime);
        }
        $timezone = $this->timezoneHelper->getTimezone();
        if ($timezone) {
            $datetime->setTimezone($timezone);
        }

        return $datetime->format('Hч.iм.sс.');
    }

    /**
     * @param int|float|string|\DateInterval $value seconds or \DateInterval
     */
    public function getLength(int|float|string|\DateInterval $value): string
    {
        if ($value instanceof \DateInterval) {
            $iv = $value;
        } else {
            $d1 = new \DateTime();
            $d2 = new \DateTime('- '.\round($value).' seconds');
            $iv = $d2->diff($d1);
        }

        $length = [];
        if ($iv->y) {
            $length[] = $iv->y.'г.';
        }

        if ($iv->m) {
            $length[] = $iv->m.'м.';
        }

        if ($iv->d) {
            $length[] = $iv->d.'д.';
        }

        if ($iv->h) {
            $length[] = $iv->h.'ч.';
        }

        if ($iv->i) {
            $length[] = $iv->i.'мин.';
        }

        if ($iv->s) {
            $length[] = $iv->s.'сек.';
        }

        return \implode(' ', $length);
    }
}
