<?php
/**
 * 
 * CDNORLOCAL  ::  ADMIN WEB GUI 
 * 
 * search & download libraries on CDNJS
 * 
 */

require_once '../classes/cdnorlocal-admin.class.php';

$sOut = '';
$oCdn = new axelhahn\cdnorlocaladmin(array(
    'vendordir' => __DIR__ . '/../vendor',
    'vendorurl' => '../vendor',
    'debug' => 0
        ));



// ----------------------------------------------------------------------
// functions
// ----------------------------------------------------------------------

/**
 * render the local libs (the box on the right)
 * 
 * @global axelhahn\cdnorlocaladmin $oCdn  object
 * @global string $sLibrary                name of current library from GET param
 * 
 * @return string
 */
function renderLocalLibs(){
    global $oCdn,$sLibrary;
    $sReturn='';
    $sTable='';
    
    $sLibAction = (isset($_GET) && array_key_exists('libaction', $_GET)) ? $_GET['libaction'] : '';
    
    $aLocalLibs=$oCdn->getLocalLibs($sLibAction==='refresh');

    
    if($aLocalLibs && count($aLocalLibs)){
        foreach($aLocalLibs as $sMyLibrary=>$aVersions){
            $sLatestVersion=false;
            if($sLibAction==='checkversion'){
                $aApidata=$oCdn->getLibraryMetadata($sMyLibrary);
                $sLatestVersion=$aApidata['version'];
            }
            $sTable.='<tr'
                    .($sLibrary===$sMyLibrary ? ' class="current"' : '') 
                    . '>'
                . '<td><a href="?action=detail&library='.$sMyLibrary.'&q=" title="Show details for this library"><i class="fa fa-suitcase"></i> '.$sMyLibrary.'</a></td>'
                . '<td>'
                . ($sLibAction==='checkversion' ? 'latest: <i class="fa fa-flag"></i> '.$sLatestVersion.'<br>' : '')
                ;
                foreach($aVersions as $sLibversion){
                    if(preg_match('/_in_progress/', $sLibversion)){
                        $sTable.=''
                                . '<a href="?action=download&library='.$sMyLibrary.'&version='.str_replace('_in_progress', '', $sLibversion).'&q=" title="Continue download"><i class="fa fa-flag"></i> '.$sLibversion.'</a> '
                                ;
                    } else {
                    $sTable.=''
                            . '<a href="?action=detail&library='.$sMyLibrary.'&version='.$sLibversion.'&q=" title="Show details for this version"><i class="fa fa-flag"></i> '.$sLibversion.'</a> '
                            . ($sLatestVersion && $sLatestVersion===$sLibversion ? '<span class="ok"><i class="fa fa-check"></i> Up to date</span>' : '')
                            . ($sLatestVersion && version_compare($sLatestVersion, $sLibversion, '>') ? '<span class="warning"><i class="fa fa-warning"></i> outdated</span>' : '')
                            ;
                    }
                    $sTable.=' '
                            
                            // . '<a href="#" onclick="if (confirm(\'delete '.$sMyLibrary.' v'.$sLibversion.'?\')) {location.href=\'?action=delete&library='.$sMyLibrary.'&version='.$sLibversion.'&q=\'};" class="delete">x</a>'
                            . '<br>';
                }
            $sTable.='</td>'
                . '</tr>';
        }
    }
    $sUrlCheck = $sLibAction==='checkversion' ? '#':  '?'.$_SERVER["QUERY_STRING"].'&libaction=checkversion';
    $sUrlRefresh= $sLibAction==='refresh' ? '#': '?'.$_SERVER["QUERY_STRING"].'&libaction=refresh';
    $sReturn.='<div id="mylibs">'
            . '<br><strong><i class="fa fa-download"></i> Downloaded Libraries</strong><br><br>'
            . ($sTable 
                ? '<table class="pure-table">'
                . '<tbody>'.$sTable.'</tbody></table>'
                . '<br>'
                . ($sLibAction ? '': '<a href="'.$sUrlCheck .'" class="button" title="Online check if local versions are still up to date"><i class="fa fa-refresh"></i> Check all versions</a> ')
            : ''
            )
            . ($sLibAction ? '<a href="?" class="button"><i class="fa fa-home"></i> Home</a>': '<a href="'.$sUrlRefresh.'" class="button" title="re-read local directories to scan downloaded libs"><i class="fa fa-refresh"></i> Refresh</a><br><br>')
            . '</div>';
    return $sReturn;
}

/**
 * get a GET param
 * @param type $sParam
 * @return type
 */
