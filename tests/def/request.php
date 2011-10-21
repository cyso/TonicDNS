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

/* Test resource definitions */

/**
 * @namespace Tonic\Tests
 * @uri /requesttest/one
 * @uri /requesttest/three/(.+)/four 12
 */
class NewResource extends Resource {

}

/**
 * @namespace Tonic\Tests
 * @uri /requesttest/one/two
 */
class ChildResource extends NewResource {

}

/**
 * @namespace Tonic\Tests
 */
class NewNoResource extends NoResource {

}

/**
 * @namespace Tonic\Tests
 * @uri /requesttest/railsstyle/:param/:param2
 * @uri /requesttest/uritemplatestyle/{param}/{param2}
 */
class TwoUriParams extends Resource {

    var $params;
    
    function get($request, $param, $param2) {
        $this->receivedParams = array(
            'param' => $param,
            'param2' => $param2
        );
        return new Response($request);
    }
    
}

/**
 * @namespace Tonic\Tests
 * @uri /requesttest/railsmixedstyle/{param}/(.+)/{param2}/(.+)
 * @uri /requesttest/uritemplatemixedstyle/{param}/(.+)/{param2}/(.+)
 * @uri /requesttest/mixedstyle/:param/(.+)/{param2}/(.+)
 */
class FourUriParams extends Resource {
    
    var $params;
    
    function get($request, $something, $otherthing, $param, $param2) {
        $this->receivedParams = array(
            'param' => $param,
            'param2' => $param2,
            'something' => $something,
            'otherthing' => $otherthing
        );
        return new Response($request);
    }
    
}

