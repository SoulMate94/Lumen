<?php

Facade 类参考#

在下面你可以找到每个 Facade 类及其对应的底层类。这是一个查找给定 Facade 类 API 文档的工具。服务容器绑定 的可用键值也包含在内。

Facade#
	类##   --服务器绑定
=============================Facade=================================

App#
	Illuminate\Foundation\Application  --app

Artisan#
	Illuminate\Contracts\Console\Kernel  --artisan

Auth#
	Illuminate\Auth\AuthManager  --auth

Blade#
	Illuminate\View\Compilers\BladeCompiler  --balde.compiler

Bus#
	Illuminate\Contracts\Bus\Dispatcher

Cache#
	Illuminate\Cache\Repository  --cache

Config#
	Illuminate\Config\Repository  --config

Cookie#
	Illuminate\Cookie\CookieJar  --cookie

Crypt#
	Illuminate\Encryption\Encrypter  --encrypter

DB#
	Illuminate\Database\DatabaseManager  --db

DB(instance)#
	Illuminate\Database\Connection

Event#
	Illuminate\Events\Dispatcher  --events

File#
	Illuminate\Filesystem\Filesystem  --files

Gate#
	Illuminate\Contracts\Auth\Access\Gate

Hash#
	Illuminate\Contracts\Hashing\Hasher  --hash

Lang#
	Illuminate\Translation\Translator  --translator

Log#
	Illuminate\Log\Writer  --log

Mail#
	Illuminate\Mail\Mailer  --mailer

Notification#
	Illuminate\Notification\ChannelManager

Password#
	Illuminate\Auth\Passwords\PasswordBrokerManager  --auth.password

Queue#
	Illuminate\Queue\QueueManager  --queue

Queue(instance)#
	Illuminate\Contracts\Queue\Queue  --queue

Queue(Base Class)#
	Illuminate\Queue\Queue

Redirect#
	Illuminate\Routing\Redirector  --redirect

Redis#
	Illuminate\Redis\Database  --redis

Request#
	Illuminate\Http\Request  --request

Response#
	Illuminate\Contracts\Routing\ResponseFactory

Route#
	Illuminate\Routing\Router  --router

Schema#
	Illuminate\Database\Schema\Blueprint

Session#
	Illuminate\Session\SessionManager  --session

Session(instance)#
	Illuminate\Session\Store

Storage#
	Illuminate\Contracts\Filesystem\Factory  --filesystem

URL#
	Illuminate\Routing\UrlGenerator  --url

Validator#
	Illuminate\validation\Factory  --validator

Validator(instance)#
	Illuminate\validation\Validator

View#
	Illuminate\View\Factory  --view

View(instance)#
	Illuminate\View\View



契约参考#
下表提供了所有 Laravel 契约及其对应的 Facade：

=======================契约参考=======================================

Contract			References Facade
Illuminate\Contracts\Auth\Factory  --Auth

Illuminate\Contracts\Auth\PasswordBroker  --Password

Illuminate\Contracts\Bus\Dispatcher  --Bus

Illuminate\Contracts\Broadcasting\Broadcater

Illuminate\Contracts\Cache\Repository  --Cache

Illuminate\Contracts\Cache\Factory  --Cache::driver()

Illuminate\Contracts\Config\Repository  --Config

Illuminate\Contracts\Container\Container  --App

Illuminate\Contracts\Cookie\Factory  --cookie

Illuminate\Contracts\Cookie\QueueingFactory  --Cookie::queue()

Illuminate\Contracts\Encryption\Encrypter --Crypt

Illuminate\Contracts\Events\Dispatcher  --Event

Illuminate\Contracts\Filesystem\Cloud  --File

Illuminate\Contracts\Filesystem\Factory  --File

Illuminate\Contracts\Foundation\Application  -App

Illuminate\Contracts\Hashing\Hasher  --Hash

Illuminate\Contracts\Logging\Log  --Log

Illuminate\Contracts\Mail\MailQueue  --Mail::queue()

Illuminate\Contracts\Mail\Mailer  --Mail

Illuminate\Contracts\Queue\Factory  --Queue::driver()

Illuminate\Contracts\Queue\Queue  --Queue

Illuminate\Contracts\Redis\Factory  --Redis

Illuminate\Contracts\Routing\Register  --Route

Illuminate\Contracts\Routing\ResponseFactory  --Response

Illuminate\Contracts\Routing\UrlGenerator  --URL

Illuminate\Contracts\Support\Arrayable

Illuminate\Contracts\Support\Jsonable

Illuminate\Contracts\Validation\Factory  --Validator::make()

Illuminate\Contracts\Validator

Illuminate\Contracts\View\Factory  --View::make()

Illuminate\Contracts\View\View
