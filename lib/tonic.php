<?php
/**
 * Copyright (c) 2011 Cyso Managed Hosting < development [at] cyso . nl >
 * Copyright (c) 2009 Paul James
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
 * the Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 * FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 * IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
/**
 * Model the data of the incoming HTTP request
 * @namespace Tonic\Lib
 */
class Request {
    
    /**
     * The requested URI
     * @var str
     */
    public $uri;
    
    /**
     * The URI where the front controller is positioned in the server URI-space
     * @var str
     */
    public $baseUri = '';
    
    /**
     * Array of possible URIs based upon accept and accept-language request headers in order of preference
     * @var str[]
     */
    public $negotiatedUris = array();
    
    /**
     * Array of possible URIs based upon accept request headers in order of preference
     * @var str[]
     */
    public $formatNegotiatedUris = array();
    
    /**
     * Array of possible URIs based upon accept-language request headers in order of preference
     * @var str[]
     */
    public $languageNegotiatedUris = array();
    
    /**
     * Array of accept headers in order of preference
     * @var str[][]
     */
    public $accept = array();
    
    /**
     * Array of accept-language headers in order of preference
     * @var str[][]
     */
    public $acceptLang = array();
    
    /**
     * Array of accept-encoding headers in order of preference
     * @var str[]
     */
    public $acceptEncoding = array();
    
    /**
     * Map of file/URI extensions to mimetypes
     * @var str[]
     */
    public $mimetypes = array(
            'text/html' => 'html',
            'text/plain' => 'txt',
            'application/php' => 'php',
            'text/css' => 'css',
            'application/javascript' => 'js',
            'application/json' => 'json',
            'text/xml' => 'xml',
            'application/xml' => 'xml',
            'application/rss+xml' => 'rss',
            'application/atom+xml' => 'atom',
            'application/x-gzip' => 'gz',
            'application/x-tar' => 'tar',
            'application/zip' => 'zip',
            'image/gif' => 'gif',
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/x-icon' => 'ico',
            'application/x-shockwave-flash' => 'swf',
            'video/x-flv' => 'flv',
            'video/mpeg' => 'mpeg',
            'video/quicktime' => 'mov',
            'audio/mpeg' => 'mp3'
        );
        
    /**
     * HTTP request method of incoming request
     * @var str
     */
    public $method = 'GET';
    
    /**
     * Body data of incoming request
     * @var str
     */
    public $data;

    /**
     * Query string
     * @var str
     */
    public $queryString;

    /**
     * Content-Type of the request
     * @var str
     */
    public $requestType;

    /**
     * Authorization Token of the request
     * @var str
     */
    public $requestToken;
    
    /**
     * Array of if-match etags
     * @var str[]
     */
    public $ifMatch = array();
    
    /**
     * Array of if-none-match etags
     * @var str[]
     */
    public $ifNoneMatch = array();
    
    /**
     * Name of resource class to use for when nothing is found
     * @var str
     */
    public $noResource = 'NoResource';
    
    /**
     * The resource classes loaded and how they are wired to URIs
     * @var str[]
     */
    public $resources = array();
    
    /**
     * A list of URL to namespace/package mappings for routing requests to a
     * group of resources that are wired into a different URL-space
     * @var str[]
     */
    public $mounts = array();
    
    /**
     * Set a default configuration option
     */
    private function getConfig($config, $configVar, $serverVar = NULL, $default = NULL) {
        if (isset($config[$configVar])) {
            return $config[$configVar];
        } elseif (isset($_SERVER[$serverVar]) && $_SERVER[$serverVar] != '') {
            return $_SERVER[$serverVar];
        } else {
            return $default;
        }
    }
    
