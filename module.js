M.block_aspirelists = {
    init: function(Y, id, shortname) {
        YUI().use("node", "io", "dump", "json-parse", function(Y) {

            var aspirelists = Y.one("#aspirelists-block");

            aspirelists.setHTML(M.str.block_aspirelists.ajaxwait);

            Y.io(M.cfg.wwwroot + "/blocks/aspirelists/ajax.php", {
                timeout: 3000,
                method: "GET",
                data: {
                    id: id,
                    shortname: shortname,
                    sesskey: M.cfg.sesskey
                },

                on: {
                    success: function (x, o) {
                        // Process the JSON data returned from the server
                        try {
                            var data = Y.JSON.parse(o.responseText);
                            aspirelists.setHTML(data.text);
                        }
                        catch (e) {
                            aspirelists.setHTML(M.str.block_aspirelists.ajaxerror);
                            return;
                        }
                    },

                    failure: function (x, o) {
                        aspirelists.setHTML(M.str.block_aspirelists.ajaxerror);
                    }
                }
            });
        });
    }
}