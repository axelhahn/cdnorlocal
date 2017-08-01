-------------------------------------------------------------------------------

CDN OR LOCAL

load a ressource from CDNJS or from own domain (if it exist)

With this you can
- load a library from CDNJS or from your own domain (if it exist)
- an admin web gui can search libraries on CDNJS and download several 
  versions of it

I want to use it in my php apps to link cdnjs for smaller download size
and give the possibility to run without internet connection too.
And: I wanted to have a tool to check the versions of libraries I use.

Licence GPL 3.0

author: Axel Hahn

DOCS: https://www.axel-hahn.de/docs/cdnorlocal/index.htm
SOURCE: https://github.com/axelhahn/cdnorlocal

-------------------------------------------------------------------------------
 
USAGE:

(1) In your webapp - load css or js fromcdnjs
    You only need the single file "cdnorlocal.class.php"
	
	The structure of all libraries is 
	[name of the lib]/ [version] / [path+filename]

	require_once('[PATH]/cdnorlocal.class.php');
	
	$oCdn = new axelhahn\cdnorlocal();
	echo $oCdn->getHtmlInclude("jquery/3.2.1/jquery.min.js")

	It returns:
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

(2) Include a non CSS or JS file ... or customize the <script> or <link> tag.
	Use the method getFullUrl() to get the url only.

	$oCdn = new axelhahn\cdnorlocal();
	echo '<script src="'.$oCdn->getFullUrl("jquery/3.2.1/jquery.min.js").'" (...)></script>'
	
(3) In a webapp - load css or js fromcdnjs or own domain
    It requires to have a local directory where the libraries are.
	Default is the url "/vendor" and "[webroot]/vendor".
	You need to initialize the class with that vendor directory and url if
	it differs.

	$oCdn = new axelhahn\cdnorlocal(array(
		'vendordir' => __DIR__ . '/../vendor',
		'vendorurl' => '../vendor'
	));

(4) Admin webgui
    In the develop environment open the admin in your browser
	i.e. http://localhost/cdnorlocal/admin/
	
	Remark:
	On the public website you don't need the admin subdir or the cdnorlocal-admin.class.php
	You only need the single file "cdnorlocal.class.php"
	
	In the admin webgui you can 
	- search for a library on cdnjs and show details
	- download a library to the local vendor directory (*)
	- check if all the local versions are still up to date
	
	Maybe you want to use it as tool for download and version check for external
	libraries.

(*) The download is a file by file download and is quite inefficient. For more
    than 100 files you get a warning below the download button. 
	It will work if you have javascript enaled but takes its time. If a 
	download aborts, you can continue downloading the missing files later.

-------------------------------------------------------------------------------