    /**
     * Create a request object using the given configuration options.
     *
     * The configuration options array can contain the following:
     *
     * <dl>
     * <dt>uri</dt> <dd>The URI of the request</dd>
     * <dt>method</dt> <dd>The HTTP method of the request</dd>
     * <dt>data</dt> <dd>The body data of the request</dd>
     * <dt>accept</dt> <dd>An accept header</dd>
     * <dt>acceptLang</dt> <dd>An accept-language header</dd>
     * <dt>acceptEncoding</dt> <dd>An accept-encoding header</dd>
     * <dt>ifMatch</dt> <dd>An if-match header</dd>
     * <dt>ifNoneMatch</dt> <dd>An if-none-match header</dd>
     * <dt>mimetypes</dt> <dd>A map of file/URI extenstions to mimetypes, these
     * will be added to the default map of mimetypes</dd>
     * <dt>baseUri</dt> <dd>The base relative URI to use when dispatcher isn't
     * at the root of the domain. Do not put a trailing slash</dd>
     * <dt>404</dt> <dd>Class name to use when no resource is found</dd>
     * <dt>mounts</dt> <dd>an array of namespace to baseUri prefix mappings</dd>
     * </dl>
     *
     * @param mixed[] config Configuration options
     */
    function __construct($config = array()) {
        
        // set defaults
        $config['uri'] = $this->getConfig($config, 'uri', 'REDIRECT_URL');
        $config['baseUri'] = $this->getConfig($config, 'baseUri', '');
        $config['accept'] = $this->getConfig($config, 'accept', 'HTTP_ACCEPT');
        $config['acceptLang'] = $this->getConfig($config, 'acceptLang', 'HTTP_ACCEPT_LANGUAGE');
        $config['acceptEncoding'] = $this->getConfig($config, 'acceptEncoding', 'HTTP_ACCEPT_ENCODING');
        $config['ifMatch'] = $this->getConfig($config, 'ifMatch', 'HTTP_IF_MATCH');
        $config['ifNoneMatch'] = $this->getConfig($config, 'ifNoneMatch', 'HTTP_IF_NONE_MATCH');
        
        if (isset($config['mimetypes']) && is_array($config['mimetypes'])) {
            foreach ($config['mimetypes'] as $mimetype => $ext) {
                $this->mimetypes[$mimetype] = $ext;
            }
        }
        
        // set baseUri
        $this->baseUri = $config['baseUri'];
        
        // get request URI
        $parts = explode('/', $config['uri']);
        $lastPart = array_pop($parts);
        $this->uri = join('/', $parts);
        
        $parts = explode('.', $lastPart);
        $this->uri .= '/'.$parts[0];
        
        if (substr($this->uri, -1, 1) == '/') { // remove trailing slash problem
            $this->uri = substr($this->uri, 0, -1);
        }
        
        array_shift($parts);
		foreach ($parts as $part) {
            if (array_search($part, $this->mimetypes)) {
                $this->accept[10][] = $part;
                $this->acceptLang[10][] = $part;
            } else {
                $this->uri .= "." . $part;
            }
        }
        
        // sort accept headers
        $accept = explode(',', strtolower($config['accept']));
        foreach ($accept as $mimetype) {
            $parts = explode(';q=', $mimetype);
            if (isset($parts) && isset($parts[1]) && $parts[1]) {
                $num = $parts[1] * 10;
            } else {
                $num = 10;
            }
            if (array_key_exists($parts[0], $this->mimetypes)) {
                $this->accept[$num][] = $this->mimetypes[$parts[0]];
            }
        }
        krsort($this->accept);
        
        // sort lang accept headers
        $accept = explode(',', strtolower($config['acceptLang']));
        foreach ($accept as $mimetype) {
            $parts = explode(';q=', $mimetype);
            if (isset($parts) && isset($parts[1]) && $parts[1]) {
                $num = $parts[1] * 10;
            } else {
                $num = 10;
            }
            $this->acceptLang[$num][] = $parts[0];
        }
        krsort($this->acceptLang);
        
        // get encoding accept headers
        if ($config['acceptEncoding']) {
            foreach (explode(',', $config['acceptEncoding']) as $key => $accept) {
                $this->acceptEncoding[$key] = trim($accept);
            }
        }
        
        // create negotiated URI lists from accept headers and request URI
        foreach ($this->accept as $typeOrder) {
            foreach ($typeOrder as $type) {
                if ($type) {
                    foreach ($this->acceptLang as $langOrder) {
                        foreach ($langOrder as $lang) {
                            if ($lang && $lang != $type) {
                                $this->negotiatedUris[] = $this->uri.'.'.$type.'.'.$lang;
                            }
                        }
                    }
                    $this->negotiatedUris[] = $this->uri.'.'.$type;
                    $this->formatNegotiatedUris[] = $this->uri.'.'.$type;
                }
            }
        }
        foreach ($this->acceptLang as $langOrder) {
            foreach ($langOrder as $lang) {
                if ($lang) {
                    $this->negotiatedUris[] = $this->uri.'.'.$lang;
                    $this->languageNegotiatedUris[] = $this->uri.'.'.$lang;
                }
            }
        }
        $this->negotiatedUris[] = $this->uri;
        $this->formatNegotiatedUris[] = $this->uri;
        $this->languageNegotiatedUris[] = $this->uri;
        
        $this->negotiatedUris = array_values(array_unique($this->negotiatedUris));
        $this->formatNegotiatedUris = array_values(array_unique($this->formatNegotiatedUris));
        $this->languageNegotiatedUris = array_values(array_unique($this->languageNegotiatedUris));
        
        // get HTTP method
        $this->method = strtoupper($this->getConfig($config, 'method', 'REQUEST_METHOD', $this->method));
        
        // get HTTP request data
        $this->data = $this->getConfig($config, 'data', NULL, file_get_contents("php://input"));

        // get HTTP request type
        $raw_headers = array();
        if (function_exists("apache_request_headers")) {
                $raw_headers = apache_request_headers();
        } else if (function_exists("nsapi_request_headers")) {
                $raw_headers = nsapi_request_headers();
        } else {
                $raw_headers = getallheaders();
        }
        foreach ($raw_headers as $k => $h) {
                switch (strtolower($k)) {
                case "content-type":
                        $this->requestType = $h;
                        break;
                case "x-authentication-token":
                        $this->requestToken = $h;
                        break;
                }
        }

        // get HTTP query string
        $this->queryString = $this->getConfig($config, NULL, 'QUERY_STRING');
        
        // conditional requests
        if ($config['ifMatch']) {
            $ifMatch = explode(',', $config['ifMatch']);
            foreach ($ifMatch as $etag) {
                $this->ifMatch[] = trim($etag, '" ');
            }
        }
        if ($config['ifNoneMatch']) {
            $ifNoneMatch = explode(',', $config['ifNoneMatch']);
            foreach ($ifNoneMatch as $etag) {
                $this->ifNoneMatch[] = trim($etag, '" ');
            }
        }
        
        // 404 resource
        if (isset($config['404'])) {
            $this->noResource = $config['404'];
        }
        
        // mounts
        if (isset($config['mount']) && is_array($config['mount'])) {
            $this->mounts = $config['mount'];
        }
        
        // prime named resources for autoloading
        if (isset($config['autoload']) && is_array($config['autoload'])) {
            foreach ($config['autoload'] as $uri => $filename) {
                $parts = preg_split('|[/\\\\]|', $filename);
                $filename = join(DIRECTORY_SEPARATOR, $parts);
                $parts = explode('.', array_pop($parts));
                $className = $parts[0];
                if (file_exists($filename)) {
                    $this->resources[$uri] = array(
                        'class' => $className,
                        'filename' => $filename,
                        'loaded' => FALSE
                    );
                }
            }
        }
        
        // load definitions of already loaded resource classes
        foreach (get_declared_classes() as $className) {
            if (is_subclass_of($className, 'Resource')) {
                
                $resourceDetails = $this->getResourceClassDetails($className);
                
                preg_match_all('/@uri\s+([^\s]+)(?:\s([0-9]+))?/', $resourceDetails['comment'], $annotations);
                if (isset($annotations[1])) {
                    $uris = $annotations[1];
                } else {
                    $uris = array('/');
                }
                
                foreach ($uris as $index => $uri) {
                    if (substr($uri, -1, 1) == '/') { // remove trailing slash problem
                        $uri = substr($uri, 0, -1);
                    }
                    $this->resources[$resourceDetails['mountPoint'].$uri] = array(
                        'namespace' => $resourceDetails['namespaceName'],
                        'class' => $resourceDetails['className'],
                        'filename' => $resourceDetails['filename'],
                        'line' => $resourceDetails['line'],
                        'priority' => isset($annotations[2][$index]) && is_numeric($annotations[2][$index]) ? intval($annotations[2][$index]) : 0,
                        'loaded' => TRUE
                    );
                }
            }
        }
        
    }
    
