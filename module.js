M.block_aspirelists = {
    init: function(Y, id) {
        YUI().use("node", "io", "dump", "json-parse", function(Y) {

            var aspirelists = Y.one(".block_aspirelists .content");

            Y.one(".block_aspirelists .block_loading").setStyle("display", "block");

            Y.io(M.cfg.wwwroot + "/blocks/aspirelists/ajax.php", {
                timeout: 3000,
                method: "GET",
                data: {
                    id: id,
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