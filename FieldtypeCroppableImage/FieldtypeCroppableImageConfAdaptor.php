<?php

class FieldtypeCroppableImageConfAdaptor extends Wire {


    const ciVersion = 82;

    static protected $sharpeningValues = array('none', 'soft', 'medium', 'strong');


    public function getConfig(array $data) {

        // check that they have the required PW version
        if(version_compare(wire('config')->version, '2.5.11', '<')) {
            $this->error(" requires ProcessWire 2.5.11 or newer. Please update.");
        }

        $modules = wire('modules');
        if($modules->isInstalled('InputfieldRangeSlider')) $rangeSliderInfo = InputfieldRangeSlider::getModuleInfo();
        $hasSlider = $modules->isInstalled('InputfieldRangeSlider') && version_compare($rangeSliderInfo['version'], '1.0.4', '>=');

        $form = new InputfieldWrapper();

        $fieldset = $modules->get('InputfieldFieldset');
        $fieldset->label = 'Quality & Sharpening';
        $fieldset->attr('name', '_quality_sharpening');
        $fieldset->description = __('Here you can set sitewide options for Quality and Sharpening');
        $fieldset->collapsed = Inputfield::collapsedNo;


            $field = $modules->get('InputfieldCheckbox');
            $field->attr('name', 'manualSelectionDisabled');
            $field->label = __('Globally disable the usage of DropDown-Selects for Quality & Sharpening!');
            $field->attr('value', 1);
            $field->attr('checked', ($data['manualSelectionDisabled'] ? 'checked' : ''));
            $field->columnWidth = 100;
            $fieldset->add($field);


            $field = $modules->get('InputfieldSelect');
            $field->label = 'global setting for Sharpening';
            $field->attr('name', 'optionSharpening');
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
            $field->description = __('sharpening: none | soft | medium | strong');
            $field->columnWidth = 50;
            $field->showIf = "manualSelectionDisabled=1";
            $fieldset->add($field);


            $field = $modules->get('InputfieldInteger');
            $field->label = 'global setting for Quality';
            $field->attr('name', 'optionQuality');
            $field->attr('value', ($data['optionQuality']>0 && $data['optionQuality']<=100 ? $data['optionQuality'] : 90));
            $field->description = __('quality: 1-100 where higher is better but bigger');
            $field->columnWidth = 50;
            $field->showIf = "manualSelectionDisabled=1";
            if($hasSlider) {
                $field->collapsed = Inputfield::collapsedHidden;
                $fieldset->add($field);
                $fieldS = $modules->get('InputfieldRangeSlider');
                // read value from optionQuality, not from optionQualitySlider
                $fieldS->label = 'quality';
                $fieldS->attr('name', 'optionQualitySlider');
                $fieldS->attr('value', array('min'=>($data['optionQuality']>0 && $data['optionQuality']<=100 ? $data['optionQuality'] : 90)));
                $fieldS->isrange = false;
                $fieldS->minValue = 1;
                $fieldS->maxValue = 100;
                $fieldS->step = 1;
                $fieldS->width = 100;
                $fieldS->description = __('quality: 1-100 where higher is better but bigger');
                $fieldS->columnWidth = 50;
                $fieldS->showIf = "manualSelectionDisabled=1";
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
//        $field->label = __('Info');
//        $field->columnWidth = 100;
//        $form->add($field);



//        $field = $modules->get('InputfieldCheckbox');
//        $field->attr('name', 'remove_all_variations');
//        $field->label = __('Remove all Imagevariations to clear the images-cache sitewide!');
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
//        $field->label = __('Run only in test mode! Do not delete the variations.');
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

if( ! class_exists('filo')) {
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