    /**
     * Get the details of a Resource class by reflection
     * @param str className
     * @return str[]
     */
    private function getResourceClassDetails($className) {
        
        $resourceReflector = new ReflectionClass($className);
        $comment = $resourceReflector->getDocComment();
        
        $className = $resourceReflector->getName();
        if (method_exists($resourceReflector, 'getNamespaceName')) {
            $namespaceName = $resourceReflector->getNamespaceName();
        } else {
            $namespaceName = FALSE;
        }
        
        if (!$namespaceName) {
            preg_match('/@(?:package|namespace)\s+([^\s]+)/', $comment, $package);
            if (isset($package[1])) {
                $namespaceName = $package[1];
            }
        }
        
        // adjust URI for mountpoint
        if (isset($this->mounts[$namespaceName])) {
            $mountPoint = $this->mounts[$namespaceName];
        } else {
            $mountPoint = '';
        }
        
        return array(
            'comment' => $comment,
            'className' => $className,
            'namespaceName' => $namespaceName,
            'filename' => $resourceReflector->getFileName(),
            'line' => $resourceReflector->getStartLine(),
            'mountPoint' => $mountPoint
        );
    
    }
    
    /**
     * Convert the object into a string suitable for printing
     * @return str
     */
    function __toString() {
        $str = 'URI: '.$this->uri."\n";
        $str .= 'Method: '.$this->method."\n";
        if ($this->data) {
            $str .= 'Data: '.$this->data."\n";
        }
        $str .= 'Negotated URIs:'."\n";
        foreach ($this->negotiatedUris as $uri) {
            $str .= "\t".$uri."\n";
        }
        $str .= 'Format Negotated URIs:'."\n";
        foreach ($this->formatNegotiatedUris as $uri) {
            $str .= "\t".$uri."\n";
        }
        $str .= 'Language Negotated URIs:'."\n";
        foreach ($this->languageNegotiatedUris as $uri) {
            $str .= "\t".$uri."\n";
        }
        if ($this->ifMatch) {
            $str .= 'If Match:';
            foreach ($this->ifMatch as $etag) {
                $str .= ' '.$etag;
            }
            $str .= "\n";
        }
        if ($this->ifNoneMatch) {
            $str .= 'If None Match:';
            foreach ($this->ifNoneMatch as $etag) {
                $str .= ' '.$etag;
            }
            $str .= "\n";
        }
        $str .= 'Loaded Resources:'."\n";
        foreach ($this->resources as $uri => $resource) {
            $str .= "\t".$uri."\n";
            if (isset($resource['namespace']) && $resource['namespace']) $str .= "\t\tNamespace: ".$resource['namespace']."\n";
            $str .= "\t\tClass: ".$resource['class']."\n";
            $str .= "\t\tFile: ".$resource['filename'];
            if (isset($resource['line']) && $resource['line']) $str .= '#'.$resource['line'];
            $str .= "\n";
        }
        return $str;
    }
    
