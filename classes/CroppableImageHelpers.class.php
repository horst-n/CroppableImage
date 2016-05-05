<?php namespace ProcessWire;

class CroppableImageHelpers {


    const ciVersion = 90;

    const ciGlobalConfigName = 'FieldtypeCroppableImage';


    /**
     * get a TemplateFile instance of a given template name
     *
     * @param  string           $templateName template name without extension
     * @return TemplateFile     TemplateFile instance
     */
    static public function getTemplate($templateName, $array = null) {
        $template = new TemplateFile(dirname(__FILE__) . "/../templatefiles/{$templateName}.php");
        if ($array) {
            $template->setArray($array);
        }
        return $template;
    }



    /**
    * with a version near 2.5.16, PW logs / can log modules API actions,
    * this can lead very quick to thousands of logentries with Croppableimages or Pia
    * so we suppress logs for our config data merging
    */
    static public function writeModuleConfigData($classname, &$data) {
        $logs = wire('config')->logs;                              // get current log status
        if(!is_array($logs) || !isset($logs['modules'])) {
            wire('modules')->saveModuleConfigData($classname, $data);
            return;
        }
        wire('config')->logs = array();                            // switch off logging for modules
        wire('modules')->saveModuleConfigData($classname, $data);  // save config data
        wire('config')->logs = $logs;                              // toggle on logging for modules
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
    static public function renderImage(&$caller, &$img, $sourceFilename, $targetFilename, $width, $height, $options) {

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
     * easy access to the DefaultOptions of an ImageEngine
     *
     * @return array
     */
    static public function getImageEngineConfigSettings($filename, $asArray = false) {
        $imageSizer = new ImageSizer($filename);
        $options = array(
            'quality' => $imageSizer->quality,
            'sharpening' => $imageSizer->sharpening,
            'engineName' => $imageSizer->getEngine()->className()
        );
        return true === $asArray ? $options : self::arrayToObject($options);
    }


    /**
     * easy access to the ImageOptions of CroppableImage,
     *
     * @return array
     */
    static public function getGlobalImageSettings($asArray = false) {
        $options = wire('modules')->get('ProcessCroppableImage')->getCroppableImageOptions();
        if(!is_array($options)) {
            throw new WireException(__('Unable to get CroppableImageOptions!'));
        }
        return true === $asArray ? $options : self::arrayToObject($options);
    }



    /**
     * easy access to the ImageOptions of CroppableImage,
     *
     * @return array
     */
    static public function getGlobalImageSetting($option) {
        $options = self::getGlobalImageSettings(true);
        if(isset($options[$option])) return $options[$option];
        return null;
    }



    /**
     * easy access to the global settings of CroppableImage,
     * stored in the Modules-Configpage of InputfieldCroppableImage
     *
     * @param  bool $asArray    optional, default is false, if set to true returns an array, otherwise an object
     * @return object | array   with properties|arraykeys named like config-keys
     */
    static public function getGlobalConfigSettings($asArray = false) {
        $gcd = wire('modules')->getModuleConfigData(self::ciGlobalConfigName);
        if(!is_array($gcd)) {
            throw new WireException(__('Unable to get Configdata of ' . self::ciGlobalConfigName . '!'));
        }

        return true === $asArray ? $gcd : self::arrayToObject($gcd);
    }



    /**
     * easy access to the global settings of CroppableImage,
     * stored in the Modules-Configpage of InputfieldCroppableImage
     *
     * @param  bool $asArray    optional, default is false, if set to true returns an array, otherwise an object
     * @return object | array   with properties|arraykeys named like config-keys
     */
    static public function getGlobalConfigSetting($param) {
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
    static public function setGlobalConfigSettings($newPartialSettings) {
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



    /**
     * Helper method: Array to HTML attributes string
     *
     * @param (array) $array, key value pair array
     * @return (string)
     *
     */
    static public function arrayToHtmlAttr($array) {
        if(!count($array)) return '';
        $out = '';
        foreach ($array as $key => $value) $out .= $key . "='{$value}' ";
        return trim($out);
    }



    static public function sanitizeColor($value, $forGdColorallocate = false, $forAdjustments = false) {
        if(is_array($value) && (count($value) == 4 || count($value) == 3)) {
            $color = $value;
        } else if(is_int($value)) {
            $color = array($value, $value, $value);
        } else if(!is_string($value)) {
            // ERROR !!
            throw new wireException(__FUNCTION__ . " (A) wrong value given: $value !!");
            return false;
        } else {
            // test for common colornames
            static $common_colors = array('antiquewhite'=>'#FAEBD7','aqua'=>'#00FFFF','aquamarine'=>'#7FFFD4','beige'=>'#F5F5DC','black'=>'#000000','blue'=>'#0000FF','brown'=>'#A52A2A','cadetblue'=>'#5F9EA0','chocolate'=>'#D2691E','cornflowerblue'=>'#6495ED','crimson'=>'#DC143C','darkblue'=>'#00008B','darkgoldenrod'=>'#B8860B','darkgreen'=>'#006400','darkmagenta'=>'#8B008B','darkorange'=>'#FF8C00','darkred'=>'#8B0000','darkseagreen'=>'#8FBC8F','darkslategray'=>'#2F4F4F','darkviolet'=>'#9400D3','deepskyblue'=>'#00BFFF','dodgerblue'=>'#1E90FF','firebrick'=>'#B22222','forestgreen'=>'#228B22','fuchsia'=>'#FF00FF','gainsboro'=>'#DCDCDC','gold'=>'#FFD700','gray'=>'#808080','green'=>'#008000','greenyellow'=>'#ADFF2F','hotpink'=>'#FF69B4','indigo'=>'#4B0082','khaki'=>'#F0E68C','lavenderblush'=>'#FFF0F5','lemonchiffon'=>'#FFFACD','lightcoral'=>'#F08080','lightgoldenrodyellow'=>'#FAFAD2','lightgreen'=>'#90EE90','lightsalmon'=>'#FFA07A','lightskyblue'=>'#87CEFA','lightslategray'=>'#778899','lightyellow'=>'#FFFFE0','lime'=>'#00FF00','limegreen'=>'#32CD32','magenta'=>'#FF00FF','maroon'=>'#800000','mediumaquamarine'=>'#66CDAA','mediumorchid'=>'#BA55D3','mediumseagreen'=>'#3CB371','mediumspringgreen'=>'#00FA9A','mediumvioletred'=>'#C71585','mintcream'=>'#F5FFFA','moccasin'=>'#FFE4B5','navy'=>'#000080','olive'=>'#808000','orange'=>'#FFA500','orchid'=>'#DA70D6','palegreen'=>'#98FB98','palevioletred'=>'#D87093','peachpuff'=>'#FFDAB9','pink'=>'#FFC0CB','powderblue'=>'#B0E0E6','purple'=>'#800080','red'=>'#FF0000','royalblue'=>'#4169E1','salmon'=>'#FA8072','seagreen'=>'#2E8B57','sienna'=>'#A0522D','silver'=>'#C0C0C0','skyblue'=>'#87CEEB','slategray'=>'#708090','springgreen'=>'#00FF7F','tan'=>'#D2B48C','teal'=>'#008080','thistle'=>'#D8BFD8','turquoise'=>'#40E0D0','violetred'=>'#D02090','white'=>'#FFFFFF','yellow'=>'#FFFF00');
            if(isset($common_colors[strtolower($value)])) {
                $value = $common_colors[strtolower($value)];
            }
            // test for #HexColor
            if(preg_match('/^(#*[a-f0-9]{3}([a-f0-9]{3})?)$/i', $value)) {
                if(3 == strlen($value) || 6 == strlen($value)) $value = '#' . $value;
                if($value{0} == '#') { //case of #nnnnnn or #nnn
                    $c = strtoupper($value);
                    if(strlen($c) == 4) { // Turn #RGB into #RRGGBB
                        $c = "#" . $c{1} . $c{1} . $c{2} . $c{2} . $c{3} . $c{3};
                    }
                    $color = array();
                    $color[0] = hexdec(substr($c, 1, 2));
                    $color[1] = hexdec(substr($c, 3, 2));
                    $color[2] = hexdec(substr($c, 5, 2));
                }
            } else if(preg_match('/^([rgb|rgba]){1}\(\d(,\d){2-3}\))$/i', str_replace(' ', '', trim($value)))) { //case of RGB(r,g,b) or rgba(r,g,b,a)
                $value = str_replace(array('rgb', 'RGB', 'rgba', 'RGBA', '(', ')'), '', str_replace(' ', '', trim($value)));
                $c = explode(',', $value);
                $color = array($c[0], $c[1], $c[2]);
                if(isset($c[3])) $color[] = $c[3];
            } else {
                // ERROR !!
                throw new wireException(__FUNCTION__ . " (B) wrong value given: $value !!");
                return false;
            }
        }
        $min = $forAdjustments ? -255 : 0;
        $max = 255;
        $default = $forAdjustments ? 0 : 127;
        foreach(array(0, 1, 2) as $c) {
            $i = intval(trim($color[$c]));
            $color[$c] = $i >= $min && $i <= $max ? $i : $default;
        }
        if(isset($color[3])) {  // rgba, value for the alpha channel
            // we have a float like with css rgba, float 0 - 1 where 0 is transparent and 1 is opaque
            $color[3] = $color[3] >= 0 && $color[3] <= 1 ? $color[3] : 0.5;
            if($forGdColorallocate) {
                // convert css rgba alpha setting float 0-1 (transparent-opaque) scale to GDs ImagecolorAllocateAlpha 0-127 (opaque-transparent) scale
                $color[3] = intval((($color[3] * 127) - 127) * -1);
            }
        }

        return array($color[0], $color[1], $color[2]); // only rgb is supported here in ALIF, not rgba !!
    }



    static public function convertRgbColorToHex($rgb, $addHexChar = false) {
        if(!is_array($rgb)) return '';
        $hex = str_pad(dechex($rgb[0]), 2, '0', STR_PAD_LEFT);
        $hex .= str_pad(dechex($rgb[1]), 2, '0', STR_PAD_LEFT);
        $hex .= str_pad(dechex($rgb[2]), 2, '0', STR_PAD_LEFT);
        return $addHexChar ? '#' . $hex : $hex;
    }



    static public function arrayToObject($a) {
        $o = new \stdClass();
        foreach($a as $k => $v) $o->$k = $v;
        return $o;
    }

}
