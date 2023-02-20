<?php
/**
 * Project web-builder-sdk
 * Created by PhpStorm
 * User: 713uk13m <dev@nguyenanhung.com>
 * Copyright: 713uk13m <dev@nguyenanhung.com>
 * Date: 02/07/2022
 * Time: 01:32
 */

namespace nguyenanhung\Platforms\WebBuilderSDK\Language\Packages;

use RuntimeException;

/**
 * Class MultiLang
 *
 * @package   nguyenanhung\WebBuilderSDK\Repository
 * @author    713uk13m <dev@nguyenanhung.com>
 * @copyright 713uk13m <dev@nguyenanhung.com>
 */
class MultiLang
{
    protected $lang;
    protected $USE_COOKIES;
    protected $lang_file;
    protected $dictionary;
    protected $languages_dir        = __DIR__ . '/language/';
    protected $DEFAULT_LANGUAGE     = 'EN';
    protected $untranslated_logging = true;
    protected $last_translated      = false;

    public function __construct($use_cookies = false, $untranslated_logging = true)
    {
        $this->USE_COOKIES = $use_cookies;
        $this->untranslated_logging = $untranslated_logging;

        if ($this->USE_COOKIES) {
            //If USE_COOKIES is true we try to load the language code from the cookie.
            $this->lang = isset($_COOKIE['lang']) ? $_COOKIE['lang'] : '';
        } else {
            //If USE_COOKIE is false, we try to load the language code from the session.
            $this->lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : '';
        }

        if (empty($this->lang)) {

            $this->setLanguage($this->DEFAULT_LANGUAGE);
        }

    }

    //The Main Translate Function.
    //Parameters for the translation string are nested in double brackets e.g. 'Hello {{World}}!' can be represented in the language dictionary as '{{1}} Hello !', which would produce the string 'World Hello!';
    public function tr($word)
    {
        $lookup_word = strtolower($word);
        $lookup_word = preg_replace('/{{.*}}/', '', $lookup_word);

        if (isset($this->dictionary) & isset($this->dictionary[$lookup_word])) {

            $trWord = $this->dictionary[$lookup_word];

            $arr = array();
            $arr2 = array();

            preg_match_all("/{{([0-9]+)}}/", $trWord, $arr);

            preg_match_all("/{{(.*?)}}/", $word, $arr2);

            foreach ($arr[1] as $key => $value) {

                $val = (int) $value - 1;

                if (isset($arr2[1][$val])) {

                    $trWord = str_replace('{{' . $value . '}}', $arr2[1][$val], $trWord);
                }
            }

            $this->last_translated = true;

            return $trWord;

        }

        $this->not_yet_translated($lookup_word);

        $word = str_replace(array("{{", "}}"), '', $word);

        $this->last_translated = false;

        return $word;

    }

    public function set_directory($path)
    {
        return ($this->languages_dir = $path);
    }

    public function set_untranslated_logging($bool = false)
    {

        return ($this->untranslated_logging = $bool);
    }

    private function not_yet_translated($lookup_word)
    {

        if (!file_exists($this->languages_dir) & $this->untranslated_logging) {
            $concurrentDirectory = $this->languages_dir;
            if (!mkdir($concurrentDirectory, 0777, true) && !is_dir($concurrentDirectory)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }
        }

        if (!$this->USE_COOKIES & !file_exists($this->lang_file)) {

            $example_contents = "<?php\n\n\n$" . strtoupper($this->lang) . "=[\n    'example text'=>'例文',\n     ];";

            if ($this->untranslated_logging) {

                $example_contents .= "\n\n\n/** Not Yet Translated **/\n\n// " . $lookup_word;

                file_put_contents($this->lang_file, $example_contents);
            }

            return;
        }


        if (!$this->USE_COOKIES & $this->untranslated_logging) {

            $contents = file_get_contents($this->lang_file);

            if (strpos($contents, '// ' . $lookup_word) === false) {

                file_put_contents($this->lang_file, "\n// " . $lookup_word, FILE_APPEND);
            }
        }
    }

    public function setLanguage($language_code, $duration = 604800)
    {

        // Cookie Duration defaults to 1 week.

        $language_code = '' . $language_code;

        if (strlen($language_code) > 2) {

            //Only two-character language codes are accepted.
            $language_code = $this->DEFAULT_LANGUAGE;
        }

        $this->lang = strtoupper($language_code);

        if ($this->USE_COOKIES) {

            setcookie('lang', $this->lang, $duration);

        } else {

            if (!isset($_SESSION)) {

                session_start();
            }

            $_SESSION['lang'] = $this->lang;
        }

        $this->lang_file = $this->languages_dir . $this->lang . '.php';

        if (file_exists($this->lang_file)) {

            require $this->lang_file;

            $this->dictionary = ${$this->lang};
        }
    }

    public function translated()
    {

        return $this->last_translated;
    }

    public function language()
    {

        return $this->lang;
    }
}
