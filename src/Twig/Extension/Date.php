<?php

namespace App\Twig\Extension;

use App\Service\Timezone;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class Date extends AbstractExtension
{
    private Timezone $timezone;

    public function __construct(Timezone $timezone)
    {
        $this->timezone = $timezone;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('wapinet_date', [$this, 'getDate']),
            new TwigFilter('wapinet_time', [$this, 'getTime']),
            new TwigFilter('wapinet_datetime', [$this, 'getDateTime']),
            new TwigFilter('wapinet_length', [$this, 'getLength']),
        ];
    }

    public function getDate(?\DateTimeInterface $date): string
    {
        if ($date instanceof \DateTimeInterface) {
            $timezone = $this->timezone->getTimezone();

            if (null !== $timezone) {
                $date->setTimezone($timezone);
            }

            $today = new \DateTime('today', $timezone);
            $yesterday = new \DateTime('yesterday', $timezone);
            $dayBeforeYesterday = new \DateTime('yesterday - 1 day', $timezone);
            $tomorrow = new \DateTime('tomorrow', $timezone);
            $dayAfterTomorrow = new \DateTime('tomorrow + 1 day', $timezone);

            if ($date->format('Ymd') === $today->format('Ymd')) {
                return 'Сегодня';
            }
            if ($date->format('Ymd') === $yesterday->format('Ymd')) {
                return 'Вчера';
            }
            if ($date->format('Ymd') === $dayBeforeYesterday->format('Ymd')) {
                return 'Позавчера';
            }
            if ($date->format('Ymd') === $tomorrow->format('Ymd')) {
                return 'Завтра';
            }
            if ($date->format('Ymd') === $dayAfterTomorrow->format('Ymd')) {
                return 'Послезавтра';
            }

            return $date->format('d.m.Y');
        }

        return '';
    }

    public function getDateTime(?\DateTimeInterface $datetime): string
    {
        if ($datetime instanceof \DateTimeInterface) {
            $timezone = $this->timezone->getTimezone();

            if (null !== $timezone) {
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

        return '';
    }

    public function getTime(?\DateTimeInterface $datetime): string
    {
        if ($datetime instanceof \DateTimeInterface) {
            $timezone = $this->timezone->getTimezone();

            if (null !== $timezone) {
                $datetime->setTimezone($timezone);
            }

            return $datetime->format('Hч.iм.sс.');
        }

        return '';
    }

    public function getLength(int|float|string $seconds): string
    {
        $length = '';
        $d1 = new \DateTime();
        $d2 = new \DateTime('- '.\round($seconds).' seconds');

        $iv = $d2->diff($d1);

        if ($iv->y) {
            $length .= $iv->y.'г.';
        }

        if ($iv->m) {
            $length .= $iv->m.'м.';
        }

        if ($iv->d) {
            $length .= $iv->d.'д.';
        }

        if ($iv->h) {
            $length .= $iv->h.'ч.';
        }

        if ($iv->i) {
            $length .= $iv->i.'мин.';
        }

        if ($iv->s) {
            $length .= $iv->s.'сек.';
        }

        return $length;
    }
}
