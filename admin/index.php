<?php
/**
 * 
 * CDNORLOCAL  ::  ADMIN WEB GUI 
 * 
 * search & download libraries on CDNJS
 * check version of downloaded libraries
 * 
 */


require_once '../classes/cdnorlocal-admin.class.php';

$sOut = '';
$oCdn = new axelhahn\cdnorlocaladmin(array(
    'vendordir' => __DIR__ . '/../vendor',
    'vendorurl' => '../vendor',
    'debug' => 0
        ));

$sModule = getQueryparam('module', 'search');
$sAction = getQueryparam('action');
$sLibrarySearch = getQueryparam('q');
$sLibrary = getQueryparam('library');
$sVersion = getQueryparam('version');

// ----------------------------------------------------------------------
// CONFIG
// ----------------------------------------------------------------------

$aIcons=array(
    
    'nav-search'=>'fa fa-globe',
    'nav-browse'=>'fa fa-folder-open-o',
    
    'home'=>'fa fa-home',
    'search'=>'fa fa-search',
    'download'=>'fa fa-download',
    'info'=>'fa fa-info-circle',
    'author'=>'fa fa-user',
    'license'=>'fa fa-file-o',
    'library'=>'fa fa-suitcase',
    'marked'=>'fa fa-star',
    'version'=>'fa fa-flag',
    'files'=>'fa fa-file',
    'usage'=>'fa fa-hand-o-right',
    
    'go'=>'fa fa-check',
    'refresh'=>'fa fa-refresh',
    
    'ok'=>'fa fa-check',
    'warning'=>'fa fa-warning',
    'error'=>'fa fa-bolt',
    
    'linkextern'=>'fa fa-external-link',
);


$aNav=array(
    /*
    'home'=>array(
        'label'=>'Homepage',
        'descr'=>'Starting page of the admin',
    ),
     * 
     */
    'search'=>array(
        'label'=>'API Search',
        'descr'=>'Search for libraries with CDNJS API',
    ),
    'browse'=>array(
        'label'=>'Browse',
        'descr'=>'Browse local directory and see downloaded libraries.',
    ),
);

// ----------------------------------------------------------------------
// functions
// ----------------------------------------------------------------------

function getIcon($sIndex){
    global $aIcons;
    return $sIndex && array_key_exists($sIndex, $aIcons)?'<i class="fa '.$aIcons[$sIndex].'"></i> ':'';
}

/**
 * get html code for navi with highlighting of the current module
 * 
 * @global string  $sModule  module
 * @global array   $aIcons   icon definitions
 * @global array   $aNav     navigation defintion
 * @return string
 */
function renderNavi(){
    
    global $sModule, $aIcons, $aNav;
    
    $sReturn='<nav><ul>';
    foreach ($aNav as $sKey=>$aNavitem){
        $sReturn.='<li'
                . ($sModule===$sKey ? ' class="active"' : '')
                . '>'
                    . '<a href="?module='.$sKey.'"'
                        . ' title="'.$aNavitem['descr'].'"'
                    . '>'
                    . getIcon('nav-'.$sKey)
                    . ''.$aNavitem['label'].'</a>'
                . '</li>';
    }
    $sReturn.='</ul></nav><div style="clear: both;"></div><div class="hint">'.$aNav[$sModule]['descr'].'</div>';
    return $sReturn;
}

        
/**
 * render the local libs (the box on the right)
 * 
 * @global axelhahn\cdnorlocaladmin $oCdn  object
 * @global string $sLibrary                name of current library from GET param
 * 
 * @return string
 */
