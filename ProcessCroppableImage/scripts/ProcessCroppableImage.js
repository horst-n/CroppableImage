// based on work by apeisa
// enhanced, extended and refactored by owzim

(function($, window, undefined) { // iffy for scoping

    $(function() { // dom loaded

        var $jcropTarget = $('#jcrop_target');

        // do nothing at all if jcrop element is not found
        if ($jcropTarget.length > 0) {

            // functions
            var
                jcropHandler,
                roundpx,
                getSingleCrop,
                updatePreviewScale,
                updatePreview,
                updateHiddenInputs;

            // elements
            var
                $preview = $('#preview'),
                $previewContainer = $('#preview-container'),
                $buttonShowPreview = $('#show_preview'),

                // hidden inputs
                $inputX1 = $('#x1'),
                $inputY1 = $('#y1'),
                $inputW = $('#w'),
                $inputH = $('#h'),
                $inputUpscaling = $("#upscaling"),

                $croppedWidth = $("#ci-cropped-width"),
                $croppedHeight = $("#ci-cropped-height"),

                $window = $(window);

            // misc vars
            var
                isFullPreview = false,
                // size of the thumbnail from the input tab
                cropSettingWidth = $jcropTarget.data('width'),
                cropSettingHeight = $jcropTarget.data('height'),

                isZeroWidth = cropSettingWidth === 0,
                isZeroHeight = cropSettingHeight === 0,
                isZeroSize = isZeroWidth || isZeroHeight,

                // size of the cropping area
                visualImageWidth = $jcropTarget.width(),
                visualImageHeight = $jcropTarget.height(),

                actualImageWidth = parseInt($("#ci-image-width").val()),
                actualImageHeight = parseInt($("#ci-image-height").val()),

                jcropOptions = {
                    onChange: function(cropRectangle) {
                        jcropHandler(cropRectangle);
                    },
                    onSelect: function(cropRectangle) {
                        jcropHandler(cropRectangle);
                    },
                    aspectRatio: cropSettingHeight === 0 ? 0 : cropSettingWidth/cropSettingHeight,
                    boxWidth: $("#bd").innerWidth(),
                    boxHeight: $window.height() - (parseInt($("#bd").css("margin-bottom")) + parseInt($("#bd").css("margin-top")) )
                },

                // get previously chosen coords via hidden input
                cropX = $inputX1.val(),
                cropY = $inputY1.val(),

                cropW = $inputW.val(),
                cropH = $inputH.val(),

                isUpscaling = $inputUpscaling.val() === "0" ? false : true;

            updatePreviewScale = function() {

                var smallPreviewWidth = $window.width() / 4,
                    isLargerThanPreview = cropSettingWidth > smallPreviewWidth,
                    scale = smallPreviewWidth/cropSettingWidth;

                if (isLargerThanPreview) {
                    $previewContainer.addClass("is-zoomable");

                    if (!isFullPreview) {
                        $previewContainer.css({
                            transform: "scale("+scale+")",
                            transformOrigin: "100% 0"
                        });
                    } else {
                        $previewContainer.css({
                            transform: "none",
                        });
                    }

                } else {
                    $previewContainer.removeClass("is-zoomable");
                }
            };



            jcropHandler = function(cropRectangle) {
                updatePreview(cropRectangle);
                updateHiddenInputs(cropRectangle);
                updatePreviewScale();
                if (isZeroSize) {
                    updateResizedValue(cropRectangle);
                };
                $(".jcrop-holder div .jcrop-tracker").attr("data-size", getFinalWidth(cropRectangle.w) + " x " + getFinalHeight(cropRectangle.h));
                $(".jcrop-holder>.jcrop-tracker").attr("data-size", actualImageWidth + " x " + actualImageHeight);
            };



            updateResizedValue = function(cropRectangle) {
                var visualImageWidth = $jcropTarget.width(),
                    visualImageHeight = $jcropTarget.height(),
                    // relation between crop rect andactual crop setting
                    rectSettingRelation;

                if (cropSettingWidth === 0) {
                    rectSettingRelation = cropSettingHeight / cropRectangle.h;
                } else {
                    rectSettingRelation = cropSettingWidth / cropRectangle.w;
                }

                $croppedWidth.html(Math.round(cropRectangle.w * rectSettingRelation));
                $croppedHeight.html(Math.round(cropRectangle.h * rectSettingRelation));
            };



            updatePreview = function(cropRectangle) {

                var visualImageWidth = $jcropTarget.width(),
                    visualImageHeight = $jcropTarget.height(),
                    // relation between crop rect andactual crop setting
                    rectSettingRelation,
                    winWidth = $(window).width();

                if (cropSettingWidth === 0) {
                    rectSettingRelation = cropSettingHeight / cropRectangle.h;
                } else {
                    rectSettingRelation = cropSettingWidth / cropRectangle.w;
                }

                $preview.css({
                    width: roundpx(rectSettingRelation * visualImageWidth),
                    height: roundpx(rectSettingRelation * visualImageHeight),
                    // better performance than margin
                    transform: [
                        'translateX(-' + roundpx(rectSettingRelation * cropRectangle.x) + ')',
                        'translateY(-' + roundpx(rectSettingRelation * cropRectangle.y) + ')'
                    ].join(' ')
                });

                if (isZeroSize) {
                    $previewContainer.css({
                        width: roundpx(cropRectangle.w * rectSettingRelation),
                        height: roundpx(cropRectangle.h * rectSettingRelation)
                    });
                }
            };



            roundpx = function(num) {
                return Math.round(num) + 'px'
            };



            getSingleCrop = function(crop) {
                return (crop !== "0" && typeof crop !== "undefined") ? parseFloat(crop) : 0;
            };


            // prevent rounding errors by jcrop,
            // when image is really large but resized visually
            getFinalWidth = function(width) {
                return !isUpscaling && width < cropSettingWidth ? cropSettingWidth : width;
            };


            // prevent rounding errors by jcrop,
            // when image is really large but resized visually
            getFinalHeight = function(height) {
                return !isUpscaling && height < cropSettingHeight ? cropSettingHeight : height;
            };



            updateHiddenInputs = function(cropRectangle) {
                $inputX1.val(cropRectangle.x);
                $inputY1.val(cropRectangle.y);

                // $inputW.val(finalWidth);
                // $inputH.val(finalHeight);
                $inputW.val(getFinalWidth(cropRectangle.w));
                $inputH.val(getFinalHeight(cropRectangle.h));
            };



            // todo
            if (!isUpscaling) {
                jcropOptions.minSize = [cropSettingWidth,cropSettingHeight];
            };

            cropX = getSingleCrop(cropX);
            cropY = getSingleCrop(cropY);

            cropW = getSingleCrop(cropW);
            cropH = getSingleCrop(cropH);

            // make the inital rectangle square, so that is not 0 and barely visible/usable
            if (cropW === 0) { cropW = cropH };
            if (cropH === 0) { cropH = cropW };

            jcropOptions.setSelect = [
                cropX, cropY,
                cropX + cropW, cropY + cropH
            ];

            $jcropTarget.Jcrop(jcropOptions);


            // events

            $buttonShowPreview.click(function(){
                $previewContainer.toggleClass('hide');
            });

            $previewContainer.on("click", function() {
                isFullPreview = !isFullPreview;
                $(this).toggleClass("is-zoomed");
                updatePreviewScale();
            });

            $window.on("resize", updatePreviewScale);

            $("img").one("load", updatePreviewScale).each(function() {
                if(this.complete) $(this).load();
                $jcropTracker = $(".jcrop-tracker");
                console.log($jcropTracker);
            });

            // TODO: tmp fix - hide preview if crop dimensions are 0
            if (cropSettingWidth === 0 && cropSettingHeight === 0) {
                $previewContainer.hide();
                $buttonShowPreview.hide();
                $('label[for='+$buttonShowPreview.attr("id")+']').hide();
            };
        }

    });

})(jQuery, window);




