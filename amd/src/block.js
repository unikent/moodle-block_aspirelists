// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/*
 * @package    block_aspirelists
 * @copyright  2015 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 /**
  * @module block_aspirelists/block
  */
define(['core/ajax'], function(ajax) {
    return {
        init: function(courseid) {
            $("#aspire_block_contents").removeClass("hidden");

            // Call AJAX webservice to search.
            var promises = ajax.call([{
                methodname: 'aspirelists_get_lists',
                args: {
                    courseid: courseid
                }
            }]);

            promises[0].done(function(response) {
                $("#aspire_block_contents").html("");
                $.each(response, function(i, o) {
                    var container = $("#aspire_block_contents #aspire_" + o.campus);
                    if (container.length == 0) {
                        container = $("#aspire_block_contents").append('<div id="aspire_' + o.campus + '"></div>');
                        container = $("#aspire_block_contents #aspire_" + o.campus);
                        container.append("<h3>" + o.campus + "</h3>");
                    }

                    var extras = '';
                    if (o.items > 0) {
                        var itemnoun = (o.items == 1) ? "item" : "items";
                        extras += ' (' + o.items + ' ' + itemnoun + ')';
                    }

                    if (o.lastupdated.length > 0) {
                        extras += ', last updated: ' + o.lastupdated;
                    }

                    container.append('<p><a href="' + o.url + '" target="_blank">' + o.name + '</a>' + extras + '</p>');
                });
            });

            promises[0].fail(function(response) {
                $("#aspire_block_contents").html(response.errorcode);
            });
        }
    };
});