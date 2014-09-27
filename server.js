var ZMR = ZMR || {};


(function ($) {

    ZMR.API = {

        RELEASED: 'released',
        LOCKED: 'locked'

    };

    /**
     * Abstract socket cli
     * @type {{session: null, SERVER_URL: string, clientColor: null, sessionId: null, formLocked: boolean, init: Function, initEvents: Function, formEdit: Function, clickReleaseEvent: Function, clickLockEvent: Function, connect: Function, connectedEvent: Function, closedEvent: Function, subscribeEvent: Function, lockForm: Function, releaseForm: Function, log: Function}}
     */
    ZMR.WebSocketClient = {

        session: null,
        SERVER_URL: 'ws://localhost:8080',
        clientColor: null,
        sessionId: null,
        formLocked: false,


        init: function () {

            this.session = this.connect();
            this.box = $('#box');

            this.initEvents();


        },

        initEvents: function () {

            $('#releaseForm').on('click', $.proxy(this.clickReleaseEvent, this));
            $('#lockForm').on('click', $.proxy(this.clickLockEvent, this));
            this.box.on('focus input', $.proxy(this.formEdit, this));

        },

        formEdit: function (e) {

            var target = $(e.currentTarget);

            if (!this.formLocked) {

                this.lockForm('zmr:forms:' + target.attr('id'));

            }

        },

        clickReleaseEvent: function (e) {

            e.preventDefault();

            this.releaseForm('zmr:forms:box');

        },

        clickLockEvent: function (e) {

            e.preventDefault();

            this.lockForm('zmr:forms:box');

        },

        /**
         * Connect to websocket and return session instance
         * @returns {window.ab.Session}
         */
        connect: function () {

            return new ab.Session(
                this.SERVER_URL,
                $.proxy(this.connectedEvent, this),
                $.proxy(this.closedEvent, this),
                {
                    'skipSubprotocolCheck': true,
                    'maxRetries': 60,
                    'retryDelay': 2000
                }
            );

        },

        connectedEvent: function () {

            this.sessionId = this.session._session_id;

            this.session.subscribe('zmr:forms:box', $.proxy(this.subscribeEvent, this));
        },

        closedEvent: function () {
            console.warn('WebSocket connection closed');
        },

        subscribeEvent: function(form, payload) {


            switch (payload.status) {

                case ZMR.API.RELEASED:

                    this.box.css('border', 'none');
                    ZMR.WebSocketClient.formLocked = false;
                    $('.actions').hide();
                    break;

                case ZMR.API.LOCKED:

                    ZMR.WebSocketClient.box.css('border', '5px solid red');
                    ZMR.WebSocketClient.box.css('disabled', 'true');
                    ZMR.WebSocketClient.formLocked = true;
                    break;

            }


        },

        lockForm: function (formName) {

            this.session.call('lockForm', {
                locker: this.sessionId,
                form: formName
            }).then(
                function (response) {
                    ZMR.WebSocketClient.formLocked = true;
                    ZMR.WebSocketClient.box.css('border', '5px solid green');

                    $('.actions').show();
                },
                function (response) {
                    ZMR.WebSocketClient.box.css('border', '5px solid red');
                    ZMR.WebSocketClient.box.css('disabled', 'true');
                    ZMR.WebSocketClient.formLocked = true;
                }
            );


        },
        releaseForm: function (formName) {

            this.session.call('releaseForm', {
                form: formName
            }).then(
                function (response) {
                },
                function (response) {
                }
            );


        },

        log: function (message) {

            this.box.append('<p>' + message + '</p>');

        }

    }

    $(function () {

        ZMR.WebSocketClient.init();

    });


}(jQuery));