<?php namespace ProcessWire;

class FieldtypeCroppableImageConfAdaptor extends Wire {

    const ciVersion = 90;

    static protected $sharpeningValues = array('none', 'soft', 'medium', 'strong');


    public function getConfig(array $data) {

        @require_once(dirname(__FILE__) . '/../classes/CroppableImageHelpers.class.php');

        // check that they have the required PW version
        if(version_compare(wire('config')->version, '3.0.14', '<')) {
            $this->error("Requires ProcessWire 3.0.14 or newer. Please update!");
        }

        // load color picker
        $jsUrl = $this->config->urls->FieldtypeCroppableImage . 'jscolor.js';
        $this->config->scripts->append($jsUrl);

        $modules = wire('modules');

        $hasSlider = false;
        if($modules->isInstalled('InputfieldRangeSlider') && class_exists('\InputfieldRangeSlider')) {
            $rangeSliderInfo = \InputfieldRangeSlider::getModuleInfo();
            $hasSlider = version_compare($rangeSliderInfo['version'], '1.0.4', '>=');
        }

        $form = new InputfieldWrapper();

        $fieldset = $modules->get('InputfieldFieldset');
        $fieldset->label = $this->_('Core Imagefield Settings');
        $fieldset->attr('name+id', '_settings_for_coreimage');
        #$fieldset->description = __('');
        $fieldset->collapsed = Inputfield::collapsedYes;
            $field = $modules->get('InputfieldCheckbox');
            $field->attr('name+id', 'imagefieldCroptoolDisabled');
            $field->label = $this->_('Disable the usage of the individual Croptool from the Core-Imagefield!');
            $field->attr('value', 1);
            $field->attr('checked', ($data['imagefieldCroptoolDisabled'] ? 'checked' : ''));
            $field->columnWidth = 100;
            $fieldset->add($field);
        $form->add($fieldset);

        $fieldset = $modules->get('InputfieldFieldset');
        $fieldset->label = $this->_('Customize Thumbnail Settings');
        $fieldset->attr('name+id', '_settings_for_thumbnails');
        #$fieldset->description = $this->_('Here you can control how to display the Thumbnails');
        $fieldset->collapsed = Inputfield::collapsedNo;

            $field = $modules->get('InputfieldSelect');
            $field->attr('name+id', 'thumbnailContain');
            $field->label = $this->_('Define how the Thumbnails should be displayed initially');
            $field->description = $this->_('Cover shows a centralized cropped, square Thumbnail over the full Griditem. Contain shrinks the image to fit completely into the Griditem, so that you directly see the aspects (portrait, landscape, square).');
            $field->notes = $this->_('The default from the Core Imagefield is') . ': cover';
            $field->addOptions(array('cover', 'contain'));
            $field->attr('value', $data['thumbnailContain']);
            $field->columnWidth = 100;
            $fieldset->add($field);

            $field = $modules->get('InputfieldCheckbox');
            $field->attr('name+id', 'thumbnailShowBasename');
            $field->label = $this->_('Initially show the images basename over the Thumbnails.');
            $field->description = $this->_('There is a switcher in the header of each Croppableimages field. It toggles the basename on/off in the Griditems. This setting here only defines how you want it behave initially!');
            $field->notes = $this->_('default is: unchecked');
            $field->attr('value', 1);
            $field->attr('checked', ($data['thumbnailShowBasename'] ? 'checked' : ''));
            $field->columnWidth = 65;
            $fieldset->add($field);

            $field = $modules->get('InputfieldRangeSlider');
            $field->attr('name+id', 'thumbnailShowBasenameCharlength');
            $field->label = $this->_('When displaying the images basename over the Thumbs, shorten it to this max. chars');
            $field->attr('value', $data['thumbnailShowBasenameCharlength']);
            #$field->attr('value', array('min'=>15));
            $field->isrange = false;
            $field->minValue = 10;
            $field->maxValue = 40;
            $field->step = 1;
            $field->width = 100;
            $field->columnWidth = 35;
            $field->notes = $this->_('select a number corresponding to your Thumbnails width');
            $fieldset->add($field);

            $fieldset2 = $modules->get('InputfieldFieldset');
            $fieldset2->label = $this->_('ColorSettings');
            $fieldset2->attr('name+id', '_settings_for_basenamecolors');
            $fieldset2->collapsed = Inputfield::collapsedYes;

                $defaults = FieldtypeCroppableImage::getDefaultData();
                $colors = array(
                    'griditem_color_bg' => array($data['griditem_color_bg'], false, 100, $defaults['griditem_color_bg']),
                    'tooltip_color_bg' => array($data['tooltip_color_bg'], false, 100, $defaults['tooltip_color_bg']),
                    'basename_color_bg' => array($data['basename_color_bg'], true, 65, $defaults['basename_color_bg']),
                    'basename_color_fg' => array($data['basename_color_fg'], true, 65, $defaults['basename_color_fg']),
                    'basename_color_fg_shadow' => array($data['basename_color_fg_shadow'], true, 65, $defaults['basename_color_fg_shadow'])
                );
                $alphas = array(
                    'basename_color_bg_alpha' => $data['basename_color_bg_alpha'],
                    'basename_color_fg_alpha' => $data['basename_color_fg_alpha'],
                    'basename_color_fg_shadow_alpha' => $data['basename_color_fg_shadow_alpha']
                );
                foreach($colors as $k => $v) {
                    $field = $this->modules->get('InputfieldText');
                    $field->attr('name+id', $k);
                    $field->label = str_replace(array('_color', '_', 'bg', 'fg'), array('', ' ', 'background color', 'color'), $k);
                    $field->value = CroppableImageHelpers::convertRgbColorToHex(CroppableImageHelpers::sanitizeColor($v[0]));
                    $field->attr('class', 'jscolor');
                    $field->attr('maxlength', 7);
                    $field->attr('length', 7);
                    $field->columnWidth = $v[2];
                    $field->notes = $this->_('default value') . ": #{$v[3]}";
                    #$field->required = true;
                    $fieldset2->add($field);

                    if(!$v[1]) continue;

                    $field = $this->modules->get('InputfieldRangeSlider');
                    $field->attr('name+id', $k . '_alpha');
                    $field->label = str_replace(array('basename_color', '_', 'bg', 'fg'), array('', ' ', 'background color', 'color'), $k);
                    $field->attr('value', $data[$k . '_alpha']);
                    $field->isrange = false;
                    $field->minValue = 0;
                    $field->maxValue = 10;
                    $field->step = 1;
                    $field->width = 100;
                    $field->notes = $this->_('alpha transparency: 0 - 10');
                    $field->columnWidth = 35;
                    #$field->required = true;
                    $fieldset2->add($field);
                }
            $fieldset->add($fieldset2);

        $form->add($fieldset);


        $fieldset = $modules->get('InputfieldFieldset');
        $fieldset->label = 'Quality & Sharpening';
        $fieldset->attr('name+id', '_quality_sharpening');
        $fieldset->description = $this->_('Here you can set sitewide options for Quality and Sharpening. Per default there are selections available in the crop editor, but you can disable them here and define what should be used instead!');
        $fieldset->collapsed = Inputfield::collapsedNo;

            $field = $modules->get('InputfieldCheckbox');
            $field->attr('name+id', 'manualSelectionDisabled');
            $field->label = $this->_('Globally disable the usage of DropDown-Selects for Quality & Sharpening in the CropEditor!');
            $field->notes = $this->_('Instead define them here or use the ImagesizerEngines default values');
            $field->attr('value', 1);
            $field->attr('checked', ($data['manualSelectionDisabled'] ? 'checked' : ''));
            $field->columnWidth = 65;
            $fieldset->add($field);

            $field = $modules->get('InputfieldSelect');
            $field->label = $this->_('Global Setting for Sharpening');
            $field->attr('name+id', 'optionSharpening');
            if(is_numeric($data['optionSharpening']) && isset(self::$sharpeningValues[intval($data['optionSharpening'])])) {
                $value = $data['optionSharpening'];
            } elseif(is_string($data['optionSharpening']) && in_array($data['optionSharpening'], self::$sharpeningValues)) {
                $flippedA = array_flip(self::$sharpeningValues);
                $value = strval($flippedA[$data['optionSharpening']]);
            } else {
                $value = '1';
            }
            $field->attr('value', intval($value));
            $field->addOptions(self::$sharpeningValues);
            $field->description = $this->_('sharpening: none | soft | medium | strong');
            $field->columnWidth = 35;
            $field->showIf = "manualSelectionDisabled=1,useImageEngineDefaults=0";
            $fieldset->add($field);

            $field = $modules->get('InputfieldCheckbox');
            $field->attr('name+id', 'useImageEngineDefaults');
            $field->label = $this->_('Use the ImagesizerEngines default values for Quality & Sharpening!');
            $field->attr('value', 1);
            $field->attr('checked', ($data['useImageEngineDefaults'] ? 'checked' : ''));
            $field->showIf = "manualSelectionDisabled=1";
            $ImageSizer = new ImageSizer();
            $engines = array_merge($ImageSizer->getEngines(), array('ImageSizerEngineGD'));
            $a = array();
            foreach($engines as $e) {
                $mcd = 'ImageSizerEngineGD' == $e ? wire('config')->imageSizerOptions : $modules->getModuleConfigData($e);
                $a[] = ' [&nbsp;' . implode('&nbsp;|&nbsp;', array($e, $mcd['quality'], $mcd['sharpening'])) . '&nbsp;] ';
            }
            $s = implode(' - ', $a);
            if(!empty($s)) $field->notes = $s;
            //$this->_('Is defined and can be changed in the Engines module config pages!')
            $field->columnWidth = 65;
            $fieldset->add($field);

            $field = $modules->get('InputfieldInteger');
            $field->label = $this->_('Global Setting for Quality');
            $field->attr('name+id', 'optionQuality');
            $field->attr('value', ($data['optionQuality']>0 && $data['optionQuality']<=100 ? $data['optionQuality'] : 90));
            $field->description = $this->_('quality: 1-100 where higher is better but bigger');
            $field->columnWidth = 35;
            $field->showIf = "manualSelectionDisabled=1,useImageEngineDefaults=0";
            if($hasSlider) {
                $field->collapsed = Inputfield::collapsedHidden;
                $fieldset->add($field);
                $fieldS = $modules->get('InputfieldRangeSlider');
                // read value from optionQuality, not from optionQualitySlider
                $fieldS->label = $this->_('Global Setting for Quality');
                $fieldS->attr('name+id', 'optionQualitySlider');
                $fieldS->attr('value', array('min'=>($data['optionQuality']>0 && $data['optionQuality']<=100 ? $data['optionQuality'] : 90)));
                $fieldS->isrange = false;
                $fieldS->minValue = 1;
                $fieldS->maxValue = 100;
                $fieldS->step = 1;
                $fieldS->width = 100;
                $fieldS->description = $this->_('quality: 1-100 where higher is better but bigger');
                $fieldS->columnWidth = 35;
                $fieldS->showIf = "manualSelectionDisabled=1,useImageEngineDefaults=0";
                $fieldset->add($fieldS);
            } else {
                $fieldset->add($field);
            }

        $form->add($fieldset);



//        $field = $modules->get("InputfieldMarkup");
//        $field->attr('name', 'info1');
//        $field->collapsed = Inputfield::collapsedNo;
//        $field->attr('value',
//            "THIS IS A TEMPORARY PLACEHOLDER FOR LATER CONTRIBUTIONS<br /><br />"
//            );
//        $field->label = $this->_('Info');
//        $field->columnWidth = 100;
//        $form->add($field);



//        $field = $modules->get('InputfieldCheckbox');
//        $field->attr('name', 'remove_all_variations');
//        $field->label = $this->_('Remove all Imagevariations to clear the images-cache sitewide!');
//        $field->attr('value', 1);
//        $field->attr('checked', '');
//        $field->columnWidth = 65;
//        $form->add($field);
//
//        if(wire('session')->remove_all_variations) {
//            wire('session')->remove('remove_all_variations');
//            $testmode = '1'==$data['do_only_test_run'] ? true : false;
//            $field->notes = $this->doTheDishes( !$testmode );
//        } else if(wire('input')->post->remove_all_variations) {
//            wire('session')->set('remove_all_variations', 1);
//        }
//
//        $field = $modules->get('InputfieldCheckbox');
//        $field->attr('name', 'do_only_test_run');
//        $field->label = $this->_('Run only in test mode! Do not delete the variations.');
//        $field->attr('value', 1);
//        $field->attr('checked', '1');
//        $field->columnWidth = 35;
//        $form->add($field);

        return $form;
    }



