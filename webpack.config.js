const Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')

    // Entrées principales (tu peux en ajouter)
    .addEntry('app', './assets/front/js/app.js')

    // ====================================
    // BACKEND (Admin)
    // ====================================
    .addEntry('admin', './assets/admin/js/admin.js')
    .addStyleEntry('admin-styles', './assets/admin/styles/admin.scss')

    // Active SCSS, Babel, etc.
    .enableSassLoader()
    .enablePostCssLoader()
    .enableSingleRuntimeChunk()
    .splitEntryChunks()
    .cleanupOutputBeforeBuild()
    .enableVersioning(Encore.isProduction())

    // Active Stimulus si utilisé
    .enableStimulusBridge('./assets/controllers.json')
;

module.exports = Encore.getWebpackConfig();