function renderLocalLibs($bSidebar=false){
    global $oCdn,$sLibrary,$sLibrarySearch,$sModule,$aIcons;
    $sReturn='';
    $sTable='';
    
    $sLibAction = (isset($_GET) && array_key_exists('libaction', $_GET)) ? $_GET['libaction'] : '';
    
    $sUrlCheck = $sLibAction==='checkversion' ? '#':  '?'.$_SERVER["QUERY_STRING"].'&libaction=checkversion';
    $sUrlRefresh= $sLibAction==='refresh' ? '#': '?'.$_SERVER["QUERY_STRING"].'&libaction=refresh';
    
    $aLocalLibs=$oCdn->getLocalLibs($sLibAction==='refresh');
    if(!$aLocalLibs || !count($aLocalLibs)){
        return $bSidebar 
            ? '' 
            : '<p>'
                . '<span class="info">'.getIcon('info').'INFO: No downloads so far.</span>'
                . '<br><br><a href="'.$sUrlRefresh.'" class="button" title="re-read local directories to scan downloaded libs">'.getIcon('refresh').'Refresh</a>'
                . '</p>';
    }

    
    $sLastLibrary='';
    foreach($aLocalLibs as $sMyLibrary=>$aVersions){
        $sLatestVersion=false;

        if($sLibAction==='checkversion'){
            $aApidata=$oCdn->getLibraryMetadata($sMyLibrary);
            $sLatestVersion=$aApidata['version'];
        }
            foreach($aVersions as $sLibversion){
                $sDateOfDownload='';
                if(!$bSidebar){
                    $aTmp=stat($oCdn->getLocalfile($sMyLibrary.'/'.$sLibversion));
                    if ($aTmp && is_array($aTmp)){
                        $sDateOfDownload=date('Y-m-d H:i', $aTmp['mtime']);
                    }
                }
                $sTable.='<tr'
                        .($sLibrary===$sMyLibrary ? ' class="current"' : '') 
                        . '>'
                    . '<td>'
                        . ($sLastLibrary!=$sMyLibrary 
                            ? '<a href="?module=search&action=detail&library='.$sMyLibrary.'&q='.$sLibrarySearch.'" title="Show details library &quot;'.$sMyLibrary.'&quot;">'.getIcon('library').$sMyLibrary.'</a>'
                            : ''
                    )
                    . '</td>'
                    . '<td>'
                    ;
                
                if(preg_match('/_in_progress/', $sLibversion)){
                    $sTable.=''
                            . '<a href="?module=search&action=download&library='.$sMyLibrary.'&version='.str_replace('_in_progress', '', $sLibversion).'&q='.$sLibrarySearch.'" title="Continue download">'.getIcon('version').$sLibversion.'</a> '
                            ;
                } else {
                $sTable.=''
                        . '<a href="?module=search&action=detail&library='.$sMyLibrary.'&version='.$sLibversion.'&q='.$sLibrarySearch.'" title="Show details for version '.$sLibversion.' of &quot;'.$sMyLibrary.'&quot;">'.getIcon('version').$sLibversion.'</a> '
                        . ($sLatestVersion && $sLatestVersion===$sLibversion ? '<span class="ok">'.getIcon('ok').'Up to date</span>' : '')
                        . ($sLatestVersion && version_compare($sLatestVersion, $sLibversion, '>') ? '<span class="warning">'.getIcon('').'outdated</span> &lt; '.getIcon('version').$sLatestVersion.'' : '')
                        . (!$bSidebar ? '<td>'.$sDateOfDownload.'</td>' : '')
                        ;
                }
                $sLastLibrary=$sMyLibrary;
            }
        $sTable.='</td>'
            . '</tr>';
    }
    
    $sReturn.=
            ($bSidebar
                ? '<br><strong>'.getIcon('download').'Downloaded Libraries</strong><br><br>'
                : '<h2>'.getIcon('download').'Downloaded Libraries</h2>'
            )
            . ($sTable 
                ? '<table class="pure-table">'
                . ($bSidebar
                    ? ''
                    : '<thead><tr>'
                        . '<th>Library</th>'
                        . '<th>Version</th>'
                        . (!$bSidebar ? '<th>Date of download</th>' : '')
                    . '</tr></thead>'
                    )
                . '<tbody>'.$sTable.'</tbody></table>'
                . '<br>'
                . ($sLibAction || $bSidebar ? '': '<a href="'.$sUrlCheck .'" class="button" title="Online check if local versions are still up to date">'.getIcon('refresh').'Check all versions</a> ')
            : ''
            )
            ;
    if(!$bSidebar){
        $sReturn.=($sLibAction  
                ? ''
                : '<a href="'.$sUrlRefresh.'" class="button" title="re-read local directories to scan downloaded libs">'.getIcon('refresh').'Refresh</a><br><br>')
            ;
    } else {
        $sReturn='<div id="mylibs">'.$sReturn.'</div>';
    }
    return $sReturn;
}

