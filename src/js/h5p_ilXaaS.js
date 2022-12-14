

Events2Lrs = jQuery.extend({
    iliasHttpPath: '',
    pluginPath: '/Customizing/global/plugins/Services/Cron/CronHook/Events2Lrs/src/js/',
    H5P: {}
}, Events2Lrs);



jQuery.execEvents2LrsScript = function(scriptFile) {

    return jQuery.ajax({
        cache: false,
        dataType: "script",
        url: Events2Lrs.iliasHttpPath + Events2Lrs.pluginPath + scriptFile
    });

};


if(undefined === window.Events2LrsLoaded) {

    window.Events2LrsLoaded = true;

    window.document.addEventListener('readystatechange', function (ev) {

        let parentWin = window;

        let parentDocument = window.document; //e.target; // parentWin.document;

        let UrlH5PModule = window.location.href; // parentWin.location.href;

        if ('complete' === parentDocument.readyState && typeof parentWin.H5P !== 'undefined' && parentWin.H5P.externalDispatcher) {

            $.execEvents2LrsScript( 'lookupH5PLib.js' ).done(function( script, textStatus ) {

                parentWin.H5P.lookupLib = script;

            });

            parentWin.H5P.externalDispatcher.on('xAPI', function (event) {

                event.data.statement.UrlH5PModule = UrlH5PModule;

                $.ajax({
                    type: 'POST',
                    url: window.urlEvents2LrsRouterGUI,
                    dataType: 'json',
                    headers: {'xAPI': 'statement'},
                    data: event.data.statement,
                });

                console.log('Events2Lrs H5P Action');

                console.dir(event.data.statement);

            });
        }
    });

}

