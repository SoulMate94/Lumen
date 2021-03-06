{
    "name": "laravel/lumen",
    "description": "The Laravel Lumen Framework.",
    "keywords": ["framework", "laravel", "lumen"],
    "license": "MIT",
    "type": "project",
    "require": {
        "php": "^7.0",
        "laravel/lumen-framework": "5.4.*",
        "vlucas/phpdotenv": "~2.2",
        "firebase/php-jwt": "^5.0",
        "symfony/var-dumper": "^3.3",
        "predis/predis": "^1.1",
        "illuminate/redis": "^5.4",
        "qiniu/php-sdk": "^7.1",
        "maatwebsite/excel": "~2.1.0",
        "jpush/jpush": "^3.5",
        "flc/dysms": "^1.0",
        "latrell/alipay": "dev-master"
    },
    "require-dev": {
        "fzaninotto/faker": "~1.4",
        "phpunit/phpunit": "~5.0",
        "mockery/mockery": "~0.9"
    },
    "autoload": {
        "psr-4": {
            "App\\": "app/"
        }
    },
    "autoload-dev": {
        "classmap": [
            "tests/",
            "database/"
        ]
    },
    "scripts": {
        "post-root-package-install": [
            "php -r \"copy('.env.example', '.env');\""
        ]
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