    /**
     * Instantiate the resource class that matches the request URI the best
     * @return Resource
     */
    function loadResource() {
        
        $uriMatches = array();
        foreach ($this->resources as $uri => $resource) {
            
            preg_match_all('#(:[^/]+|{[^}]+}|\(.+?\))#', $uri, $params, PREG_PATTERN_ORDER);
            $uriRegex = preg_replace('#(:[^/]+|{[^}]+})#', '(.+)', $uri);
            
            if (preg_match('#^'.$this->baseUri.$uriRegex.'$#', $this->uri, $matches)) {
                array_shift($matches);
                
                if (isset($params[1])) {
                    foreach ($params[1] as $index => $param) {
                        if (isset($matches[$index])) {
                            if (substr($param, 0, 1) == ':') {
                                $matches[substr($param, 1)] = $matches[$index];
                                unset($matches[$index]);
                            } elseif (substr($param, 0, 1) == '{' && substr($param, -1, 1) == '}') {
                                $matches[substr($param, 1, -1)] = $matches[$index];
                                unset($matches[$index]);
                            }
                        }
                    }
                }
                
                $uriMatches[isset($resource['priority']) ? $resource['priority'] : 0] = array(
                    $uri,
                    $resource,
                    $matches
                );
                
            }
        }
        ksort($uriMatches);
        
        if ($uriMatches) {
            list($uri, $resource, $parameters) = array_shift($uriMatches);
            if (!$resource['loaded']) { // autoload
                if (!class_exists($resource['class'])) {
                    include $resource['filename'];
                }
                $resourceDetails = $this->getResourceClassDetails($resource['class']);
                $resource = $this->resources[$uri] = array(
                    'namespace' => $resourceDetails['namespaceName'],
                    'class' => $resourceDetails['className'],
                    'filename' => $resourceDetails['filename'],
                    'line' => $resourceDetails['line'],
                    'priority' => 0,
                    'loaded' => TRUE
                );
            }
            return new $resource['class']($parameters);
        }
        return new $this->noResource(array());
        
    }
    
