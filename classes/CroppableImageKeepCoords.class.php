<?php

/**
* We use this wrapper class with a read and a write method to be able to store
* the coords and options permanent!
*
* It is stored as metadata in a IPTC custom field of the original imagefile :)
*/

class CroppableImageKeepCoords {


    const ciVersion = 82;


    private $permanentStorage = true;  // set it to true by default now! :)
    private $sessArrayName = __CLASS__;
    private $id = null;
    private $suffix;
    private $pageimage;
    private $validIptcTags = array('005','007','010','012','015','020','022','025','030','035','037','038','040','045','047','050','055','060','062','063','065','070','075','080','085','090','092','095','100','101','103','105','110','115','116','118','120','121','122','130','131','135','150','199','209','210','211','212','213','214','215','216','217');
    private $iptcCustomfield = '2#216';  // use another field than CropImage with PiM


    // !! $pageimage needs to be ALWAYS the original image, not a variation !!
    public function __construct($pageimage = null, $suffix = null, $cropW = null, $cropH = null, $permanentStorage=true) {
        if (!$pageimage) return;
        if (!$suffix || !$cropW || !$cropH) return;
        $this->id = $suffix . md5($pageimage->url);
        $this->suffix = $suffix;
        $this->pageimage = $pageimage;
        $this->cropW = $cropW;
        $this->cropH = $cropH;
        $this->permanentStorage = $permanentStorage;
    }



    public function dropSuffix($pageimage, $suffix) {

        if (!$pageimage) return false;
        $this->id = $suffix . md5($pageimage->url);
        $this->suffix = $suffix;
        $this->pageimage = $pageimage;
        $this->cropW = -1;
        $this->cropH = -1;
        $this->permanentStorage = true;

        $iptc = $this->getIPTCraw();
        // if it don't have our custom tag, return
        if (!isset($iptc[$this->iptcCustomfield]) || !isset($iptc[$this->iptcCustomfield][0])) {
            return true;
        }

        // fetch complete array with all subsets
        $crops = unserialize($iptc[$this->iptcCustomfield][0]);
        if (!is_array($crops) || !isset($crops[$suffix])) {
            return true;
        }

        // remove a subset and write back into IPTC
        unset($crops[$suffix]);
        $iptc[$this->iptcCustomfield][0] = serialize($crops);

        // write back into file
        return $this->writeIptcIntoFile($pageimage->filename, $iptc);
    }



    public function readSuffix($pageimage, $suffix, &$x1, &$y1, &$w, &$h, &$quality, &$sharpening) {
        if (!$pageimage) return false;
        $this->id = $suffix . md5($pageimage->url);
        $this->suffix = $suffix;
        $this->pageimage = $pageimage;
        $this->cropW = -1;
        $this->cropH = -1;
        $this->permanentStorage = true;
        if (true === $this->readFromIptc($x1, $y1, $w, $h, $quality, $sharpening, $suffix)) {
            return true;
        }
        return false;
    }



    public function read(&$x1, &$y1, &$w, &$h, &$quality, &$sharpening) {

        $session = wire('session');
        if (!$this->pageimage || (!$this->permanentStorage && !isset($session->{$this->sessArrayName}[$this->id]))) {
            $x1 = $y1 = 0;
            $w = $this->cropW;
            $h = $this->cropH;
            return false;
        }

        // read from Session if possible, (should be faster than to access the diskfile)
        $a = is_array($session->{$this->sessArrayName}) ? $session->{$this->sessArrayName} : array();
        if (isset($a[$this->id])) {
            foreach(array('x1', 'y1', 'w', 'h', 'quality', 'sharpening') as $k=>$v) {
                $$v = $a[$this->id][$k];
            }
            return true;
        }

        // no Session-Info available? = read from IPTC if possible
        if ($this->readFromIptc($x1, $y1, $w, $h, $quality, $sharpening, $this->suffix)) {
            return true;
        }

        // fallback
        $x1 = $y1 = 0;
        $w = $this->cropW;
        $h = $this->cropH;
        return false;
    }



    private function readFromIptc(&$x1, &$y1, &$w, &$h, &$quality, &$sharpening, $suffix=null) {

        $a = $this->getIPTCraw();

        // if it don't have our custom tag, return
        if (!isset($a[$this->iptcCustomfield])) {
            return false;
        }

        // if $suffix === null we return the complete array with all subsets
        if (null === $suffix) {
            return unserialize($a[$this->iptcCustomfield][0]);
        }

        // if is requested a specific suffix, we extract it if available and return true | false for success
        $a = unserialize($a[$this->iptcCustomfield][0]);
        if (!is_array($a) || !isset($a[$suffix])) {
            return false;
        }
        $x1 = $a[$suffix]['x1'];
        $y1 = $a[$suffix]['y1'];
        $w = $a[$suffix]['w'];
        $h = $a[$suffix]['h'];
        $quality = $a[$suffix]['quality'];
        $sharpening = $a[$suffix]['sharpening'];

        return true;
    }



