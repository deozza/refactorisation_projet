<?php

namespace App\Tests;

use Hautelook\AliceBundle\PhpUnit\RecreateDatabaseTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerTest extends WebTestCase
{
    use RecreateDatabaseTrait;

    /**
     * @dataProvider dataprovider_getListeDesUsers_checkAuthorizedMethods
     */
    public function test_getListeDesUsers_checkAuthorizedMethods(string $method)
    {
        $client = static::createClient();
        $client->request($method, '/users');
        $this->assertEquals(405, $client->getResponse()->getStatusCode());
    }

    private static function dataprovider_getListeDesUsers_checkAuthorizedMethods(): array
    {
        return [
            ['PUT'],
            ['DELETE'],
            ['PATCH'],
        ];
    }

    public function test_getListeDesUsers_checkReturnStatus(){
        $client = static::createClient();
        $client->request('GET', '/users');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function test_getListeDesUsers_checkValues(){
        $client = static::createClient();
        $client->request('GET', '/users');

        $content = $client->getResponse()->getContent();
        $this->assertJsonStringEqualsJsonString('[{"id":1,"name":"John","age":25},{"id":2,"name":"Jane","age":22},{"id":3,"name":"Jack","age":27}]', $content);
    }

    /**
     * @dataProvider dataprovider_createUser_checkWhenMissingData
     */
    public function test_createUser_checkWhenMissingData(){
        $client = static::createClient();
        $client->request('POST', '/users');
        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }

    private static function dataprovider_createUser_checkWhenMissingData(): array
    {
        return [
            ['{"nom":"John"}'],
            ['{"age":25}'],
            ['{}'],
        ];
    }

    public function test_createUser_checkWithTooManyData(){
        $client = static::createClient();
        $client->request('POST', '/users', [], [], ['CONTENT_TYPE' => 'application/json'], '{"nom":"John","age":25,"foo":"bar"}');
        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }

    public function test_createUser_checkWhenWrongAge(){
        $client = static::createClient();
        $client->request('POST', '/users', [], [], ['CONTENT_TYPE' => 'application/json'], '{"nom":"John","age":15}');
        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }

    public function test_createUser_checkWhenUserAlreadyExists(){
        $client = static::createClient();
        $client->request('POST', '/users', [], [], ['CONTENT_TYPE' => 'application/json'], '{"nom":"John","age":25}');
        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }

    public function test_createUser_checkWithValidData(){
        $client = static::createClient();
        $client->request('POST', '/users', [], [], ['CONTENT_TYPE' => 'application/json'], '{"nom":"Joe","age":30}');
        $this->assertEquals(201, $client->getResponse()->getStatusCode());
    }

    public function test_getUserWithIdentifiant_checkWithInvalidMethod(){
        $client = static::createClient();
        $client->request('POST', '/user/1');
        $this->assertEquals(405, $client->getResponse()->getStatusCode());
    }

    /**
     * @dataProvider dataprovider_getUserWithIdentifiant_checkWithInvalidId
     */
    public function test_getUserWithIdentifiant_checkWithInvalidId($id){
        $client = static::createClient();
        $client->request('GET', '/user/'.$id);
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    private static function dataprovider_getUserWithIdentifiant_checkWithInvalidId(): array
    {
        return [
            [0],
            [4],
            [-1],
            ['a'],
        ];
    }

    public function test_getUserWithIdentifiant_checkStatusWithValidId(){
        $client = static::createClient();
        $client->request('GET', '/user/1');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function test_getUserWithIdentifiant_checkValuesWithValidId(){
        $client = static::createClient();
        $client->request('GET', '/user/1');

        $content = $client->getResponse()->getContent();
        $this->assertJsonStringEqualsJsonString('{"id":1,"name":"John","age":25}', $content);
    }
    
    /**
     * @dataProvider dataprovider_updateUser_withInvalidId
     */
    public function test_updateUser_withInvalidId($id){
        $client = static::createClient();
        $client->request('PATCH', '/user/'.$id);
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    private static function dataprovider_updateUser_withInvalidId(): array
    {
        return [
            [0],
            [4],
            [-1],
            ['a'],
        ];
    }

    public function test_updateUser_withTooManyData(){
        $client = static::createClient();
        $client->request('PATCH', '/user/1', [], [], ['CONTENT_TYPE' => 'application/json'], '{"nom":"John","age":25,"foo":"bar"}');
        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }

    public function test_updateUser_withWrongAge(){
        $client = static::createClient();
        $client->request('PATCH', '/user/1', [], [], ['CONTENT_TYPE' => 'application/json'], '{"age":15}');
        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }

    public function test_updateUser_whenUserAlreadyExists(){
        $client = static::createClient();
        $client->request('PATCH', '/user/1', [], [], ['CONTENT_TYPE' => 'application/json'], '{"nom":"Jane"}');
        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }

    public function test_updateUser_checkValidStatus(){
        $client = static::createClient();
        $client->request('PATCH', '/user/1', [], [], ['CONTENT_TYPE' => 'application/json'], '{"nom":"Joe","age":30}');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function test_updateUser_checkValidValues(){
        $client = static::createClient();
        $client->request('PATCH', '/user/1', [], [], ['CONTENT_TYPE' => 'application/json'], '{"nom":"Joe","age":30}');

        $content = $client->getResponse()->getContent();
        $this->assertJsonStringEqualsJsonString('{"id":1,"name":"Joe","age":30}', $content);
    }

    /**
     * @dataProvider dataprovider_suprUser_withInvalidId
     */
    public function test_suprUser_withInvalidId($id){
        $client = static::createClient();
        $client->request('DELETE', '/user/'.$id);
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    private static function dataprovider_suprUser_withInvalidId(): array
    {
        return [
            [0],
            [4],
            [-1],
            ['a'],
        ];
    }

    public function test_suprUser_checkValidStatus(){
        $client = static::createClient();
        $client->request('DELETE', '/user/1');
        $this->assertEquals(204, $client->getResponse()->getStatusCode());
    }

    public function test_suprUser_checkUserIsDeleted(){
        $client = static::createClient();
        $client->request('DELETE', '/user/1');
        $client->request('GET', '/user/1');
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }
}
