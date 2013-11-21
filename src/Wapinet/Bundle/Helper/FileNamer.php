<?php

namespace Wapinet\Bundle\Helper;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileNamer
{
    /**
     * @var UploadedFile
     */
    protected $file;
    /**
     * @var string
     */
    protected $baseDirectory;

    /**
     * @param string $baseDirectory
     * @param UploadedFile $file
     * @return FileNamer
     */
    public function init($baseDirectory, UploadedFile $file)
    {
        $this->baseDirectory = $baseDirectory;
        $this->file = $file;

        return $this;
    }

    /**
     * @return string
     */
    public function getDirectory()
    {
        $date = new \DateTime();

        return $date->format('Y/m/d');
    }


    /**
     * @return string
     */
    public function getFilename()
    {
        $name = preg_replace('/[^\\pL\d.]+/u', '-', $this->file->getClientOriginalName());

        $iso = array(
            "Є" => "YE", "І" => "I", "Ѓ" => "G", "і" => "i", "№" => "N", "є" => "ye", "ѓ" => "g",
            "А" => "A", "Б" => "B", "В" => "V", "Г" => "G", "Д" => "D",
            "Е" => "E", "Ё" => "YO", "Ж" => "ZH",
            "З" => "Z", "И" => "I", "Й" => "J", "К" => "K", "Л" => "L",
            "М" => "M", "Н" => "N", "О" => "O", "П" => "P", "Р" => "R",
            "С" => "S", "Т" => "T", "У" => "U", "Ф" => "F", "Х" => "H",
            "Ц" => "C", "Ч" => "CH", "Ш" => "SH", "Щ" => "SHH", "Ъ" => "'",
            "Ы" => "Y", "Ь" => "", "Э" => "E", "Ю" => "YU", "Я" => "YA",
            "а" => "a", "б" => "b", "в" => "v", "г" => "g", "д" => "d",
            "е" => "e", "ё" => "yo", "ж" => "zh",
            "з" => "z", "и" => "i", "й" => "j", "к" => "k", "л" => "l",
            "м" => "m", "н" => "n", "о" => "o", "п" => "p", "р" => "r",
            "с" => "s", "т" => "t", "у" => "u", "ф" => "f", "х" => "h",
            "ц" => "c", "ч" => "ch", "ш" => "sh", "щ" => "shh", "ъ" => "",
            "ы" => "y", "ь" => "", "э" => "e", "ю" => "yu", "я" => "ya", "«" => "", "»" => "", "—" => "-"
        );
        $name = strtr($name, $iso);
        // trim
        $name = trim($name, '-');

        // transliterate
        if (function_exists('iconv')) {
            $name = iconv('utf-8', 'us-ascii//TRANSLIT', $name);
        }

        $name = strtolower($name);


        //TODO: если файл с таким именем уже существует, добавлять префикс


        return $name;
    }
}
