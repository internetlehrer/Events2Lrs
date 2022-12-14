if(undefined === window.Events2LrsLoaded) {

    window.Events2LrsLoaded = true;

    Events2Lrs.H5P = {};

    Events2Lrs.getScript('ext.H5P.lookupLib.js');

    window.document.addEventListener('readystatechange', function (ev) {

        let parentWin = window;

        let parentDocument = window.document; //e.target; // parentWin.document;

        let UrlH5PModule = window.location.href; // parentWin.location.href;

        if ('complete' === parentDocument.readyState && typeof parentWin.H5P !== 'undefined' && parentWin.H5P.externalDispatcher) {

            let availCIDs = parentWin.H5PIntegration.contents;

            parentWin.H5P.externalDispatcher.on('xAPI', function (event) {

                let currCID = this.activeElement.getAttribute('data-content-id');

                let lib = availCIDs['cid-' + currCID].library;

                console.dir(availCIDs['cid-' + currCID].library);
                //console.dir(this.activeElement.getAttribute('data-content-id'));
                //console.dir(H5P.XAPIEvent.prototype.getContentXAPIId(parentWin.H5P));

                let h5pXapiStatement = event.data.statement;

                h5pXapiStatement.UrlH5PModule = UrlH5PModule;

                //h5pXapiStatement.context.contextActivities.category[0].id = Events2Lrs.H5P.lookupLib(event);

                if(undefined === h5pXapiStatement.context.contextActivities.category) {

                    h5pXapiStatement.context.contextActivities.category = [];

                    h5pXapiStatement.context.contextActivities.category[0] = {

                        id: 'http://h5p.org/libraries/' + availCIDs['cid-' + currCID].library, // Events2Lrs.H5P.lookupLib(event),

                        objectTyp: 'Activity'

                    }

                }

                $.ajax({
                    type: 'POST',
                    url: Events2Lrs.urlRouterGUI,
                    dataType: 'json',
                    headers: {'xAPI': 'statement'},
                    data: h5pXapiStatement,
                });

                console.log('Events2Lrs H5P Action');

                console.dir(event.data.statement);

            }, this);
        }
    });

}

