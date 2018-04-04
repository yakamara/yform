<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_captcha extends rex_yform_value_abstract
{
    public function postValidateAction()
    {
        rex_login::startSession();

        $this->captcha_ini = parse_ini_string($this->captacha_ini(), true);
        extract($this->captcha_ini);

        $captchaRequest = rex_request('captcha', 'string');

        if ($captchaRequest == 'show') {
            while (@ob_end_clean());
            $this->captcha_showImage();
            exit;
        }

        if ($this->params['send'] == 1 && $_SESSION['captcha'] != '' && md5(mb_strtolower($this->getValue())) == $_SESSION['captcha']) {
            $_SESSION['captcha'] = '';
        } elseif ($this->params['send'] == 1) {
            if ($this->getElement(4) == 1) {
                $this->params['warning'] = [];
                $this->params['warning_messages'] = [];
            };

            $this->params['warning'][$this->getId()] = $this->params['error_class'];
            $this->params['warning_messages'][$this->getId()] = $this->getElement(2);
        }
    }


    public function enterObject()
    {
        if (!$this->needsOutput()) {
            return;
        }

        if ($this->getElement(3) != '') {
            $link = $this->getElement(3);
            if (preg_match("/\?/", $link)) {
                if (mb_substr($link, -1) != '&') {
                    $link .= '&';
                }
            } else {
                $link .= '?';
            }

            $link .= 'captcha=show&' . time();
        } else {
            $link = rex_getUrl($this->params['article_id'], $this->params['clang'], ['captcha' => 'show'], '&') . '&' . time() . str_replace(' ', '', microtime());
        }

        $this->params['form_output'][$this->getId()] = $this->parse('value.captcha.tpl.php', ['link' => $link]);
    }

    public function getDescription()
    {
        return 'captcha|Beschreibungstext|Fehlertext|[link]|hide_warnings[0/1]';
    }

    public function captcha_showImage()
    {
        extract($this->captcha_ini);
        $this->captcha_image = imagecreate($width, $height);

        $this->captcha_letters = $this->captcha_get_random_letters();
        $this->captcha_put_md5_into_session();

        $this->captcha_add_dust_and_scratches($bg_color_2);
        $this->captcha_print_letters();
        $this->captcha_add_dust_and_scratches($bg_color_1);

        header('Content-type: image/jpeg');
        imagejpeg($this->captcha_image);
        imagedestroy($this->captcha_image);
    }

    public function captcha_add_dust_and_scratches($color)
    {
        extract($this->captcha_ini);

        $max_x = $width - 1;
        $max_y = $height - 1;

        $color = $this->captcha_split($color);

        $color = imagecolorallocate($this->captcha_image, $color[0], $color[1], $color[2]);

        for ($i = 0; $i < $noise; ++$i) {
            if (rand(1, 100) > $dust_vs_scratches) {
                imageline($this->captcha_image, rand(0, $max_x), rand(0, $max_y), rand(0, $max_x), rand(0, $max_y), $color);
            } else {
                imagesetpixel($this->captcha_image, rand(0, $max_x), rand(0, $max_y), $color);
            }
        }
    }

    public function captcha_print_letters()
    {
        extract($this->captcha_ini);

        $font_path = rex_addon::get('yform')->getDataPath('fonts');

        list($padding_top, $padding_right, $padding_bottom, $padding_left) = $this->captcha_split($padding);
        $box_width = ($width - ($padding_left + $padding_right)) / $letters_no;
        $box_height = $height - ($padding_top + $padding_bottom);

        $font_size = $this->captcha_split($font_size);
        $font_size_count = (count($font_size) - 1);

        $fonts = $this->captcha_split($fonts);
        $fonts_count = (count($fonts) - 1);

        foreach ($fonts as $k => $v) {
            $a[$k] = $font_path.'/'.$v.'.ttf';
        }
        $fonts = $a;
        unset($a);

        // sem pridat podporu pro #xxx a #xxxxxx resolve_color()
        $fg_colors_count = (count($fg_colors) - 1);
        foreach ($fg_colors as $fg_color) {
            $a[] = $this->captcha_split($fg_color);
        }
        $fg_colors = $a;
        unset($a);

        for ($i = 0; $i < $letters_no; ++$i) {
            $size_index = rand(0, $font_size_count);
            $size = $font_size[$size_index];

            $angle = ((rand(0, ($letter_precession * 2)) - $letter_precession) + 360) % 360;

            $x = $padding_left + ($box_width * $i);
            $y = $padding_top + $size + (($box_height - $size) / 2);

            $color_index = (rand(0, $fg_colors_count));
            $color = $fg_colors[$color_index];
            $color = imagecolorallocate($this->captcha_image, $color[0], $color[1], $color[2]);

            $font_index = rand(0, $fonts_count);
            $font = $fonts[$font_index];

            imagettftext($this->captcha_image, $size, $angle, $x, $y, $color, $font, $this->captcha_letters[$i]);
        }
    }

    public function captcha_get_random_letters()
    {
        extract($this->captcha_ini);

        $letters = $this->captcha_split($letters);
        $letters_max = (count($letters) - 1);

        for ($i = 0; $i < $letters_no; ++$i) {
            $letter_index = rand(0, $letters_max);
            $rtn_val[] = $letters[$letter_index];
        }

        return $rtn_val;
    }

    public function captcha_split($s)
    {
        $a = @preg_split('/\s?,\s?/', $s, -1, PREG_SPLIT_NO_EMPTY);
        if (is_array($a)) {
            return $a;
        }
    }

    public function captacha_ini()
    {
        return '
; size of the resulting image
width               = 120
height              = 30
padding             = 5,5,8,10

; ammount of noise and dots vs. lines ratio (may vary from 0 to 100)
noise               = 30
dust_vs_scratches   = 90

; how many & which letters. $letter_precession means the absolute value of the angle
letters_no          = 4
letters             = a,b,c,d,e,f,g,h,m,n,p,q,r,s,w,x,y,z,A,B,D,E,F,H,M,N,P,Q,R,S,W,X,Y,Z
letter_precession   = 15

; if $use_local_fonts == 1, then the fonts from APP_ROOT/fonts are used
use_local_fonts     = 1
font_size           = 20,20,20
fonts               = arena_condensed, ecceb

    ; how to compare the resulting string
case_sensitive      = 0

; there are two bg colors. second is used to add some noise before the letters
    ; are printed, the first for filling the bg and for adding some noise after
bg_color_1          = 10,10,10
bg_color_2          = 128,128,128

    ; you can use as many fg colors as you wish
[fg_colors]
fg_color_1          = 255,185,0
fg_color_2          = 231,231,231
fg_color_3          = 255,255,255
fg_color_4          = 255,221,0';
    }

    public function captcha_put_md5_into_session()
    {
        extract($this->captcha_ini);
        $string = implode('', $this->captcha_letters);
        $string = mb_strtolower($string);
        $string = md5($string);
        $_SESSION['captcha'] = $string;
    }
}
