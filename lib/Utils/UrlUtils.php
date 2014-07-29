<?php
namespace Goetas\Xsd\XsdToPhp\Utils;
class UrlUtils
{
    /**
    * Splits url into array of it's pieces as follows:
    * [scheme]://[user]:[pass]@[host]/[path]?[query]#[fragment]
    * In addition it adds 'query_params' key which contains array of
    * url-decoded key-value pairs
    *
    * @param string $sUrl Url
    * @return array Parsed url pieces
    */
    public static function explode($sUrl)
    {
        $aUrl = parse_url($sUrl);
        $aUrl['query_params'] = array();
        if (isset($aUrl['query'])) {
            parse_str($aUrl['query'],$aUrl['query_params']);
        }

        return $aUrl;
    }
        /**
         * Compiles url out of array of it's pieces (returned by explodeUrl)
         * 'query' is ignored if 'query_params' is present
         *
         * @param Array $aUrl Array of url pieces
         */
        public static function implode($aUrl)
        {
            //[scheme]://[user]:[pass]@[host]/[path]?[query]#[fragment]

            $sQuery = '';

            // Compile query
            if (isset($aUrl['query_params']) && is_array($aUrl['query_params'])) {
                $sQuery = http_build_query($aUrl['query_params']);
            } else {
                $sQuery = $aUrl['query'];
            }

            // Compile url
            $sUrl =
                $aUrl['scheme'] . '://' . (
                    isset($aUrl['user']) && $aUrl['user'] != '' && isset($aUrl['pass'])
                       ? $aUrl['user'] . ':' . $aUrl['pass'] . '@'
                       : ''
                ) .
                $aUrl['host'] . (
                    isset($aUrl['path']) && $aUrl['path'] != ''
                       ? $aUrl['path']
                       : ''
                ) . (
                   $sQuery != ''
                       ? '?' . $sQuery
                       : ''
                ) . (
                   isset($aUrl['fragment']) && $aUrl['fragment'] != ''
                       ? '#' . $aUrl['fragment']
                       : ''
                );

            return $sUrl;
        }
        /**
         * Parses url and returns array of key-value pairs of url params
         *
         * @param String $sUrl
         * @return Array
         */
        public static function getParams($sUrl)
        {
            $aUrl = self::explode($sUrl);

            return $aUrl['query_params'];
        }
        /**
         * Removes existing url params and sets them to those specified in $aParams
         *
         * @param String $sUrl Url
         * @param Array $aParams Array of Key-Value pairs to set url params to
         * @return  String Newly compiled url
         */
        public static function setParams($sUrl, $aParams)
        {
            $aUrl = self::explode($sUrl);
            $aUrl['query'] = '';
            $aUrl['query_params'] = $aParams;

            return self::implode($aUrl);
        }
        /**
         * Updates values of existing url params and/or adds (if not set) those specified in $aParams
         *
         * @param String $sUrl Url
         * @param Array $aParams Array of Key-Value pairs to set url params to
         * @return  String Newly compiled url
         */
        public static function updateParams($sUrl, $aParams)
        {
            $aUrl = self::explode($sUrl);
            $aUrl['query'] = '';
            $aUrl['query_params'] = array_merge($aUrl['query_params'], $aParams);

            return self::implode($aUrl);
        }
    /**
     * restituisce l'URL $url accodando i parametri in $data.
     * Controlla se ce bisogno di aggiungere /,?,&amp;
     * @param  string $url
     * @param  array  $data
     * @return string
     */
    public static function encodeUrl($url, array $data=array())
    {
        $parts=parse_url($url);
        if (!isset($parts["path"])) {
            $url.="/";
        }
        if (strstr($url,"?")===false) {
            $url.="?";
        } elseif ($url[strlen($url)-1]!="&") {
            $url.="&";
        }

        return $url.http_build_query($data);
    }
    public static function is_absolute_path($path)
    {
        return ($path [0] == "/" || substr( $path, 0, 2 ) == "\\\\" || preg_match( "#^[a-z]:\\\\#i", $path ) || preg_match( "#^[a-z-0-9-\\.]+://#i", $path ));
    }
    public static function resolve_url($base, $url)
    {
        if(! strlen( $base ))

            return $url;
            // Step 2
        if(! strlen( $url ))

            return $base;
            // Step 3
        if(preg_match( '!^[a-z]+:!i', $url ))

            return $url;
        $base = parse_url( $base );
        if ($url {0} == "#") {
            // Step 2 (fragment)
            $base ['fragment'] = substr( $url, 1 );

            return self::unparse_url( $base );
        }
        unset( $base ['fragment'] );
        unset( $base ['query'] );
        if (substr( $url, 0, 2 ) == "//") {
            // Step 4
            return self::unparse_url( array('scheme' => $base ['scheme'], 'path' => substr( $url, 2 ) ) );
        } elseif ($url [0] == "/") {
            // Step 5
            $base ['path'] = $url;
        } else {
            // Step 6
            $path = explode( '/', $base ['path'] );
            $url_path = explode( '/', $url );
            // Step 6a: drop file from base
            array_pop( $path );
            // Step 6b, 6c, 6e: append url while removing "." and ".." from
            // the directory portion
            $end = array_pop( $url_path );
            foreach ($url_path as $segment) {
                if ($segment == '.') {
                    // skip
                } elseif ($segment == '..' && $path && $path [sizeof( $path ) - 1] != '..') {
                    array_pop( $path );
                } else {
                    $path [] = $segment;
                }
            }
            // Step 6d, 6f: remove "." and ".." from file portion
            if ($end == '.') {
                $path [] = '';
            } elseif ($end == '..' && $path && $path [sizeof( $path ) - 1] != '..') {
                $path [sizeof( $path ) - 1] = '';
            } else {
                $path [] = $end;
            }
            // Step 6h
            $base ['path'] = join( '/', $path );

        }
        // Step 7
        return self::unparse_url( $base );
    }
    public static function unparse_url($parts_arr)
    {
        $ret_url = '';

        if (isset( $parts_arr ['scheme'] ) && strcmp( $parts_arr ['scheme'], '' ) != 0) {
            $ret_url = $parts_arr ['scheme'] . '://';
        }

        if (isset( $parts_arr ['user'] )) {
            $ret_url .= $parts_arr ['user'];
        }

        if (isset( $parts_arr ['pass'] )) {
            if (strcmp( $parts_arr ['pass'], '' ) != 0) {
                $ret_url .= ':' . $parts_arr ['pass'];
            }
            if ((strcmp( $parts_arr ['user'], '' ) != 0) || (strcmp( $parts_arr ['pass'], '' ) != 0)) {
                $ret_url .= '@';
            }
        }

        if (isset( $parts_arr ['host'] )) {
            $ret_url .= $parts_arr ['host'];
        }

        if (isset( $parts_arr ['port'] ) && strcmp( $parts_arr ['port'], '' ) != 0) {
            $ret_url .= ':' . $parts_arr ['port'];
        }

        if (isset( $parts_arr ['path'] )) {
            $ret_url .= $parts_arr ['path'];
        }

        /*if (isset($parts_arr['query']) && strcmp($parts_arr['query'], '') != 0) {
                $ret_url .= '?' . $parts_arr['query'];
        }*/

        if (isset( $parts_arr ['fragment'] ) && strcmp( $parts_arr ['fragment'], '' ) != 0) {
            $ret_url .= '#' . $parts_arr ['fragment'];
        }

        return $ret_url;
    }

}
