<?php

class CroppableImageHelpers {


    const ciVersion = 81;

    const ciGlobalConfigName = 'FieldtypeCroppableImage';


    /**
     * get a TemplateFile instance of a given template name
     *
     * @param  string           $templateName template name without extension
     * @return TemplateFile     TemplateFile instance
     */
    public static function getTemplate($templateName, $array = null) {
        $template = new TemplateFile(dirname(__FILE__) . "/../templatefiles/{$templateName}.php");
        if ($array) {
            $template->setArray($array);
        }
        return $template;
    }



    /**
    * do the resizes like Pageimage does it
    *
    * @param object $caller
    * @param pageimage $img
    * @param string $targetFilename
    * @param array $options1
    * @param array $options2
    * @return pageimage
    */
    public static function renderImage(&$caller, &$img, $sourceFilename, $targetFilename, $width, $height, $options) {

        $filenameFinal = $targetFilename;
        $filenameUnvalidated = $img->pagefiles->page->filesManager()->getTempPath() . basename($targetFilename);

        if(file_exists($filenameFinal)) @unlink($filenameFinal);
        if(file_exists($filenameUnvalidated)) @unlink($filenameUnvalidated);

        if(@copy($sourceFilename, $filenameUnvalidated)) {
            try {
                $sizer = new ImageSizer($filenameUnvalidated);
                $sizer->setOptions($options);
                if($sizer->resize($width, $height) && @rename($filenameUnvalidated, $filenameFinal)) {
                    // if script runs into a timeout while in ImageSizer, we never will reach this line and we will stay with $filenameUnvalidated
                    if($caller->config->chmodFile) chmod($filenameFinal, octdec($caller->config->chmodFile));
                } else {
                    $caller->error = "ImageSizer::resize($width, $height) failed for $filenameUnvalidated";
                }
            } catch(Exception $e) {
                $caller->error = $e->getMessage();
            }
        } else {
            $caller->error("Unable to copy $sourceFilename => $filenameUnvalidated");
        }

        $pageimage = clone $img;

        // if desired, user can check for property of $pageimage->error to see if an error occurred.
        // if an error occurred, that error property will be populated with details
        if($caller->error) {
            // error condition: unlink copied file
            if(is_file($filenameFinal)) @unlink($filenameFinal);
            if(is_file($filenameUnvalidated)) @unlink($filenameUnvalidated);

            // write an invalid image so it's clear something failed
            $data = "This is intentionally invalid image data.\n$caller->error";
            if(file_put_contents($filenameFinal, $data) !== false) wireChmod($filenameFinal);

            // we also tell PW about it for logging and/or admin purposes
            $caller->error($caller->error);
        }

        $pageimage->setFilename($filenameFinal);
        $pageimage->setOriginal($img);

        return $pageimage;
    }



    static public function typeCaster($typeDefinitions, $params) {
        if (!is_array($params)) return null;
        foreach($typeDefinitions as $type => $param) {
            foreach($param as $par) $$par = $type;
        } unset($type, $param, $typeDefinitions);
        $typecastedParams = array();
        $validBooleans = array(-1=>-1, 1=>1, '1'=>'1', '-1'=>'-1', 'on'=>'on', 'ON'=>'ON', 'true'=>'true', 'TRUE'=>'TRUE', 'yes'=>'yes', 'y'=>'y');
        foreach($params as $k => $v) {
            $swtch = isset($$k) ? $$k : '';
            switch($swtch) {
                case 'float': $typecastedParams[$k] = floatval($v); break;
                case 'int': $typecastedParams[$k] = intval($v); break;
                case 'str': $typecastedParams[$k] = strval($v); break;
                case 'bool': $typecastedParams[$k] = (bool) isset($validBooleans[$v]); break;
                default:
                    if (is_numeric($v)) $typecastedParams[$k] = (float) $v;
                    elseif (in_array($v, array('on', 'ON', 'true', 'TRUE', true))) $typecastedParams[$k] = true;
                    elseif (in_array($v, array('off', 'OFF', 'false', 'FALSE', false))) $typecastedParams[$k] = false;
                    else $typecastedParams[$k] = strval($v);
            }
        }
        return $typecastedParams;
    }


    static public function selector2array($selectorStr) {
        if (empty($selectorStr)) return;
        $selectors = new Selectors($selectorStr);
        $params = array();
        foreach($selectors as $selector) $params[$selector->field] = $selector->value;
        return $params;
    }


    /**
     * easy access to the ImageOptions of CroppableImage,
     *
     * @return array
     */
    public static function getGlobalImageSettings() {
        $options = wire('modules')->get('ProcessCroppableImage')->getCroppableImageOptions();
        if (!is_array($options)) {
            throw new WireException(__('Unable to get CroppableImageOptions!'));
        }
        return $options;
    }



    /**
     * easy access to the ImageOptions of CroppableImage,
     *
     * @return array
     */
    public static function getGlobalImageSetting($option) {
        $options = self::getGlobalImageSettings();
        if (isset($options[$option])) return $options[$option];
        return null;
    }



    /**
     * easy access to the global settings of CroppableImage,
     * stored in the Modules-Configpage of InputfieldCroppableImage
     *
     * @param  bool $asArray    optional, default is false, if set to true returns an array, otherwise an object
     * @return object | array   with properties|arraykeys named like config-keys
     */
    public static function getGlobalConfigSettings($asArray=false) {
        $gcd = wire('modules')->getModuleConfigData(self::ciGlobalConfigName);
        if (!is_array($gcd)) {
            throw new WireException(__('Unable to get Configdata of ' . self::ciGlobalConfigName . '!'));
        }
        if (true===$asArray) return $gcd;
        $o = new stdClass();
        foreach($gcd as $k=>$v) $o->$k = $v;
        return $o;
    }



    /**
     * easy access to the global settings of CroppableImage,
     * stored in the Modules-Configpage of InputfieldCroppableImage
     *
     * @param  bool $asArray    optional, default is false, if set to true returns an array, otherwise an object
     * @return object | array   with properties|arraykeys named like config-keys
     */
    public static function getGlobalConfigSetting($param) {
        $a = self::getGlobalConfigSettings(true);
        if (isset($a[$param])) return $a[$param];
        return null;
    }



    /**
     * easy access to the global settings of CroppableImage,
     * stored in the Modules-Configpage of InputfieldCroppableImage
     *
     * @param  object or array $newPartialSettings  hold key/value pairs of settings that should be stored
     * @return result of save action
     */
    public static function setGlobalConfigSettings($newPartialSettings) {
        if (is_object($newPartialSettings)) {
            $newPartialSettings = (array) $newPartialSettings;
        }
        if (!is_array($newPartialSettings)) {
            throw new WireException(__('Params passed to setGlobalConfigSettings are not valid!'));
        }
        $old = self::getGlobalConfigSettings(true);
        $data = array_merge($old, $newPartialSettings);
        return wire('modules')->saveModuleConfigData(self::ciGlobalConfigName, $data);
    }


}
