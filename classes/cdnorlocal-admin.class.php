<?php

namespace axelhahn;
require_once 'cdnorlocal.class.php';

/**
 * use source from a CDN cdnjs.cloudflare.com or local folder?
 * 
 * admin functions to request API, download, read existing local downloads
 * This file is needed by admin/index.php only - NOT in your projects to publish
 *
 * @version 1.0
 * @author Axel Hahn
 * @link http://www.axel-hahn.de
 * @license GPL
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL 3.0
 * @package cdnorlocal
 */
class cdnorlocaladmin extends cdnorlocal{

    /**
     * url for cdnjs api request
     * @var string
     */
    var $sUrlApiPackage='https://api.cdnjs.com/libraries/%s';

    var $aLibs=array();
    
    // ----------------------------------------------------------------------
    // 
    // API requests
    // 
    // ----------------------------------------------------------------------

    /**
     * search for a library with CDN API
     * 
     * @param string  $sLibrary  name of the library to ask for
     * @return array
     */
    public function searchLibrary($sLibrary) {
        $this->_wd(__METHOD__ . "($sLibrary)");
        $sLibrary=str_replace(' ', '+', $sLibrary);
        $sApiUrl=sprintf($this->sUrlApiPackage, '?search='.$sLibrary.'&fields=version,description');
        $this->_wd(__METHOD__ . " fetch $sApiUrl");
        $aJson=json_decode(file_get_contents($sApiUrl));
        
        return $aJson;
    }
    /**
     * fetch metadata of a library from CDN API (and put/ "cache" them to $this->aLibs[$sLibrary]['_cdn'])
     * 
     * @param string  $sLibrary  name of the library to ask for
     * @return array
     */
    public function getLibraryMetadata($sLibrary) {
        $this->_wd(__METHOD__ . "($sLibrary)");
        if (!array_key_exists($sLibrary, $this->aLibs)){
            $this->aLibs[$sLibrary]=array();
        }
        if (!array_key_exists('_cdn', $this->aLibs[$sLibrary])){
            
            // hmmm, add cache with TTL (i.e. 1d)??
            
            $sApiUrl=sprintf($this->sUrlApiPackage, $sLibrary);
            $this->_wd(__METHOD__ . " fetch $sApiUrl");
            $aJson=json_decode(file_get_contents($sApiUrl));
            // echo '<pre>'.print_r($aJson, 1).'</pre>';
            $this->aLibs[$sLibrary]['_cdn']=$aJson;
        }
        if (!array_key_exists('_cdn', $this->aLibs[$sLibrary])){
            return false;
        }
        $aReturn=array();
        foreach(array('description', 'homepage', 'keywords', 'namespace', 'license', 'version', 'author'/*  */) as $sKey){
            $aReturn[$sKey]=(isset($this->aLibs[$sLibrary]['_cdn']->$sKey)) ? $this->aLibs[$sLibrary]['_cdn']->$sKey : false;
        }
        
        return $aReturn;
    }
    
    
    /**
     * get a flat array with all assets of a library from CDN API
     * 
     * @param string  $sLibrary  name of the library to ask for
     * @param strnmig $sVersion  optional: version of the library (default: version from local config)
     * @return array
     */
    public function getLibraryAssets($sLibrary, $sVersion=false) {
        $this->getLibraryMetadata($sLibrary);
        if(!$sVersion){
            $sVersion=isset($this->aLibs[$sLibrary]['_cdn']->version) ? $this->aLibs[$sLibrary]['_cdn']->version : false;
        }
        $this->_wd(__METHOD__ . ' version: '. $sVersion);
        if ($this->aLibs[$sLibrary]['_cdn']){
            foreach ($this->aLibs[$sLibrary]['_cdn']->assets as $aAsset){
                if($aAsset->version === $sVersion){
                    return $aAsset->files;
                }
            }
        }
        return false;
    }
    
    /**
     * get all versions of a library from CDN API
     * 
     * @param string  $sLibrary  name of the library to ask for
     * @param integer $iMax      optional: max count of return values; default: all
     * @return array
     */
    public function getLibraryVersions($sLibrary, $iMax=false) {
        $this->getLibraryMetadata($sLibrary);
        $aReturn=array();
        if ($this->aLibs[$sLibrary]['_cdn']){
            $iCount=0;
            foreach ($this->aLibs[$sLibrary]['_cdn']->assets as $aAsset){
                $iCount++;
                if (!$iMax || $iCount<=$iMax){
                    $aReturn[]=$aAsset->version;
                }
            }
        }
        return $aReturn;
    }

    // ----------------------------------------------------------------------
    // 
    // download library to local vendor dir
    // 
    // ----------------------------------------------------------------------
    