    /**
     * Check if an etag matches the requests if-match header
     * @param str etag Etag to match
     * @return bool
     */
    function ifMatch($etag) {
        if (isset($this->ifMatch[0]) && $this->ifMatch[0] == '*') {
            return TRUE;
        }
        return in_array($etag, $this->ifMatch);
    }
    
    /**
     * Check if an etag matches the requests if-none-match header
     * @param str etag Etag to match
     * @return bool
     */
    function ifNoneMatch($etag) {
        if (isset($this->ifMatch[0]) && $this->ifMatch[0] == '*') {
            return FALSE;
        }
        return in_array($etag, $this->ifNoneMatch);
    }

    /**
     * Returns a parsed version of the request data.
     * @return mixed Parsed data, or null on error.
     */
    function parseData() {
        if ($this->method === "GET") {
            parse_str($this->queryString, $data);
            return json_decode(json_encode($data));
        }

        $type = null;
        if (isset($this->requestType)) {
            preg_match("/^([\w]+\/[\w]+)(?:;.*)?$/", $this->requestType, $r);
            if (is_array($r) && count($r) > 1) {
                $this->requestType = $r[1];
            }
            switch($this->requestType) {
            case "application/xml":
            case "text/xml":
                $type = "xml";
                break;
            case "application/json":
            case "text/json":
                $type = "json";
                break;
            default:
                $type = null;
                break;
            }
        }

        if ($type == null) {
            if (preg_match("#<([a-z_:][a-z]*(\s+[a-z_:][a-z]*\s*=\s*(\"[^\"]*\"|'[^']*'))*|/[a-z_:][a-z]*)\s*>#", $this->data)) {
                $type = "xml";
            } else if (preg_match("#^(\"(\.|[^\"\\\n\r])*?\"|[,:{}\[\]0-9.\-+Eaeflnr-u \n\r\t])+?$#", $this->data)) {
                $type = "json";
            }
        }

        if ($type == null) {
            return null;
        }

        $data = null;
        switch ($type) {
        case "xml":
            $data = simplexml_load_string($this->data, null, LIBXML_NOBLANKS | LIBXML_NOEMPTYTAG | LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_NOENT);
            $data = $this->sanitizeXMLObject($data);
            break;
        case "json":
            $data = json_decode($this->data);
            break;
        }


        if (is_null($data)) {
            return null;
        } else {
            return $data;
        }
    }

