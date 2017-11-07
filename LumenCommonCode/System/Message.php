<?php

// All messages in API system

============================Routes=============================================

$app->group([
    'middleware' => [
        'ak_sk_auth',
    ],
], function () use ($app) {
    $app->get('/sys_err_msg.json', 'System\Message@get');
});

======================app\Http\Controllers\System\Message.php==================

namespace App\Http\Controllers\System;

use Illuminate\Http\Request;

class Message implements \ArrayAccess
{
    public $lang  =  null;
    public $text  =  [];

    protected function path()
    {
        return resource_path().'sys_msg/'.$this->lang;
    }

    public function get(Request $req)
    {
        $this->lang = $req->get('lang') ?? $this->getDefaultLang();

        return response()->json([
            'err' => 0,
            'msg' => 'ok',
            'dat' => $this->load(),
        ]);
    }

    public function offsetExists($offset)
    {
        return isset($this->text[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->text[$offset])
        ? $this->text[$offset]
        : (
            ('zh' == $this->lang)
            ? '服务繁忙,请稍后再试'
            : 'Service is busy or temporarily unavailable.'
        );
    }

    public function offsetSet($offset, $value):void
    {
    }

    public function offsetUnset($offset): void
    {
    }

    public function msg($lang): self
    {
        $this->load($lang);

        return $this;
    }

    public function load($lang = null)
    {
        if (! $this->lang) {
            $this->lang = $lang ?? $this->getDefaultLang();
        }

        $dat = [];

        if ($fsi = $this->getFilesystemIterator()) {
            foreach ($fsi as $file) {
                if ($file-isFile() && 'php' == $file->getExtension()) {
                    $_dat = include_once $file->getPathname();
                    if ($_dat && is_array($_dat)) {
                        $dat = array_merge($_dat, $dat);
                    }
                }
            }
        }

        return $this->text = $dat;
    }

    protected function getDefaultLang(): string
    {
        return 'zh';
    }

    protected function getFilesystemIterator()
    {
        if ($path = $this->path()) {
            if (! file_exists($path)) {
                $this->lang = 'zh';
                $path = $this->path();
                if (! file_exists($path)) {
                    return false;
                }
            }

            return new \getFilesystemIterator($path);
        }

        return false;
    }
}
