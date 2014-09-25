var ZMR = ZMR || {};


(function ($) {

    ZMR.Server = {

        conn: null,
        SERVER_URL: 'ws://localhost:8080',
        clientColor: null,

        getRandomColor: function() {
            var letters = '0123456789ABCDEF'.split('');
            var color = '#';
            for (var i = 0; i < 6; i++ ) {
                color += letters[Math.floor(Math.random() * 16)];
            }
            return color;
        },

        init: function () {

            console.time('connection');

            var sess = new ab.Session('ws://78.8.167.114:8080',
                function() {
                    console.timeEnd('connection')
                    console.time('subscription');
                    sess.subscribe('zmr:forms', function(room, msg) {

                        console.timeEnd('subscription');
                        console.log(room, msg);


                    });

                },
                function() {
                    console.warn('WebSocket connection closed');
                },
                {'skipSubprotocolCheck': true}
            );


        },

        log: function (message) {

            this.box.append('<p>' + message + '</p>');

        }

    }

    $(function () {

        ZMR.Server.init();

    });


}(jQuery));