function getQueryparam($sParam){
    return (isset($_GET) && array_key_exists($sParam, $_GET)) 
            ? preg_replace('/[^a-zA-Z0-9\ \_\-\.]/', '', $_GET[$sParam])
            : false
        ;
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
$sAction = getQueryparam('action');
$sLibrarySearch = getQueryparam('q');
$sLibrary = getQueryparam('library');
$sVersion = getQueryparam('version');


// ----------------------------------------------------------------------
// handle other actions
// ----------------------------------------------------------------------
$aLocalLibs=$oCdn->getLocalLibs();
switch ($sAction) {
    // ----------------------------------------------------------------------
    case false:
    case 'search':

        $sTryme='';
        if(!$sLibrarySearch){
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
                $sTryme.='<a href="?q='.$sTry.'"><i class="fa fa-search"></i> '.$sTry.'</a> ';
            }
            $sTryme.='</p>';
        }
        
        $sOut .= '<h2>Search</h2>
            <p>
                Search for a javascript library on CDNJS here:
            </p>
            <form action="?">
                <input type="hidden" name="action" value="search">
                <input type="text" size="20" name="q" value="' . $sLibrarySearch . '" autofocus>
                <button class="search" type="submit"><i class="fa fa-search"></i> search</button> '
                . ($sLibrarySearch ? '<button class="reset" onclick="document.location.href=\'?\'; return false;">&nbsp;&nbsp;X&nbsp;&nbsp;</button>' : ''
                ) . '                
            </form>'
            .$sTryme
        ;

        if ($sLibrarySearch) {
            $aSearchResult = $oCdn->searchLibrary($sLibrarySearch);
            $sOut .= '<h2><i class="fa fa-check"></i> Results: ' . $aSearchResult->total . '</h2>';

            if ($aSearchResult->total) {
                $sOut .= '<ol>';
                
                foreach ($aSearchResult->results as $aResult) {
                    $sOut .= '<li>'
                            . '<strong><i class="fa fa-suitcase"></i> <a href="?action=detail&library=' . $aResult->name . '&q=' . $sLibrarySearch . '">' . $aResult->name . '</a></strong>'
                            . ' (' . $aResult->version . ')'
                            . ($aLocalLibs && array_key_exists($aResult->name, $aLocalLibs) ? ' <span class="star"><i class="fa fa-star"></i></span>' : '')
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

        $sLicence=(is_object($aMeta['license']) ? $aMeta->license : $aMeta['license']);
        $sAuthor=(is_object($aMeta['author']) ? $aMeta['author']->name : '');
        
        if (!$sVersion) {
            $sVersion = $aMeta['version'];
        }
        $aFiles = $oCdn->getLibraryAssets($sLibrary, $sVersion);

        $sFirstFile = $sLibrary . '/' . $sVersion . '/' . $aFiles[0];
        $sDownload = ($oCdn->getLocalfile($sFirstFile)) 
                ? '<span class="ok"><i class="fa fa-check"></i> Library <strong>'.$sLibrary.' v'.$sVersion.'</strong> was downloaded.</span><br>see ' . $oCdn->sVendorDir 
                : ''
                    . 'Here you can download all files (they are listed below) from CDNJS to your local vendor directory ('.$oCdn->sVendorDir.').<br><br>'
                    .'<a href="?action=download&library=' . $sLibrary . '&version=' . $sVersion . '&q='.$sLibrarySearch.'" class="button"><i class="fa fa-download"></i> Download <strong>'.$sLibrary.'</strong> v'.$sVersion.'</a><br><br>'
                    . ($aMeta['version'] !== $sVersion ? ' <span class="warning"><i class="fa fa-warning"></i> Warning: This is not the latest version of this library!</span><br>' : '')
                    . (preg_match('/rc/i', $sVersion) ? ' <span class="warning"><i class="fa fa-warning"></i> Warning: This version is a RELEASE CANDIDATE - not a final version.</span><br>' : '')
                    . (preg_match('/beta/i', $sVersion) ? ' <span class="warning"><i class="fa fa-warning"></i> Warning: This version is a BETA release - not a final version.</span><br>' : '')
                    . (preg_match('/alpha/i', $sVersion) ? ' <span class="warning"><i class="fa fa-warning"></i> Warning: This version is an ALPHA release - not a final version.</span><br>' : '')
                    . (preg_match('/^0\./', $sVersion) ? ' <span class="warning"><i class="fa fa-warning"></i> Warning: This version is a 0.x release - not a final version.</span><br>' : '')
                    . (count($aFiles)>100 ? ' <span class="warning"><i class="fa fa-warning"></i> Warning: Many files detected. Maybe you need to wait a longer time. On Timeout error: just reload the page to continue downloading still missing files.</span><br>' : '')
                    . ($oCdn->getLocalfile($sLibrary . '/' . $sVersion.'_in_progress' ) ? ' <span class="warning"><i class="fa fa-warning"></i> Warning: An incomplete download was detected. Clicking on download fetches still missing files.</span><br>' : '')
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
            $sShowVersions .= '<h3><i class="fa fa-flag"></i> Versions (' . (count($oCdn->getLibraryVersions($sLibrary))) . ')</h3>'
                . '<form action="?">'
                . '<input type="hidden" name="action" value="detail">'
                . '<input type="hidden" name="q" value="' . $sLibrarySearch . '">'
                . '<input type="hidden" name="library" value="' . $sLibrary . '">'
                . '<select name="version">' . $sOptVersions . '</select>'
                . '<button><i class="fa fa-check"></i> Go</button>'
                . '</form>'
            ;
        } else {
            $sShowVersions .= (count($aAllversions)
                    ? '<h3><i class="fa fa-flag"></i> Version</h3><strong>'.$aAllversions[0].'</strong> (this is the only version)'
                    : '<h3>Version seems to be wrong: '.$sVersion.'</h3>'
                    );
        }
        
        $sOut .= ''
                . '<br><a href="?action=search&q=' . $sLibrarySearch . '" class="button"><i class="fa fa-chevron-left"></i> back</a><br>'
                . '<h2><i class="fa fa-suitcase"></i> ' . $sLibrary 
                . ($aLocalLibs && array_key_exists($sLibrary, $aLocalLibs) ? ' <span class="star"><i class="fa fa-star"></i></span>' : '')
                . '</h2>'
                . '<h3><i class="fa fa-info-circle"></i> Infos</h3>'
                . '<p>'
                . '<strong>'.$aMeta['description'] . '</strong><br><br>'
                . '<i class="fa fa-home"></i> Homepage: <a href="' . $aMeta['homepage'] . '" target="_blank">' . $aMeta['homepage'] . '</a><br>'
                . (!$aMeta['homepage'] ? '<span class="warning"><i class="fa fa-warning"></i> Warning: This project has no homepage</span><br><br>': '')

                . '<i class="fa fa-user"></i> Author: ' . $sAuthor . '<br>'
                . (!$sAuthor ? '<span class="warning"><i class="fa fa-warning"></i> Warning: The author was not detected</span><br><br>': '')

                . '<i class="fa fa-file-o"></i> Licence: ' . $sLicence . '<br>'
                . (!$sLicence ? '<span class="warning"><i class="fa fa-warning"></i> Warning: The lience was not detected</span><br><br>': '')

                . '<i class="fa fa-flag"></i> Latest version: ' . $aMeta['version'] . '<br>'
                . '</p>'
                . $sShowVersions

                . '<h3><i class="fa fa-download"></i> Download</h3>'
                . $sDownload

                . '<h3><i class="fa fa-file"></i> Files</h3>'
                . 'Files (in the version ' . $sVersion
                . ($sVersion == $aMeta['version'] ? ' [latest]' : '')
                . '): <strong>' . count($aFiles) . '</strong><br>'

                . (count($aFiles) ? ' - ' . implode('<br> - ', $aFiles)
                . '<br>'
                . '<h3><i class="fa fa-hand-o-right"></i> Usage</h3>'
                . 'Example (just guessing and taking the first file):'
                . '<pre>'
                . '<strong>'
                . '$oCdn = new axelhahn\cdnorlocal([options])<br>'
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
?>
<!doctype html>
<html>
    <style>
        body{background: #304048; background: linear-gradient(#405060, #304048) fixed; color: #9bb; font-family: "arial";}
        a{color:#5ce;}
        a:hover{color:#aff;}
        h1{background:rgba(0,0,0,0.1); border-radius: 0.5em; color:#ec3; color:rgba(200,220,255,0.4); font-size: 250%; margin: 0; padding: 0.5em;}
        h2{color:#ae5; font-size: 240%;}
        h3{color:#a7a; font-size: 220%; margin: 1.5em 0 0 0;}
        li{padding: 0.5em; transition: ease-in-out 0.2s;}
        tr:hover,li:hover{background: rgba(255,255,255,0.03)}
        a.button, button{background: #4cd; border: 3px solid rgba(0,0,0,0.3); border-radius: 0.3em; color: #256; font-size: 100%; padding: 0.5em; text-decoration: none; }
        a.button:hover,button:hover{color:#fff;}
        button.search{background: #8c8;}
        button.reset{background: #eaa;}
        input, select{background: rgba(0,0,0,0.1); border: 3px solid rgba(0,0,0,0.3); border-radius: 0.3em; color: #499; font-weight: bold; font-size: 100%; padding: 0.5em;}
        pre{background:rgba(0,0,0,0.1); padding: 0.5em;}
        pre>strong{color:#7ac;}
        td{vertical-align: top;}
        td{border-bottom: 1px solid #345; padding: 0.4em;}
        #main{background:rgba(0,0,0,0.1); border-radius: 1em; margin: 2em auto; padding: 1em;width: 90%; }
        #mylibs{float: right; background:rgba(0,0,0,0.1); border-radius: 1em; margin: 1em auto; padding: 1em;}
        .warning{color:#ec3; }
        .ok{color:#4c4; }
        .star{color:#ec3; }
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
                    echo renderLocalLibs() . $sOut; 
                ?>
                <div style="clear: both;"></div>
        </div>

    </body>
</html>