/**
 * InputfieldCroppableImage
 *
 */

$(function () {

    'use strict';

    /**
     * Croppable image & warning tooltip
     * @var $elements, undefined/object, jquery collection object with list items
     *
     */
    var croppableTooltip = function ($elements) {

        if ($elements === undefined) {
            $elements = $(".InputfieldCroppableImage .cropLinks a");
        }

        // hover image tooltip
        function showImage($link, json) {
            var $cropWrap = $("<div class='crop-border'></div>"),
                timestamp = new Date().getTime();

            $cropWrap.append("<img src='" + $link.data('image') + "?t=" + timestamp + "'>");
            $cropWrap.append("<div class='cropInfo'>" + json.width + "×" + json.height + "</div>");
            return $cropWrap;
        }

        $elements.tooltip({
            items: "a",
            content: function () {
                var $link = $(this),
                    suffix = $link.data('suffix'),
                    json = config.CroppableImage.crops[suffix];

                // return content
                if ($link.data('croppable')) {
                    return showImage($link, json);
                }

                return "<div class='cropWarning'>" + $link.data('warning') + "</div>";
            },
            show: {
                effect: "fade",
                delay: 330,
                duration: 100
            },
            open: function (event, ui) {

                if ($(this).data('croppable')) {
                    //$(ui.tooltip).css({'min-width': $(this).data('width')});
                    $(ui.tooltip).css({'max-width': 260});
                } else {
                    $(ui.tooltip).addClass('warning');
                }
            },
            tooltipClass: "croppableTooltip",
            track: true,
            position: {
                my: "center bottom-25"
            }
        });
    };


    /* Croppable image tooltip  */
    croppableTooltip();

});





    /**
     * toggle Thumbnails displaymode between cover and contain
     */
    function croppableToggleThumbs(parentID) {
        var thumbs = $('#wrap_' + parentID + ' .gridImage__overflow > img');
        if(thumbs.first().hasClass('ciThumbnailContain')) {
            $.each(thumbs, function(e) {
                $(thumbs[e]).removeClass('ciThumbnailContain');
            });
        } else {
            $.each(thumbs, function(e) {
                $(thumbs[e]).addClass('ciThumbnailContain');
            });
        }
    }


    /**
     * show or hide the Images Basename (shortened)
     */
    function croppableToggleBasename(parentID) {
        var thumbs = $('#wrap_' + parentID + ' div.croppableImageBasename');
        var mode = thumbs.first().css('display') == 'none' ? 'block' : 'none';
        $.each(thumbs, function(e) {
            $(thumbs[e]).css('display', mode);
        });
    }


    /**
     * handle basename filter textinput
     */
    function croppableRegisterFilterBasename(parentID) {
        parentID = '#' + parentID;
        $(parentID + ' input.croppablefilter').keyup(function(event) {
            var cleaner =  /[^a-z0-9_-]/gi;
            var filter = $('input.croppablefilter').val();
            if(filter.search(cleaner) > -1) {
                filter = filter.replace(cleaner, '');
                $(parentID + ' input.croppablefilter').val(filter);
            }
            if((event.keyCode == 45 || event.keyCode == 46 || event.keyCode == 8) ||
               (event.keyCode >= 48 && event.keyCode <= 57) ||
               (event.keyCode >= 65 && event.keyCode <= 90) ||
               (event.keyCode >= 96 && event.keyCode <= 105) ||
               (event.keyCode == 163 || event.keyCode == 173))
            {
                var thumbs = $(parentID + ' div.croppableImageBasename');
                var suche = new RegExp(filter, 'gi');
                var griditemSel = 'li.ImageOuter.gridImage';
                $.each(thumbs, function(e) {
                    var t = thumbs[e];
                    var haystack = t.getAttribute('data-basename');
                    var elemId = t.closest(griditemSel).id;
                    if(0 == filter.length || haystack.match(suche)) {
                        $('#' + elemId).show();
                    } else {
                        $('#' + elemId).hide();
                    }
                });
            }
        });
    }

