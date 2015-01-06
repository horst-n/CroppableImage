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
                        $(ui.tooltip).css({'min-width': $(this).data('width')});
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
        },

        /**
         * Build the edit inputfields for grid view.
         *
         */
        croppableInputProxy = function ($addedItems) {

            var $item,
                $items,
                $fields,
                $label,
                $field,
                $header,
                $markup,
                $itemEdit,
                $temp,
                $wrapper,
                id,
                value,
                name,
                hasLanguageSupport;

            // Is item in viewport?
            function inViewPort($el) {
                return $el.offset().top >= 0
                    && $el.offset().left >= 0
                    && ($el.offset().top + $el.outerHeight()) <= $(window).height()
                    && ($el.offset().left + $el.outerWidth()) <= $(window).width();
            }

            // Collect input fields and build input proxy
            function initialize(li) {

                $item = $(li).closest('li');
                hasLanguageSupport = !!($item.find('.LanguageSupport').length);
                $fields = $();
                $markup = $("<div class='editCroppableItem'></div>").hide();

                if (hasLanguageSupport) {
                    $markup.append("<div class='langTabs'><ul></ul></div>");
                }

                $($item.html()).find(':input').filter(function () {
                    return !(this.className === 'InputfieldFileSort' || this.type === 'checkbox');

                }).each(function () {
                    $field = $(this);
                    value = $("#" + $field.attr('id')).val();
                    name = $field.attr('name');
                    $field.removeAttr('id');
                    $field.attr('name', 'copy_' + name);
                    $field.attr('value', value);
                    $label = $(this).prev();
                    $fields = $fields.add($field);

                    if ($label.hasClass('LanguageSupportLabel')) {
                        id = 'langTab' + $label.attr('for').replace('description', '');
                        $temp = $markup.find('.langTabs');
                        $wrapper = $("<div class='InputfieldFileDescription LanguageSupport' id='" + id + "'></div>");
                        $wrapper.append($label);
                        $wrapper.append($field);
                        $temp.append($wrapper);
                        $temp.find('ul').append("<li><a href='#" + id + "'>" + $label.text() + "</a></li>");

                    } else {
                        $temp = $("<div class='InputfieldData'></div>");
                        $temp.append($label);
                        $temp.append($field);

                    }

                    $markup.append($temp);
                });

                $header = $item.find('.InputfieldItemHeader').clone();
                $header.children().remove('.InputfieldFileMove');
                $header.children().remove('.InputfieldFileDelete');
                $header.children().remove('.editCropItem');
                $header.append("<a class='close-item'><i class='fa fa-close'></i></a>");
                $markup.prepend($header);
                $markup.append($item.find('.cropLinks').clone());
                $markup.wrapInner("<div class='croppableItem ui-widget-content'></div>");

                // let setupLanguageTabs() build default language tabs
                if (hasLanguageSupport) {
                    $markup.find('.croppableItem').addClass('hasLanguageSupport');
                    // located: /wire/modules/LanguageSupport/LanguageTabs.js
                    setupLanguageTabs($markup);
                }

                // attach to DOM
                $item.closest('.InputfieldContent').append($markup);
                $itemEdit = $markup.find(".croppableItem");
                $itemEdit.css({ minWidth: 300});
                $markup.fadeIn(250);

                // When item not in the viewport handle it with jQuery
                if (!inViewPort($itemEdit)) {
                    $itemEdit.css({
                        position: 'fixed',
                        left: ($(window).width() / 2) - $itemEdit.width() / 2,
                        top: ($(window).height() / 2) - $itemEdit.height() / 2
                    });
                }

                $itemEdit.draggable({containment: 'body'});
            }

            // close Edit item
            function closeItem() {
                // close button
                $(".editCroppableItem .close-item, .InputfieldImageListToggle").on('click', function () {
                    $(this).closest('.editCroppableItem').click();
                });
                // grid toggle
                $(".InputfieldImageListToggle").on('click', function () {
                    $(this).closest('li').find('.editCroppableItem').click();
                });
                $('.editCroppableItem').on('click', function (event) {
                    var $clicked = $(this),
                        target = event.target || event.srcElement;

                    if (target.className === 'editCroppableItem') {
                        $clicked.children(':first').remove();
                        $clicked.fadeOut(function () {
                            $(this).remove();
                        });
                    }
                });
            }

            // User input proxy for :input fields
            function inputProxy() {
                $fields.each(function () {
                    $(this).on('input', function () {
                        name = $(this).attr('name').replace('copy_', '');
                        $item.find("[name='" + name + "']").val(this.value);
                    });
                });
            }

            return (function ($addedItems) {

                if (typeof $addedItems === 'object') {
                    $items = $addedItems.find('.editCropItem');
                } else {
                    $items = $(".InputfieldCroppableImage .editCropItem");
                }

                $items.on('click', function () {
                    initialize(this);
                    closeItem();
                    croppableTooltip();
                    inputProxy();
                });

            }($addedItems));
        },

        /**
         * Switch between Grid or List.
         *
         * @var gridMode, true/false/undefined
         */

        croppableGridSwitch = function (gridMode) {

            var $parent = $(".InputfieldCroppableImage"),
                $image = $parent.find(".base-image:first"),
                $header = $(".InputfieldCroppableImage .InputfieldHeader"),
                $toggle = $("<a class='InputfieldImageListToggle HideIfEmpty' href='#'></a>"),
                $toggleIcon = $("<i class='fa fa-th'></i>"),
                grid = {
                    x: $image.data('grid-x'),
                    y: $image.data('grid-y')
                };

            function isGrid() {
                return $parent.hasClass('InputfieldCroppableImageGrid');
            }

            function isThumbed() {
                return $parent.hasClass('InputfieldCroppableImageThumbs');
            }

            function setGridMode() {
                $toggleIcon.removeClass('fa-th').addClass('fa-list');
                $parent.addClass('InputfieldCroppableImageGrid');
                $parent.removeClass('InputfieldCroppableImageList');
                $parent.find(".InputfieldFileLink").css({
                    width: grid.x,
                    height: grid.y
                });

                $parent.find(".InputfieldFileItem").css({
                    width: grid.x,
                    height: grid.y
                });

                $parent.find(".InputfieldFileItem").each(function () {
                    var $item = $(this),
                        src = $item.find(".base-image:first").attr('src');

                    $item.find(".InputfieldFileLink").css({
                        backgroundImage: 'url(' + src + ')'
                    });
                });
            }

            function setListMode() {
                $parent.addClass('InputfieldCroppableImageList');
                $parent.removeClass('InputfieldCroppableImageGrid');
                $toggleIcon.removeClass('fa-list').addClass('fa-th');
                $parent.find(".InputfieldFileItem").css({
                    width: '',
                    height: ''
                });

                $parent.find(".InputfieldFileLink").css({backgroundImage: ''});

                if (isThumbed()) {
                    $parent.find(".InputfieldFileLink").css({height: ''});
                } else {
                    $parent.find(".InputfieldFileLink").css({width: '', height: ''});
                }
            }

            function initialize() {

                if ($header.find('.InputfieldImageListToggle').length === 0) {
                    $toggle.append($toggleIcon);
                    $header.append($toggle);

                    // set grid or tell it is list
                    if (!!$parent.find(".InputfieldImageDefaultGrid").length) {
                        setGridMode();
                    } else {
                        $parent.addClass('InputfieldCroppableImageList');
                    }

                    // thumbnail or full image class
                    if (!!$(".InputfieldCroppableImage .adminThumb").length) {
                        $parent.addClass('InputfieldCroppableImageThumbs');
                    } else {
                        $parent.addClass('InputfieldCroppableImageFull');
                    }
                }
            }

            return (function (gridMode) {
                if (gridMode === true) {
                    setGridMode();

                } else if (gridMode === false) {
                    setListMode();

                } else if (gridMode === undefined) {
                    initialize();
                    $toggle.on('click', function (e) {
                        e.preventDefault();
                        if (isGrid()) {
                            setListMode();
                        } else {
                            setGridMode();
                        }
                    });
                }

            }(gridMode));
        },

        /**
         * Copy of Ryan's magnificOptions object
         *
         */

        magnificOptions = {
            type: 'image',
            closeOnContentClick: true,
            closeBtnInside: true,
            image: {
                titleSrc: function (item) {
                    return item.el.find('img').attr('alt');
                }
            },
            callbacks: {
                open: function () {
                    // for firefox, which launches Magnific after a sort
                    if ($(".InputfieldCroppableImage .InputfieldFileJustSorted").length) { this.close(); }
                }
            }
        };





    /**
     * Controllers
     *
     */

    /* Croppable image tooltip  */
    croppableTooltip();
    /* Edit inputfields for grid view */
    croppableInputProxy();
    /* Switch between Grid or List */
    croppableGridSwitch();

    /**
     * Re-initialize newly added items.
     *
     */
    $('.InputfieldCroppableImage .InputfieldFileList').on('AjaxUploadDone', function () {

        var $list = $(this),
            $addedItems = $();

        $list.find('.InputfieldFileLink').filter(function () {
            return !!($(this).css('background-image') === 'none');
        }).each(function () {
            var $link = $(this);
            // for croppableInputProxy
            $addedItems = $addedItems.add($link.closest('li'));
            // re-attach magnific
            $link.magnificPopup(magnificOptions);
        });

        // property: (bool) true === grid
        croppableGridSwitch(!!($list.closest('.InputfieldCroppableImageGrid').length));
        croppableInputProxy($addedItems);
        croppableTooltip();
    });

    /**
     * HTML5 image(s) dropped
     *
     */
    $('.InputfieldCroppableImage .InputfieldContent').on('drop', function () {

        var sizes = config.CroppableImage.grid,
            x = sizes.x,
            y = sizes.y;

        $(document).on('DOMNodeInserted', function (event) {
            if ($(event.target).hasClass('AjaxUpload')) {

                $(event.target).css({
                    width: x,
                    height: y + 1 // +1 px border
                });

                // remove error messages
                var $error = $(event.target).children('.ui-state-error');

                if ($error.length) {
                    $error.parent().on('click', function () {
                        $(this).hide(function () { $(this).remove(); });
                    });
                }
            }
        });
    });

});