    private function sanitizeXMLObject($object) {
        $object = unserialize(preg_replace("/(^|;)*O:[0-9]+:\"[^\"]+\":/i","\\1"."O:" . strlen('stdClass').":\"stdClass\":", serialize($object)));
        if (!is_object($object)) {
            return null;
        }
        $copy = clone $object;

        foreach($object as $k => $child) {
            if (isset($child->item)) {
                if (is_array($child->item)) {
                    $copy->{$k} = $child->item;
                } else {
                    $copy->{$k} = array($child->item);
                }
            }
        }

        return $copy;
    }
}

/**
 * Base resource class
 * @namespace Tonic\Lib
 */
class Resource {
    
    protected $parameters;
    
    /**
     * Resource constructor
     * @param str[] parameters Parameters passed in from the URL as matched from the URI regex
     */
    function  __construct($parameters) {
        $this->parameters = $parameters;
    }
    
    /**
     * Convert the object into a string suitable for printing
     * @return str
     */
    function __toString() {
        $str = get_class($this);
        foreach ($this->parameters as $name => $value) {
            $str .= "\n".$name.': '.$value;
        }
        return $str;
    }
    
    /**
     * Execute a request on this resource.
     * @param Request request
     * @return Response
     */
    function exec($request) {
        
        if (method_exists($this, $request->method)) {
            
            $method = new ReflectionMethod($this, $request->method);
            $parameters = array();
            foreach ($method->getParameters() as $param) {
                if ($param->name == 'request') {
                    $parameters[] = $request;
                } elseif (isset($this->parameters[$param->name])) {
                    $parameters[] = $this->parameters[$param->name];
                    unset($this->parameters[$param->name]);
                } else {
                    $parameters[] = reset($this->parameters);
                    array_shift($this->parameters);
                }
            }
            
            $response = call_user_func_array(
                array($this, $request->method),
                $parameters
            );
            
            if (!$response || !is_a($response, 'Response')) {
                throw new Exception('Method '.$request->method.' of '.get_class($this).' did not return a Response object');
            }
            
        } else {
            
            // send 405 method not allowed
            $response = new Response($request);
            $response->code = Response::METHODNOTALLOWED;
            $response->body = sprintf(
                'The HTTP method "%s" used for the request is not allowed for the resource "%s".',
                $request->method,
                $request->uri
            );
            
        }
        
        # good for debugging, remove this at some point
        $response->addHeader('X-Resource', get_class($this));
        
        return $response;
        
    }
    
}

/**
 * 404 resource class
 * @namespace Tonic\Lib
 */
class NoResource extends Resource {
    
    /**
     * Always return a 404 response.
     * @param Request request
     * @return Response
     */
    function exec($request) {
        
        // send 404 not found
        $response = new Response($request);
        $response->code = Response::NOTFOUND;
        $response->body = sprintf(
            'Nothing was found for the resource "%s".',
            $request->uri
        );
        return $response;
        
    }
    
}

/**
 * Model the data of the outgoing HTTP response
 * @namespace Tonic\Lib
 */
class Response {
    
