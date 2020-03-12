<?php


namespace ZM\Utils;


use Co;
use Framework\Console;
use Framework\ZMBuf;

class DataProvider
{
    const HEADER_TYPE = '{"ai":"application/postscript","aif":"audio/x-aiff","aifc":"audio/x-aiff","aiff":"audio/x-aiff","asc":"text/plain","au":"audio/basic","avi":"video/x-msvideo","bcpio":"application/x-bcpio","bin":"application/octet-stream","bmp":"image/bmp","cdf":"application/x-netcdf","class":"application/octet-stream","cpio":"application/x-cpio","cpt":"application/mac-compactpro","csh":"application/x-csh","css":"text/css","dcr":"application/x-director","dir":"application/x-director","djv":"image/vnd.djvu","djvu":"image/vnd.djvu","dll":"application/octet-stream","dms":"application/octet-stream","doc":"application/msword","dvi":"application/x-dvi","dxr":"application/x-director","eps":"application/postscript","etx":"text/x-setext","exe":"application/octet-stream","ez":"application/andrew-inset","gif":"image/gif","gtar":"application/x-gtar","hdf":"application/x-hdf","hqx":"application/mac-binhex40","htm":"text/html","html":"text/html","ice":"x-conference/x-cooltalk","ief":"image/ief","iges":"model/iges","igs":"model/iges","jpe":"image/jpeg","jpeg":"image/jpeg","jpg":"image/jpeg","js":"application/x-javascript","kar":"audio/midi","latex":"application/x-latex","lha":"application/octet-stream","lzh":"application/octet-stream","m3u":"audio/x-mpegurl","man":"application/x-troff-man","me":"application/x-troff-me","mesh":"model/mesh","mid":"audio/midi","midi":"audio/midi","mif":"application/vnd.mif","mov":"video/quicktime","movie":"video/x-sgi-movie","mp2":"audio/mpeg","mp3":"audio/mpeg","mpe":"video/mpeg","mpeg":"video/mpeg","mpg":"video/mpeg","mpga":"audio/mpeg","ms":"application/x-troff-ms","msh":"model/mesh","mxu":"video/vnd.mpegurl","nc":"application/x-netcdf","oda":"application/oda","pbm":"image/x-portable-bitmap","pdb":"chemical/x-pdb","pdf":"application/pdf","pgm":"image/x-portable-graymap","pgn":"application/x-chess-pgn","png":"image/png","pnm":"image/x-portable-anymap","ppm":"image/x-portable-pixmap","ppt":"application/vnd.ms-powerpoint","ps":"application/postscript","qt":"video/quicktime","ra":"audio/x-realaudio","ram":"audio/x-pn-realaudio","ras":"image/x-cmu-raster","rgb":"image/x-rgb","rm":"audio/x-pn-realaudio","roff":"application/x-troff","rpm":"audio/x-pn-realaudio-plugin","rtf":"text/rtf","rtx":"text/richtext","sgm":"text/sgml","sgml":"text/sgml","sh":"application/x-sh","shar":"application/x-shar","silo":"model/mesh","sit":"application/x-stuffit","skd":"application/x-koan","skm":"application/x-koan","skp":"application/x-koan","skt":"application/x-koan","smi":"application/smil","smil":"application/smil","snd":"audio/basic","so":"application/octet-stream","spl":"application/x-futuresplash","src":"application/x-wais-source","sv4cpio":"application/x-sv4cpio","sv4crc":"application/x-sv4crc","swf":"application/x-shockwave-flash","t":"application/x-troff","tar":"application/x-tar","tcl":"application/x-tcl","tex":"application/x-tex","texi":"application/x-texinfo","texinfo":"application/x-texinfo","tif":"image/tiff","tiff":"image/tiff","tr":"application/x-troff","tsv":"text/tab-separated-values","txt":"text/plain","ustar":"application/x-ustar","vcd":"application/x-cdlink","vrml":"model/vrml","wav":"audio/x-wav","wbmp":"image/vnd.wap.wbmp","wbxml":"application/vnd.wap.wbxml","wml":"text/vnd.wap.wml","wmlc":"application/vnd.wap.wmlc","wmls":"text/vnd.wap.wmlscript","wmlsc":"application/vnd.wap.wmlscriptc","wrl":"model/vrml","xbm":"image/x-xbitmap","xht":"application/xhtml+xml","xhtml":"application/xhtml+xml","xls":"application/vnd.ms-excel","xml":"text/xml","xpm":"image/x-xpixmap","xsl":"text/xml","xwd":"image/x-xwindowdump","xyz":"chemical/x-xyz","zip":"application/zip"}';
    public static $buffer_list = [];

    public static function getResourceFolder() {
        return WORKING_DIR . '/resources/';
    }

    public static function getDataConfig(){
        return CONFIG_DIR;
    }

    public static function addSaveBuffer($buf_name, $sub_folder = null) {
        $name = ($sub_folder ?? "") . "/" . $buf_name . ".json";
        self::$buffer_list[$buf_name] = $name;
        ZMBuf::set($buf_name, self::getJsonData($name));
    }

    public static function saveBuffer() {
        $head = Console::setColor(date("[H:i:s ") . "INFO] Saving buffer......", "lightblue");
        echo $head;
        foreach(self::$buffer_list as $k => $v) {
            self::setJsonData($v, ZMBuf::get($k));
        }
        echo Console::setColor("saved", "lightblue").PHP_EOL;
    }

    public static function getFrameworkLink(){
        return ZMBuf::globals("http_reverse_link");
    }

    private static function getJsonData(string $string) {
        if(!file_exists(self::getDataConfig().$string)) return [];
        return json_decode(Co::readFile(self::getDataConfig().$string), true);
    }

    private static function setJsonData($filename, array $args) {
        Co::writeFile(self::getDataConfig() . $filename, json_encode($args, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING));
    }

    public static function getDataFolder() {
        return ZM_DATA;
    }
}