    public function write($x1, $y1, $w, $h, $quality, $sharpening) {

        if (!$this->pageimage) {
            return false;
        }

        if (0==$w || 0==$h) {
            return false;
        }

        // write to IPTC if possible
        $res = $this->writeToIptc($this->suffix, $x1, $y1, $w, $h, $quality, $sharpening) ? true : false;

        // write to Session too
        $session = wire('session');
        $entry = array($x1, $y1, $w, $h, $quality, $sharpening);
        $a = is_array($session->{$this->sessArrayName}) ? $session->{$this->sessArrayName} : array();
        $session->{$this->sessArrayName} = array_merge($a, array($this->id=>$entry));

        return $res;
    }



    private function writeToIptc($suffix, $x1, $y1, $w, $h, $quality, $sharpening) {

        if(0==$w || 0==$h) {
            return false;
        }

        $targetFilename = $this->pageimage->filename;
        if (!is_file($targetFilename) || !is_readable($targetFilename) || !is_writeable($targetFilename)) {
            return false;
        }

        // new coords
        $permanent = array($suffix => array('x1'=>$x1, 'y1'=>$y1, 'w'=>$w, 'h'=>$h, 'quality'=>$quality, 'sharpening'=>$sharpening));

        // get old coords
        $a = $this->getIPTCraw();
        $a = isset($a[$this->iptcCustomfield]) && isset($a[$this->iptcCustomfield][0]) ? unserialize($a[$this->iptcCustomfield][0]) : array();
        if (!is_array($a)) $a = array();

        // write back merged data
        $data = array(serialize(array_merge($a, $permanent)));

        if (!$this->setIPTCraw(array($this->iptcCustomfield => $data))) return false;

        // now write this into the original imagefile
        return $this->writeIptcIntoFile($targetFilename, null);
    }



    public function writeIptcIntoFile($targetFilename, $iptcRaw) {
        $content = iptcembed($this->iptcPrepareData($iptcRaw), $targetFilename);
        if ($content !== false) {
            $dest = $targetFilename . '.tmp';
            if (strlen($content) == @file_put_contents($dest, $content, LOCK_EX)) {
                // on success we replace the file
                unlink($targetFilename);
                rename($dest, $targetFilename);
            } else {
                // it was created a temp diskfile but not with all data in it
                if(file_exists($dest)) {
                    @unlink($dest);
                    return false;
                }
            }
        }
        return true;
    }



    public function getIPTCraw() {

        if (!isset($this->iptcRaw)) {
            // if called the first time, we try to read it from the original imagefile
            $sourceFilename = $this->pageimage->filename;
            if (!is_file($sourceFilename) || !is_readable($sourceFilename)) {
                return false;
            }

            $additionalInfo = array();
            $info = @getimagesize($sourceFilename, $additionalInfo);
            if ($info===false || !isset($info[2])) {
                return false;
            }

            // read metadata if present and store it as temporary class var for further usage
            if (isset($additionalInfo['APP13'])) {
                $iptc = iptcparse($additionalInfo["APP13"]);
                if(is_array($iptc)) $this->iptcRaw = $iptc;
            }
                #$this->setIPTCraw(iptcparse($additional_info["APP13"]));
        }

        if (isset($this->iptcRaw) && is_array($this->iptcRaw)) {
            return $this->iptcRaw;
        }

        return array();
    }



    protected function setIPTCraw($data) {
        if (!is_array($data)) {
            return false;
        }
        $this->iptcRaw = array_merge($this->getIPTCraw(), $data);
        return is_array($this->iptcRaw);
    }



    /**
     * Prepare IPTC data
     *
     * @return string $iptcNew
     *
     */
    protected function iptcPrepareData($iptcRaw = null) {
        $iptcNew = '';
        $iptcRaw = is_array($iptcRaw) ? $iptcRaw : $this->iptcRaw;
        foreach(array_keys($iptcRaw) as $s) {
            $tag = substr($s, 2);
            if(substr($s, 0, 1) == '2' && in_array($tag, $this->validIptcTags) && is_array($iptcRaw[$s])) {
                foreach($iptcRaw[$s] as $row) {
                    $iptcNew .= $this->iptcMakeTag(2, $tag, $row);
                }
            }
        }
        return $iptcNew;
    }



    /**
     * Make IPTC tag
     *
     * @param string $rec
     * @param string $dat
     * @param string $val
     * @return string
     *
     */
    protected function iptcMakeTag($rec, $dat, $val) {
        $len = strlen($val);
        if($len < 0x8000) {
            return  @chr(0x1c) . @chr($rec) . @chr($dat) .
                chr($len >> 8) .
                chr($len & 0xff) .
                $val;
        } else {
            return  chr(0x1c) . chr($rec) . chr($dat) .
                chr(0x80) . chr(0x04) .
                chr(($len >> 24) & 0xff) .
                chr(($len >> 16) & 0xff) .
                chr(($len >> 8 ) & 0xff) .
                chr(($len ) & 0xff) .
                $val;
        }
    }

}