    /**
     * http download of assets
     * source
     * https://stackoverflow.com/questions/21362362/file-get-contents-from-multiple-url
     * 
     * @param array  $aAssets  array with array with keys "url" + "file"
     */
    protected function _httpGet($aAssets){
        // Your URL array that hold links to files 
        // $urls = array(); 

        // cURL multi-handle
        $mh = curl_multi_init();

        // This will hold cURLS requests for each file
        $requests = array();

        $options = array(
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_AUTOREFERER    => true, 
            CURLOPT_USERAGENT      => 'php curl',
            CURLOPT_HEADER         => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true
        );

        //Corresponding filestream array for each file
        $fstreams = array();

        foreach ($aAssets as $aItem){
            
            $sRemotefile=$aItem['url'];
            $sLocalfile=$aItem['file'];
            $sFileId=$aItem['file'];

            if(!is_dir(dirname($sLocalfile))){
                mkdir(dirname($sLocalfile), 755, 1);
            }

            // Add initialized cURL object to array
            $requests[$sFileId] = curl_init($sRemotefile);

            // Set cURL object options
            curl_setopt_array($requests[$sFileId], $options);

            // Open a filestream for each file and assign it to corresponding cURL object
            $fstreams[$sFileId] = fopen($sLocalfile, 'w');
            curl_setopt($requests[$sFileId], CURLOPT_FILE, $fstreams[$sFileId]);

            // Add cURL object to multi-handle
            curl_multi_add_handle($mh, $requests[$sFileId]);
        }

        // Do while all request have been completed
        do {
           curl_multi_exec($mh, $active);
        } while ($active > 0);

        // Collect all data here and clean up
        foreach ($requests as $sFileId => $request) {

            //$returned[$key] = curl_multi_getcontent($request); // Use this if you're not downloading into file, also remove CURLOPT_FILE option and fstreams array
            curl_multi_remove_handle($mh, $request); //assuming we're being responsible about our resource management
            curl_close($request);                    //being responsible again.  THIS MUST GO AFTER curl_multi_getcontent();
            fclose($fstreams[$sFileId]);
        }

        curl_multi_close($mh);

    }


    /**
     * get a filename for a json info file to store metadata of a single library
     * 
     * @param  string  $sLibrary  name of library
     * @return string
     */
    protected function _getInfoFilename($sLibrary){
        return $this->_getLocalfilename($sLibrary).'/.ht_info_cdnorlocal.json';
    }
    
    /**
     * get a filename for a json info file to store metadata of all libraries
     * 
     * @return string
     */
    protected function _getLocalLibsFile(){
        return $this->_getLocalfilename('').'/.ht_info_all_libs_cdnorlocal.json';
    }

    /**
     * store metadata of a library
     * 
     * @param  string  $sLibrary  name of library
     * @return boolean
     */
    protected function _putInfoFile($sLibrary){
        $sJsonfile=$this->_getInfoFilename($sLibrary);
        if(!is_dir(dirname($sJsonfile))){
            mkdir(dirname($sJsonfile), 755, 1);
        }
        return file_put_contents($sJsonfile, json_encode($this->aLibs[$sLibrary]));
    }

    /**
     * store fresh metadata of all downloaded libraries
     * 
     * @return boolean
     */
    protected function _putLocalLibsFile(){
        $sJsonfile=$this->_getLocalLibsFile();
        return file_put_contents($sJsonfile, json_encode($this->_reReadLibdirs()));
    }
    
    /**
     * get fresh data with reading  local vendor dir and find downloaded 
     * classes and versions
     * 
     * @return array
     */
    protected function _reReadLibdirs(){
        $sLocaldir=$this->_getLocalfilename('');
        $aReturn=array();
        if(is_dir($sLocaldir)){
            foreach (glob($sLocaldir . '*', GLOB_ONLYDIR) as $sLibdir){
                if (is_dir($sLibdir)){
                    $sLib2test=basename($sLibdir);
                    if(file_exists($this->_getInfoFilename($sLib2test))){
                        $aReturn[$sLib2test]=array();
                        foreach (glob($sLibdir . '/*', GLOB_ONLYDIR) as $sVersiondir){
                            if(!preg_match('/_in_progressXXXX/', $sVersiondir)){
                                $aReturn[$sLib2test][]=basename($sVersiondir);
                                krsort($aReturn[$sLib2test]);
                            }
                        }
                    }
                }
            }
        }
        ksort($aReturn);
        return $aReturn;
    }

