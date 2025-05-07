<?php class CardTest extends PHPUnit_Framework_TestCase
{
    private $http;

    public function setUp()
    {
        $this->http = new GuzzleHttp\Client(['base_uri' => 'http://cards.leinsterhockey.ie:8080/']);
    }

    public function tearDown() {
        $this->http = null;
    }    

    public function testAddPlayer()
    {
        $response = $this->http->request("GET", 
    }
}