    /**
     * HTTP response code constant
     */
    const OK = 200,
          CREATED = 201,
          NOCONTENT = 204,
          MOVEDPERMANENTLY = 301,
          FOUND = 302,
          SEEOTHER = 303,
          NOTMODIFIED = 304,
          TEMPORARYREDIRECT = 307,
          BADREQUEST = 400,
          UNAUTHORIZED = 401,
          FORBIDDEN = 403,
          NOTFOUND = 404,
          METHODNOTALLOWED = 405,
          NOTACCEPTABLE = 406,
          CONFLICT = 409,
          GONE = 410,
          LENGTHREQUIRED = 411,
          PRECONDITIONFAILED = 412,
          UNSUPPORTEDMEDIATYPE = 415,
          INTERNALSERVERERROR = 500;
    
    /**
     * The request object generating this response
     * @var Request
     */
    protected $request;
    
    /**
     * The HTTP response code to send
     * @var int
     */
    public $code = Response::OK;
    
    /**
     * The HTTP headers to send
     * @var str[]
     */
    public $headers = array();
    
    /**
     * The HTTP response body to send
     * @var str
     */
    public $body;
    
    /**
     * Create a response object.
     * @param Request request The request object generating this response
     * @param str uri The URL of the actual resource being used to build the response
     */
    function __construct($request, $uri = NULL) {
        
        $this->request = $request;
        
        if ($uri && $uri != $request->uri) { // add content location header
            $this->addHeader('Content-Location', $uri);
            $this->addVary('Accept');
            $this->addVary('Accept-Language');
        }
        
    }
    
    /**
     * Convert the object into a string suitable for printing
     * @return str
     */
    function __toString() {
        $str = 'HTTP/1.1 '.$this->code;
        foreach ($this->headers as $name => $value) {
            $str .= "\n".$name.': '.$value;
        }
        return $str;
    }
    
    /**
     * Add a header to the response
     * @param str header
     * @param str value
     */
    function addHeader($header, $value) {
        $this->headers[$header] = $value;
    }
    
    /**
     * Add content encoding headers and encode the response body
     */
    function doContentEncoding() {
        if (ini_get('zlib.output_compression') == 0) { // do nothing if PHP will do the compression for us
            foreach ($this->request->acceptEncoding as $encoding) {
                switch($encoding) {
                case 'gzip':
                    $this->addHeader('Content-Encoding', 'gzip');
                    $this->addVary('Accept-Encoding');
                    $this->body = gzencode($this->body);
                    return;
                case 'deflate':
                    $this->addHeader('Content-Encoding', 'deflate');
                    $this->addVary('Accept-Encoding');
                    $this->body = gzdeflate($this->body);
                    return;
                case 'compress':
                    $this->addHeader('Content-Encoding', 'compress');
                    $this->addVary('Accept-Encoding');
                    $this->body = gzcompress($this->body);
                    return;
                case 'identity':
                    return;
                }
            }
        }
    }
    
    /**
     * Send a cache control header with the response
     * @param int time Cache length in seconds
     */
    function addCacheHeader($time = 86400) {
        if ($time) {
            $this->addHeader('Cache-Control', 'max-age='.$time.', must-revalidate');
        } else {
            $this->addHeader('Cache-Control', 'no-cache');
        }
    }
    
    /**
     * Send an etag with the response
     * @param str etag Etag value
     */
    function addEtag($etag) {
        $this->addHeader('Etag', '"'.$etag.'"');
    }
    
    function addVary($header) {
        if (isset($this->headers['Vary'])) {
            $this->headers['Vary'] .= ' '.$header;
        } else {
            $this->addHeader('Vary', $header);
        }
    }
    
    function output() {
        
        if (php_sapi_name() != 'cli' && !headers_sent()) {
            
            if ($this->body) {
                $this->doContentEncoding();
                $this->addHeader('Content-Length', strlen($this->body));
            }
            
            header('HTTP/1.1 '.$this->code);
            foreach ($this->headers as $header => $value) {
                header($header.': '.$value);
            }
        }
        
        echo $this->body;
        
    }
    
}

if (!function_exists('getallheaders')) {
    function getallheaders()
    {
        if (!is_array($_SERVER)) {
            return array();
        }

        $headers = array();
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

?>