    /**
     * recursively remove a directory; used in downloadAssets()
     * 
     * @param string  $dir  name of the directory
     */
    private function _rrmdir($dir) {
        foreach(glob($dir . '/*') as $file) {
            if(is_dir($file)){
                $this->_rrmdir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dir);
    }


    /**
     * download all files of a given library.
     * 
     * @param  string  $sLibrary  name of library
     * @return boolean
     */
    public function downloadAssets($sLibrary, $sVersion, $bForceDownload=false){
        $sRelUrl=$sLibrary.'/'.$sVersion;
        $sLocaldir=$this->_getLocalfilename($sRelUrl);
        
        // this version was downloaded already
        if (is_dir($sLocaldir)){
            return false;
        }
        $sTmpdir=$sLocaldir.'_in_progress';
        $bAllDownloaded=true;

        $iMaxFiles=50;
        $iFilesLeft=0;
        $iFilesTotal=count($this->getLibraryAssets($sLibrary));
        $this->_putInfoFile($sLibrary);
        $this->_putLocalLibsFile();
        
        // --- get the first N files...
        $aDownloads=array();
        foreach($this->getLibraryAssets($sLibrary) as $sFilename){
            if (!file_exists($sTmpdir.'/'.$sFilename) || !filesize($sTmpdir.'/'.$sFilename) ){
                if(count($aDownloads) < $iMaxFiles){
                    $aDownloads[]=array(
                        'url'=>$this->sCdnUrl.'/'.$sRelUrl.'/'.$sFilename,
                        'file'=>$sTmpdir.'/'.$sFilename,
                    );
                }
            }
        }
        // --- get the first N files...
        $this->_httpGet($aDownloads);
        
        // --- verify if all was downloaded
        foreach($this->getLibraryAssets($sLibrary) as $sFilename){
            if (!file_exists($sTmpdir.'/'.$sFilename)){
                $iFilesLeft++;
                $this->_wd(__METHOD__ . ' file still missing: '. $sTmpdir.'/'.$sFilename);
                $bAllDownloaded=false;
            }
        }
        
        // --- move tmpdir to final dir ... on incomplete downloads try to continue
        if($bAllDownloaded && is_dir($sTmpdir)){
            if(is_dir($sLocaldir)){
                $this->_wd(__METHOD__ . ' removing '. $sLocaldir);
                $this->_rrmdir($sLocaldir);
            }
            $this->_wd(__METHOD__ . ' move '. $sTmpdir.' to '.$sLocaldir);
            rename($sTmpdir, $sLocaldir);
        } else {
            $this->_wd(__METHOD__ . ' download not complete ???' . $bAllDownloaded ? ' all files were downloaded' : 'missing some files.');
            echo "downloading ".count($aDownloads)." files per request..."
                    . "<br>$iFilesLeft of ".$iFilesTotal." still left - ".round(($iFilesTotal-$iFilesLeft)/$iFilesTotal*100)."%<br>"
                    . 'Please wait ... or <a href="?">abort</a>'
                    . "<script>window.setTimeout('location.reload();', 2000);</script>";
            die();
        }
        $this->_putLocalLibsFile();
        echo "<script>window.setTimeout('location.reload();', 20);</script>";
        
        return true;
    }
    // ----------------------------------------------------------------------
    // 
    // get local directories
    // 
    // ----------------------------------------------------------------------

    

    /**
     * get an array with all downloaded libs and its versions
     * 
     * @param boolean  $bForceRefresh  force refresh
     * @return array
     */
    public function getLocalLibs($bForceRefresh=false){
        $sJsonfile=$this->_getLocalLibsFile();
        if (!file_exists($sJsonfile) || $bForceRefresh){
            $this->_putLocalLibsFile();
        }
        if (!file_exists($sJsonfile)){
            return false;
        }
        $aData=json_decode(file_get_contents($sJsonfile), 1);
        if(!$aData || !is_array($aData) || !count($aData)){
            return false;
        }
        return $aData;
    }
    
    // ----------------------------------------------------------------------
    // 
    // rendering
    // 
    // ----------------------------------------------------------------------

    /**
     * render an html table
     * 
     * @return string
     */
    public function renderTable4Usage(){
        $sReturn='';
        $sTable='';
        
        $aLocalLibs=$this->getLocalLibs();
        
        foreach($aLocalLibs as $sLibrary=>$aVersions){
            
            $sTable.='<tr>'
                    . '<td><a href="?action=detail&library='.$sLibrary.'&q="><i class="fa fa-suitcase"></i> '.$sLibrary.'</td>'
                    . '<td>'.implode('<br>', $aVersions).'</td>'
                    . '</tr>';
        }
        $sReturn.='<table class="pure-table">'
                . '<thead><tr>'
                . '<th>Library</th>'
                . '<th>Version(s)</th>'
                . '</tr></thead>'
                . '<tbody>'.$sTable.'</tbody></table>';
        return $sReturn;
    }
    
    
}