/**
 * get a GET param
 * @param type $sParam
 * @return type
 */
function getQueryparam($sParam, $default=false){
    return (isset($_GET) && array_key_exists($sParam, $_GET)) 
            ? preg_replace('/[^a-zA-Z0-9\ \_\-\.]/', '', $_GET[$sParam])
            : $default
        ;
}

/**
 * get html code to show an error message
 * 
 * @param string $sMessage  message text
 * @return string 
 */
function showError($sMessage){
    return '<br><br><span class="error">'.getIcon('error')
        . 'PANIC ERROR: '.$sMessage.'</span><br><br><br>'
        . 'You can try to go <a href="javascript: history.back()">back</a>.<br>'
        . 'If you feel completely helpless ... here is the safe way <a href="?">home</a> ...';    
}

// ----------------------------------------------------------------------
// read query params
// ----------------------------------------------------------------------
/*
$sAction = (isset($_GET) && array_key_exists('action', $_GET)) ? $_GET['action'] : '';
$sLibrarySearch = (isset($_GET) && array_key_exists('q', $_GET)) ? $_GET['q'] : '';
$sLibrary = (isset($_GET) && array_key_exists('library', $_GET)) ? $_GET['library'] : '';
$sVersion = (isset($_GET) && array_key_exists('version', $_GET)) ? $_GET['version'] : '';
 */


// ----------------------------------------------------------------------
// handle other actions
// ----------------------------------------------------------------------

