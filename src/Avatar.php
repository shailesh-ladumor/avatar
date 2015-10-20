<?php
namespace Laravolt\Avatar;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Intervention\Image\Facades\Image;
use Stringy\Stringy;

class Avatar
{
    protected $name;
    protected $chars;
    protected $colors;
    protected $fonts;
    protected $fontSize;
    protected $width;
    protected $height;
    protected $image;
    protected $background = '#cccccc';
    protected $foreground = '#ffffff';
    protected $initials = '';
    protected $ascii = false;

    /**
     * Avatar constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->chars = Arr::get($config, 'chars', 2);
        $this->colors = Arr::get($config, 'colors', ['#999999']);
        $this->fonts = Arr::get($config, 'fonts', [1]);
        $this->fontSize = Arr::get($config, 'fontSize', 32);
        $this->width = Arr::get($config, 'width', 100);
        $this->height = Arr::get($config, 'height', 100);
        $this->ascii = Arr::get($config, 'ascii', false);
    }

    public function create($name)
    {
        if (is_array($name)) {
            throw new \InvalidArgumentException(
                'Passed value cannot be an array'
            );
        } elseif (is_object($name) && !method_exists($name, '__toString')) {
            throw new \InvalidArgumentException(
                'Passed object must have a __toString method'
            );
        }

        $this->name = Stringy::create($name)->collapseWhitespace();
        if ($this->ascii)
        {
            $this->name = $this->name->toAscii();
        }

        $this->initials = $this->getInitials();
        $this->setBackground($this->getRandomBackground());

        return $this;
    }

    public function toBase64()
    {
        $this->buildAvatar();

        return $this->image->encode('data-url');
    }

    public function setBackground($hex)
    {
        $this->background = $hex;

        return $this;
    }

    public function setForeground($hex)
    {
        $this->foreground = $hex;

        return $this;
    }

    public function setDimension($width, $height = null)
    {
        if (!$height) {
            $height = $width;
        }
        $this->width = $width;
        $this->height = $height;

        return $this;
    }

    public function setFontSize($size)
    {
        $this->fontSize = $size;

        return $this;
    }

    protected function getInitials()
    {
        $words = new Collection(explode(' ', $this->name));

        // if name contains single word, use first N character
        if ($words->count() === 1) {
            if ($this->name->length() >= $this->chars) {
                return $string->substr(0, $this->chars);
            }

            return (string)$string;
        }

        // otherwise, use initial char from each word
        $initials = new Collection();
        $words->each(function ($word) use ($initials) {
            $initials->push(Stringy::create($word)->substr(0, 1));
        });

        return $initials->slice(0, $this->chars)->implode('');
    }

    protected function getRandomBackground()
    {
        if (strlen($this->initials) == 0) {
            return $this->background;
        }

        $number = ord($this->initials[0]);
        $i = 1;
        $charLength = strlen($this->initials);
        while ($i < $charLength) {
            $number += ord($this->initials[$i]);
            $i++;
        }

        return $this->colors[$number % count($this->colors)];
    }

    protected function getFont()
    {
        $initials = $this->getInitials();

        if ($initials) {
            $number = ord($initials[0]);
            $font = $this->fonts[$number % count($this->fonts)];
            $fontFile = base_path('resources/laravolt/avatar/fonts/' . $font);
            if (is_file($fontFile)) {
                return $fontFile;
            }
        }

        return 5;
    }

    protected function buildAvatar()
    {
        $this->image = Image::canvas($this->width, $this->height);
        $this->image->fill($this->background);

        $x = $this->width / 2;
        $y = $this->height / 2;
        $initials = $this->getInitials();

        $this->image->text($initials, $x, $y, function ($font) {
            $font->file($this->getFont());
            $font->size($this->fontSize);
            $font->color($this->foreground);
            $font->align('center');
            $font->valign('middle');
        });

    }
}