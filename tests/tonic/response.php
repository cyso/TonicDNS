<?php
/**
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

require_once('../../lib/tonic.php');
require_once('def/response.php');

/**
 * @namespace Tonic\Tests
 */
class ResponseTester extends UnitTestCase {
    
    function testGZipOutputEncoding() {
        
        $config = array(
            'uri' => '/responsetest',
            'acceptEncoding' => 'gzip'
        );
        
        $request = new Request($config);
        $resource = $request->loadResource();
        $response = $resource->exec($request);
        $response->doContentEncoding();
        
        $this->assertEqual($response->headers['Content-Encoding'], 'gzip');
        $this->assertEqual($response->body, gzencode('test'));
        
    }
    
    function testDeflateOutputEncoding() {
        
        $config = array(
            'uri' => '/responsetest',
            'acceptEncoding' => 'deflate'
        );
        
        $request = new Request($config);
        $resource = $request->loadResource();
        $response = $resource->exec($request);
        $response->doContentEncoding();
        
        $this->assertEqual($response->headers['Content-Encoding'], 'deflate');
        $this->assertEqual($response->body, gzdeflate('test'));
        
    }
    
    function testCompressOutputEncoding() {
        
        $config = array(
            'uri' => '/responsetest',
            'acceptEncoding' => 'compress'
        );
        
        $request = new Request($config);
        $resource = $request->loadResource();
        $response = $resource->exec($request);
        $response->doContentEncoding();
        
        $this->assertEqual($response->headers['Content-Encoding'], 'compress');
        $this->assertEqual($response->body, gzcompress('test'));
        
    }
    
    function testDefaultCacheHeader() {
        
        $config = array(
            'uri' => '/responsetest'
        );
        
        $request = new Request($config);
        $resource = $request->loadResource();
        $response = $resource->exec($request);
        $response->addCacheHeader();
        
        $this->assertEqual($response->headers['Cache-Control'], 'max-age=86400, must-revalidate');
        
    }
    
    function testNoCacheHeader() {
        
        $config = array(
            'uri' => '/responsetest'
        );
        
        $request = new Request($config);
        $resource = $request->loadResource();
        $response = $resource->exec($request);
        $response->addCacheHeader(0);
        
        $this->assertEqual($response->headers['Cache-Control'], 'no-cache');
        
    }
    
    function testAddEtag() {
        
        $config = array(
            'uri' => '/responsetest'
        );
        
        $request = new Request($config);
        $resource = $request->loadResource();
        $response = $resource->exec($request);
        $response->addEtag("123123");
        
        $this->assertEqual($response->headers['Etag'], '"123123"');
        
    }
    
}

