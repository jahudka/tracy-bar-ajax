<?php
/**
 * Created by PhpStorm.
 * User: danik
 * Date: 14/12/14
 * Time: 23:49
 */

use Tracy\Debugger;

class AjaxBar {

    private static $registered = false;

    private static $enabled = true;

    private static $contentType = null;


    public static function register() {
        if (self::$registered) {
            return;

        }

        self::$registered = true;

        if (Debugger::isEnabled()) {
            die('Ajax diagnostic helper must be registered before Tracy is enabled');

        }

        register_shutdown_function([get_called_class(), '_shutdownHandler']);
        ob_start();

    }

    public static function disable() {
        self::$enabled = false;

    }

    public static function _shutdownHandler() {
        if (!Debugger::isEnabled() || !self::$enabled || self::isHtmlMode()) {
            return;

        }


        // this is needed to force Tracy Debugger bar into thinking we're rendering as usual
        if (preg_match('#^(Content-Type):(.+)$#im', implode("\n", headers_list()), $m)) {
            header_remove($m[1]);
            self::$contentType = trim($m[2]);

        }

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            $_SERVER['HTTP_X_REQUESTED_WITH'] = '';

        }

        self::$enabled = false; // prevent accidentally running twice
        register_shutdown_function([get_called_class(), '_barHandler']);
        ob_start();

    }

    public static function _barHandler() {
        $bar = ob_get_clean();

        if (headers_sent()) {
            return;

        }

        if (isSet(self::$contentType)) {
            header('Content-Type: ' . self::$contentType);

        }

        foreach (str_split(base64_encode(@json_encode($bar)), 4990) as $k => $v) { // intentionally @
            header("AjaxBar-$k:$v");

        }
    }

    private static function isHtmlMode() {
        return empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        && PHP_SAPI !== 'cli'
        && !preg_match('#^Content-Type: (?!text/html)#im', implode("\n", headers_list()));

    }

}