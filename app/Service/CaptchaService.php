<?php

namespace App\Service;

/**
 * Laravel 5 Captcha package
 *
 * @copyright Copyright (c) 2015 MeWebStudio
 * @version 2.x
 * @author Muharrem ERİN
 * @contact me@mewebstudio.com
 * @web http://www.mewebstudio.com
 * @date 2015-04-03
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */

use Mews\Captcha\Captcha;
use Cache;
/**
 * Class Captcha
 * @package Mews\Captcha
 */
class CaptchaService extends Captcha
{

    /**
     * Generate captcha text by id
     * @param $captchaId
     * @return string
     */
    protected function generateById($captchaId)
    {
        $characters = str_split($this->characters);
        $bag = '';
        for($i = 0; $i < $this->length; $i++)
        {
            $bag .= $characters[rand(0, count($characters) - 1)];
        }
        Cache::put('captcha'.$captchaId,$bag,5);
        return $bag;
    }

    /**
     * Create captcha image by id
     * @param string $config
     * @param $captchaId
     * @return mixed
     */
    public function createById($config = 'default',$captchaId)
    {
        $this->backgrounds = $this->files->files(app_path('../vendor/mews/captcha/assets/backgrounds'));
        $this->fonts = $this->files->files(app_path('../vendor/mews/captcha/assets/fonts'));
        $this->fonts = array_values($this->fonts); //reset fonts array index

        $this->configure($config);
        $this->text = $this->generateById($captchaId);
        $this->canvas = $this->imageManager->canvas(
            $this->width,
            $this->height,
            $this->bgColor
        );

        if ($this->bgImage)
        {
            $this->image = $this->imageManager->make($this->background())->resize(
                $this->width,
                $this->height
            );
            $this->canvas->insert($this->image);
        }
        else
        {
            $this->image = $this->canvas;
        }

        if ($this->contrast != 0)
        {
            $this->image->contrast($this->contrast);
        }

        $this->text();

        $this->lines();

        if ($this->sharpen)
        {
            $this->image->sharpen($this->sharpen);
        }
        if ($this->invert)
        {
            $this->image->invert($this->invert);
        }
        if ($this->blur)
        {
            $this->image->blur($this->blur);
        }

        return $this->image->response('png', $this->quality);
    }

    /**
     * Captcha check by id
     * @param $value
     * @param $captchaId
     * @return bool
     */
    public function checkById($value,$captchaId)
    {
        $captcha = 'captcha'.$captchaId;
        if ( ! $this->session->has($captcha))
        {
            return false;
        }

        $key = $this->session->get($captcha.'.key');

        if ( ! $this->session->get($captcha.'.sensitive'))
        {
            $value = $this->str->lower($value);
        }

        $this->session->remove($captcha);

        return $this->hasher->check($value, $key);
    }

}