$aLocalLibs=$oCdn->getLocalLibs();
switch ($sModule) {
    case 'home':

        break;
    
    case 'search':
        $sOut.=renderLocalLibs(1);
        $sTryme='';
        if(!$sLibrarySearch && !$sLibrary){
            $sOut.='<h2>'.getIcon('search').'Search</h2>';
            $sTryme.='<p>
                You have no idea?!<br>
                Try one of these ...';
            foreach (array(
                'jquery', 
                'bootstrap', 
                'chart', 
                'angular', 
                'icon', 
                'player', 
                'video',
            ) as $sTry){
                $sTryme.='<a href="?module=search&q='.$sTry.'" title="search for &quot;'.$sTry.'&quot;">'.getIcon('search').$sTry.'</a> ';
            }
            $sTryme.='</p>';
        }
        $sOut .= '
            <p>
                Search for a javascript library on CDNJS here:
            </p>
            <form action="?">
                <input type="hidden" name="module" value="search">
                <input type="hidden" name="action" value="search">
                <input type="text" size="20" name="q" value="' . $sLibrarySearch . '" autofocus>
                <button class="search" type="submit" title="search for library">'.getIcon('search').'search</button> '
                . ($sLibrarySearch ? '<button class="reset" onclick="document.location.href=\'?module=search\'; return false;" title="remove search term">&nbsp;&nbsp;X&nbsp;&nbsp;</button>' : ''
                ) . '                
            </form>'
            .$sTryme
        ;
        switch ($sAction) {
            // ----------------------------------------------------------------------
            case false:
            case 'search':



                if ($sLibrarySearch) {
                    $aSearchResult = $oCdn->searchLibrary($sLibrarySearch);
                    $sOut .= '<h2>'.getIcon('ok').'Results: ' . $aSearchResult->total . '</h2>';

                    if ($aSearchResult->total) {
                        $sOut .= '<ol>';

                        foreach ($aSearchResult->results as $aResult) {
                            $sOut .= '<li>'
                                    . '<strong>'.getIcon('library').'<a href="?module=search&action=detail&library=' . $aResult->name . '&q=' . $sLibrarySearch . '" title="show details for &quot;'.$aResult->name.'&quot;">' . $aResult->name . '</a></strong>'
                                    . ' (' . $aResult->version . ')'
                                    . ($aLocalLibs && array_key_exists($aResult->name, $aLocalLibs) ? ' '.getIcon('marked') : '')
                                    . '<br>'
                                    . htmlentities($aResult->description) . '<br>'
                                    . '</li>';
                        }
                        $sOut .= '</ol>';
                    }
                }
                break;

            // ----------------------------------------------------------------------
            case 'download':
                $oCdn->downloadAssets($sLibrary, $sVersion);

            case 'detail':
                $aMeta = $oCdn->getLibraryMetadata($sLibrary);
                if(!$aMeta){
                    $sOut .= showError('A library named ['.$sLibrary.'] was not found.');
                    break;
                }
                $sOut.='<h2>'.getIcon('library').$sLibrary 
                        . ($aLocalLibs && array_key_exists($sLibrary, $aLocalLibs) ? ' '.getIcon('marked') : '')
                        . '</h2>'
                        ;

                $sLicence=(is_object($aMeta['license']) ? $aMeta->license : $aMeta['license']);
                $sAuthor=(is_object($aMeta['author']) ? $aMeta['author']->name : '');

                if (!$sVersion) {
                    $sVersion = $aMeta['version'];
                    $sOut .= '(current version: ' . $sVersion.')';
                } else {
                    $sOut .= '(version ' . $sVersion.')';
                }
                $sOut .= '<br><br>';
                $aFiles = $oCdn->getLibraryAssets($sLibrary, $sVersion);
                if(!$aFiles || !count($aFiles)){
                    $sOut .= showError('The version ['.$sVersion.'] seems to be wrong (no file was detected).')
                            . '<br><br><br>'
                            . '<a href="?module=search&action=detail&library='.$sLibrary.'" class="button">current version of '.getIcon('library').'<strong>'.$sLibrary.'</strong></a>'
                            ;
                    break;
                }
                

                $sFirstFile=$sLibrary . '/' . $sVersion . '/' . ($aMeta['filename'] ? $aMeta['filename'] : $aFiles[0]);
                
                $sDownload = ($oCdn->getLocalfile($sFirstFile)) 
                        ? '<span class="ok">'.getIcon('ok').'Library <strong>'.$sLibrary.' v'.$sVersion.'</strong> was downloaded already.</span><br>see ' . $oCdn->sVendorDir 
                        : ''
                            . 'Here you can download all files (they are listed below) from CDNJS to your local vendor directory ('.$oCdn->sVendorDir.').<br><br>'
                            .'<a href="?module=search&action=download&library=' . $sLibrary . '&version=' . $sVersion . '&q='.$sLibrarySearch.'" class="button download" title="Start download">'.getIcon('download').'Download <strong>'.$sLibrary.'</strong> v'.$sVersion.'</a><br><br>'
                            . ($aMeta['version'] !== $sVersion ? ' <span class="warning">'.getIcon('warning').'Warning: This is not the latest version of this library!</span><br>' : '')
                            . (preg_match('/rc/i', $sVersion) ? ' <span class="warning">'.getIcon('warning').'Warning: This version is a RELEASE CANDIDATE - not a final version.</span><br>' : '')
                            . (preg_match('/beta/i', $sVersion) ? ' <span class="warning">'.getIcon('warning').'Warning: This version is a BETA release - not a final version.</span><br>' : '')
                            . (preg_match('/alpha/i', $sVersion) ? ' <span class="warning">'.getIcon('warning').'Warning: This version is an ALPHA release - not a final version.</span><br>' : '')
                            . (preg_match('/^0\./', $sVersion) ? ' <span class="warning">'.getIcon('warning').'Warning: This version is a 0.x release - not a final version.</span><br>' : '')
                            . (count($aFiles)>100 ? ' <span class="warning">'.getIcon('warning').'Warning: Many files detected. Maybe you need to wait a longer time. On Timeout error: just reload the page to continue downloading still missing files.</span><br>' : '')
                            . ($oCdn->getLocalfile($sLibrary . '/' . $sVersion.'_in_progress' ) ? ' <span class="warning">'.getIcon('warning').'Warning: An incomplete download was detected. Clicking on download fetches still missing files.</span><br>' : '')
                ;

                $sShowVersions = '';

                $sOptVersions = '';
                $aAllversions = $oCdn->getLibraryVersions($sLibrary);
                if (count($aAllversions) > 1) {
                    foreach ($oCdn->getLibraryVersions($sLibrary) as $v) {
                        $sOptVersions .= '<option value="' . $v . '"'
                            . ($v === $sVersion ? ' selected="selected"' : '')
                            . '>' . $v 
                            . ($v === $aMeta['version'] ? ' (latest)' : '')
                            . '</option>';
                    }
                    $sShowVersions .= '<h3>'.getIcon('version').'Versions (' . (count($oCdn->getLibraryVersions($sLibrary))) . ')</h3>'
                        . '<form action="?">'
                        . '<input type="hidden" name="module" value="search">'
                        . '<input type="hidden" name="action" value="detail">'
                        . '<input type="hidden" name="q" value="' . $sLibrarySearch . '">'
                        . '<input type="hidden" name="library" value="' . $sLibrary . '">'
                        . '<select name="version">' . $sOptVersions . '</select>'
                        . '<button title="switch to the selected version">'.getIcon('go').'Go</button>'
                        . '</form>'
                    ;
                } else {
                    $sShowVersions .= (count($aAllversions)
                            ? '<h3>'.getIcon('version').'Version</h3><strong>'.$aAllversions[0].'</strong> (this is the only version)'
                            : '<h3>Version seems to be wrong: '.$sVersion.'</h3>'
                            );
                }

                $sOut .= ''
                        . '<h3>'.getIcon('info').'Infos</h3>'
                        . '<p>'
                        . '<strong>'.$aMeta['description'] . '</strong><br><br>'
                        . getIcon('home').'Homepage: <a href="' . $aMeta['homepage'] . '" target="_blank">' . $aMeta['homepage'] . '</a><br>'
                        . (!$aMeta['homepage'] ? '<span class="warning">'.getIcon('warning').'Warning: This project has no homepage</span><br><br>': '')

                        . getIcon('author').'Author: ' . $sAuthor . '<br>'
                        . (!$sAuthor ? '<span class="warning">'.getIcon('warning').'Warning: The author was not detected</span><br><br>': '')

                        . getIcon('license').'Licence: ' . $sLicence . '<br>'
                        . (!$sLicence ? '<span class="warning">'.getIcon('warning').'Warning: The lience was not detected</span><br><br>': '')

                        . getIcon('version').'Latest version: ' . $aMeta['version'] . '<br>'
                        . '<br><a href="https://cdnjs.com/libraries/'.$sLibrary.'" target="_blank">'.getIcon('linkextern'). ' library '.$sLibrary.' on cdnjs</a><br>'
                        . '</p>'
                        . $sShowVersions

                        . '<h3>'.getIcon('download').'Download</h3>'
                        . $sDownload

                        . '<h3>'.getIcon('files').'Files</h3>'
                        . 'Files (in the version ' . $sVersion
                        . ($sVersion == $aMeta['version'] ? ' [latest]' : '')
                        . '): <strong>' . count($aFiles) . '</strong><br>'

                        . (count($aFiles) ? ' - ' . implode('<br> - ', $aFiles)
                        . '<br>'
                        . '<h3>'.getIcon('usage').'Usage</h3>'
                        . 'Example'
                        . ($aMeta['filename'] ? '' :' (just guessing - I take the first file from filelist)')
                        . ':'
                        . '<pre>'
                        . '<strong>'
                        . '$oCdn = new axelhahn\cdnorlocal()<br>'
                        . 'echo $oCdn->getHtmlInclude(&quot;' . $sFirstFile . '&quot;)'
                        . '</strong><br><br>returns:<br>'
                        . htmlentities($oCdn->getHtmlInclude($sFirstFile))
                        . '</pre>' : ''
                        )
                        . '</p>'
                        /*
                        . '<h3>Debug</h3>'
                        . '<pre>'
                        . '<strong>'
                        . '$oCdn = new axelhahn\cdnorlocaladmin([options])<br>'
                        . '$oCdn->getLibraryMetadata(&quot;' . $sLibrary . '&quot;)'
                        . '</strong><br>'
                        . print_r($oCdn->getLibraryMetadata($sLibrary), 1)
                        . '</pre>'
                        . '<pre>'
                        . '<strong>$oCdn->getLibraryAssets(&quot;' . $sLibrary . '&quot;)</strong><br>'
                        . print_r($oCdn->getLibraryAssets($sLibrary), 1)
                        . '</pre>'
                        . '<pre>'
                        . '<strong>$oCdn->getLibraryVersions(&quot;' . $sLibrary . '&quot;, 10)</strong><br>'
                        . print_r($oCdn->getLibraryVersions($sLibrary, 10), 1)
                        . '</pre>'
                         */
                ;
                break;

            default:
                break;
        }
        break;
    case 'browse':
        $sOut.=renderLocalLibs();
        break;
}
?>
<!doctype html>
<html>
    <style>
        body{background: #304048; 
             background: linear-gradient(#405080, #305060, #102030) fixed; 
             color: #9bd; font-family: verdana,"arial"; margin: 0;}
        a{color:#5ce;}
        a:hover{color:#aff;}
        h1{background:rgba(0,0,0,0.2); border-radius: 0.5em 0.5em 0 0 ; color:#a9f; text-shadow: 1px 1px 2px #000, 0 0 0.8em #abc; font-size: 250%; margin: 0; padding: 0.5em;}
        h2{color:#ae5; font-size: 240%;text-shadow: 1px 1px 0 #000, 0 0 0.7em #000;}
        h3{color:#a6b; font-size: 220%; margin: 1.5em 0 0 0;text-shadow: 1px 1px 0 #000;}
        li{padding: 0.5em; transition: ease-in-out 0.2s;}
        tr:hover,li:hover{background: rgba(255,255,255,0.03)}
        a.button, button{background: #49a; border: 3px solid rgba(0,0,0,0.3); border-radius: 0.3em; color: #245; font-size: 100%; padding: 0.5em; text-decoration: none; }
        a.button:hover,button:hover{color:#fff;}
        a.download, button.search{background: #3d8;}
        button.reset{background: #eaa;}
        input, select{background: rgba(0,0,0,0.1); border: 3px solid rgba(0,0,0,0.3); border-radius: 0.3em; color: #499; font-weight: bold; font-size: 100%; padding: 0.5em;}
        nav>ul{padding: 0;}
        nav>ul>li{float: left; list-style: none; margin: 0 0.3em 0 0; padding: 0; background: rgba(0,0,0,0.1);}
        nav>ul>li>a{padding: 1em; display: block; text-decoration: none;}
        .hint, nav>ul>li.active{background: rgba(0,0,0,0.4);}
        nav>ul>li.active{border-top: 2px solid #9c2;}
        pre{background:rgba(0,0,0,0.1); padding: 0.5em;}
        pre>strong{color:#7ac;}
        
        table{border-spacing: 0;}
        th{background: rgba(0,0,0,0.1); padding: 1em;}
        td{vertical-align: top;}
        td{border-bottom: 1px solid rgba(255,255,255,0.02); padding: 0.4em;}
        
        #main{background:rgba(0,0,0,0.1); border-radius: 1em; margin: 2em auto 0; padding: 1em;width: 90%; }
        #footer{background:rgba(255,255,255,0.03); border-radius: 1em; margin: 4em auto 3em; padding: 1em; text-align: center; width: 60%; }
        #mylibs{float: right; background:rgba(0,0,0,0.1); border-radius: 1em; margin: 1em auto; padding: 1em;}
        .hint{padding: 1em;}
        .info{color:#ec3;}
        .warning{color:#ec3; }
        .error{background:#422; border: 2px solid; box-shadow: 0 0 2em #a00; color:#e33; margin: 0.5m; padding: 0.5em; }
        .ok{color:#4c4; }
        .fa-star{color:#ec3; }
        .current{background: rgba(0,0,0,0.4); }
    </style>
    <?php
        // echo $oCdn->getHtmlInclude('jquery/jquery.min.js');
        echo $oCdn->getHtmlInclude('font-awesome/4.7.0/css/font-awesome.min.css');
    ?>
    <body>
        <div id="main">
            <h1>CDN OR LOCAL :: admin</h1>

            <?php 
                echo renderNavi().$sOut; 
            ?>
            <div style="clear: both;"></div>
        </div>
        <div id="footer">
            &copy; 2017 <a href="https://www.axel-hahn.de/" target="_blank" title="Website of the author (German)"><?php echo getIcon('linkextern')?>Axel Hahn</a>
            ... watch my project <a href="https://github.com/axelhahn/cdnorlocal" target="_blank" title="Project page on Github"><?php echo getIcon('linkextern')?>cdnorlocal on Github</a>
            | <a href="https://cdnjs.com/" target="_blank" title="CDN hoster cdnjs"><?php echo getIcon('linkextern')?>cdnjs.com</a>
        </div>

    </body>
</html>