    public function doTheDishes($deleteVariations=false) {
        $errors = array();
        $success = false;
        try {
            $success = $this->removeAllVariations($deleteVariations);

        } catch(Exception $e) {
            $errors[] = $e->getMessage();
        }
        if($success) {
            $note = $deleteVariations ?
                $this->_('SUCCESS! All Imagevariations are removed.') :
                $this->_('SUCCESS! Found and listed all Pages with Imagevariations.');
            $this->message($note);

        } else {
            $note = $deleteVariations ?
                $this->_('ERROR: Removing Imagevariations was not successfully finished. Refer to the errorlog for more details.') :
                $this->_('ERROR: Could not find and list all Pages containing Imagevariations. Refer to the errorlog for more details.');
            $this->error($note);
        }
        return $note;
    }


    private function removeAllVariations($deleteVariations=false) {
        $stack = new filo();
        $stack->push(1);
        while($id = $stack->pop()) {
            set_time_limit(intval(15));
            // get the page
            $page = wire('pages')->get($id);
            if(0==$page->id) continue;
            // add children to the stack
            foreach($page->children('include=all') as $child) {
                $stack->push($child->id);
            }
            // iterate over the fields
            foreach($page->fields as $field) {
                if(! $field->type instanceof FieldtypeImage) {
                    continue;
                }
                // get the images
                $imgs = $page->{$field->name};
                $count = count($imgs);
                if(0==$count) continue;
                $this->message('- found page: ' . $page->title . ' - with imagefield: ' . $field->name . ' - count: ' . $count);
                foreach($imgs as $img) {
                    if(true===$deleteVariations) {
                        $this->message(' REMOVED! ');
                        #$img->removeVariations();
                    }
                }
            }
            wire('pages')->uncache($page);
        }
        return true;
    }

}

if( ! class_exists('ProcessWire\\filo')) {
    /** @shortdesc: Stack, First In - Last Out  **/
    class filo {

        /** @private **/
        var $elements;
        /** @private **/
        var $debug;

        /** @private **/
        function filo($debug=false) {
            $this->debug = $debug;
            $this->zero();
        }

        /** @private **/
        function push($elm) {
            array_push($this->elements, $elm);
            if($this->debug) echo "<p>filo->push(".$elm.")</p>";
        }

        /** @private **/
        function pop() {
            $ret = array_pop( $this->elements );
            if($this->debug) echo "<p>filo->pop() = $ret</p>";
            return $ret;
        }

        /** @private **/
        function zero() {
            $this->elements = array();
            if($this->debug) echo "<p>filo->zero()</p>";
        }
    }
} // end class FILO

