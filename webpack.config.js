var Encore = require('@symfony/webpack-encore');
var CopyWebpackPlugin = require('copy-webpack-plugin');

Encore
    // the project directory where compiled assets will be stored
    .setOutputPath('public/build/')
    // the public path used by the web server to access the previous directory
    .setPublicPath('/build')
    .cleanupOutputBeforeBuild()
    .enableSourceMaps(!Encore.isProduction())
    // uncomment to create hashed filenames (e.g. app.abc123.css)
    // .enableVersioning(Encore.isProduction())

    // uncomment to define the assets of the project
    .addEntry('js/app', [
        './assets/js/core.js',
        './assets/js/module/translate.js',
        './assets/js/module/php_obfuscator.js',
        './assets/js/module/http.js',
        './assets/js/module/guestbook.js',
        './assets/js/module/gist.js',
        './assets/js/module/archiver.js',
        './assets/js/module/file/edit.js',
        './assets/js/module/file/swiper.js',
        './assets/js/module/file/upload.js',
        './assets/js/module/file/view.js'
    ])
    .addStyleEntry('css/app', './assets/css/core.css')

    // uncomment if you use Sass/SCSS files
    // .enableSassLoader()

    // uncomment for legacy applications that require $/jQuery as a global variable
    // .autoProvidejQuery()createdSharedEntry
;

var config = Encore.getWebpackConfig();

// копируем файлы которые не используются в js/css, а напрямую в twig
config.plugins.push(new CopyWebpackPlugin([
    { from: './assets/resources/', to: 'resources'}
]));


module.exports = config;
