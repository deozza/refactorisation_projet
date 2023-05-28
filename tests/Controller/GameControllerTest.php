<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Hautelook\AliceBundle\PhpUnit\RecreateDatabaseTrait;

class GameControllerTest extends WebTestCase
{
    use RecreateDatabaseTrait;

    /**
     * @dataProvider dataprovider_getListGame_checkAuthorizedMethods
     */
    public function test_getListGame_checkAuthorizedMethods($method){
        $client = static::createClient();
        $client->request($method, '/games');
        $this->assertEquals(405, $client->getResponse()->getStatusCode());
    }

    private static function dataprovider_getListGame_checkAuthorizedMethods(): array
    {
        return [
            ['PUT'],
            ['DELETE'],
            ['PATCH'],
        ];
    }

     public function test_getListGame_checkReturnStatus(){
        $client = static::createClient();
        $client->request('GET', '/games');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function test_getListGame_checkValues(){
        $client = static::createClient();
        $client->request('GET', '/games');

        $content = json_decode($client->getResponse()->getContent());

        $expectedResult = json_decode(file_get_contents(__DIR__.'/expect/test_getListGame_checkValues.json'));
        $this->assertEquals($expectedResult, $content);
    }

    /**
     * @dataProvider dataprovider_getGameInfo_checkAuthorizedMethods
     */
    public function test_getGameInfo_checkAuthorizedMethods(string $method){
        $client = static::createClient();
        $client->request($method, '/game/1');
        $this->assertEquals(405, $client->getResponse()->getStatusCode());
    }

    private static function dataprovider_getGameInfo_checkAuthorizedMethods(): array
    {
        return [
            ['POST'],
            ['PUT'],
        ];
    }

    /**
     * @dataProvider dataprovider_getGameInfo_checkWithInvalidId
     */
    public function test_getGameInfo_checkWithInvalidId($id){
        $client = static::createClient();
        $client->request('GET', '/game/'.$id);
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    private static function dataprovider_getGameInfo_checkWithInvalidId(): array
    {
        return [
            [0],
            [-1],
            ['a'],
        ];
    }

    public function test_getGameInfo_checkReturnStatus(){
        $client = static::createClient();
        $client->request('GET', '/game/1');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function test_getGameInfo_checkValues(){
        $client = static::createClient();
        $client->request('GET', '/game/4');

        $content = $client->getResponse()->getContent();
        $this->assertJsonStringEqualsJsonString('{"id":4,"state":"finished","playLeft":"paper","playRight":"scissors","result":"winLeft","playerLeft":{"id":1,"name":"John","age":25},"playerRight":{"id":2,"name":"Jane","age":22}}', $content);
    }

    public function test_launchGame_checkStatusWithoutUserParam(){
        $client = static::createClient();
        $client->request('POST', '/games');
        $this->assertEquals(401, $client->getResponse()->getStatusCode());
    }

    /**
     * @dataProvider dataprovider_launchGame_checkStatusWithInvalidUserParam
     */
    public function test_launchGame_checkStatusWithInvalidUserParam($userParam){
        $client = static::createClient([], ['HTTP_X_USER_ID' => $userParam]);
        $client->request('POST', '/games');
        $this->assertEquals(401, $client->getResponse()->getStatusCode());
    }

    private static function dataprovider_launchGame_checkStatusWithInvalidUserParam(): array
    {
        return [
            [''],
            ['a'],
            ['0'],
            ['-1'],
        ];
    }

    public function test_launchGame_checkStatusWhenValid(){
        $client = static::createClient([], ['HTTP_X_USER_ID' => 1]);
        $client->request('POST', '/games');
        $this->assertEquals(201, $client->getResponse()->getStatusCode());
    }

    public function test_launchGame_checkValuesWhenValid(){
        $client = static::createClient([], ['HTTP_X_USER_ID' => 1]);
        $client->request('POST', '/games');

        $content = json_decode($client->getResponse()->getContent());
        $this->assertObjectHasAttribute('id', $content);
        $this->assertObjectHasAttribute('state', $content);
        $this->assertObjectHasAttribute('playLeft', $content);
        $this->assertObjectHasAttribute('playRight', $content);
        $this->assertObjectHasAttribute('result', $content);
        $this->assertObjectHasAttribute('playerLeft', $content);
        $this->assertObjectHasAttribute('playerRight', $content);
    }

    /**
     * @dataProvider dataprovider_inviteToGane_checkAuthorizedMethods
     */
    public function test_inviteToGame_checkAuthorizedMethods($method){
        $client = static::createClient();
        $client->request($method, '/game/1/add/2');
        $this->assertEquals(405, $client->getResponse()->getStatusCode());
    }

    private static function dataprovider_inviteToGane_checkAuthorizedMethods(): array
    {
        return [
            ['GET'],
            ['PUT'],
            ['DELETE'],
            ['POST'],
        ];
    }

    /**
     * @dataProvider dataprovider_inviteToGame_checkWithInvalidAuth
     */
    public function test_inviteToGame_checkWithInvalidAuth($id){
        $client = static::createClient([], ['HTTP_X_USER_ID' => $id]);
        $client->request('PATCH', '/game/1/add/2');
        $this->assertEquals(401, $client->getResponse()->getStatusCode());
    }

    private static function dataprovider_inviteToGame_checkWithInvalidAuth(): array
    {
        return [
            [''],
            ['a'],
            ['0'],
            ['-1'],
        ];
    }

    /**
     * @dataProvider dataprovider_inviteToGame_checkWithInvalidGameId
     */
    public function test_inviteToGame_checkWithInvalidGameId($id){
        $client = static::createClient([], ['HTTP_X_USER_ID' => 1]);
        $client->request('PATCH', '/game/'.$id.'/add/2');
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    private static function dataprovider_inviteToGame_checkWithInvalidGameId(): array
    {
        return [
            ['a'],
            ['0'],
            ['-1'],
            ['10'],
        ];
    }

    /**
     * @dataProvider dataprovider_inviteToGame_checkWithInvalidGameStatus
     */
    public function test_inviteToGame_checkWithInvalidGameStatus($id){
        $client = static::createClient([], ['HTTP_X_USER_ID' => 1]);
        $client->request('PATCH', '/game/'.$id.'/add/2');
        $this->assertEquals(409, $client->getResponse()->getStatusCode());
    }

    private static function dataprovider_inviteToGame_checkWithInvalidGameStatus(): array
    {
        return [
            [2],
            [4]
        ];
    }

    /**
     * @dataProvider dataprovider_inviteToGame_checkWithInvalidPlayerRight
     */
    public function test_inviteToGame_checkWithInvalidPlayerRight($id){
        $client = static::createClient([], ['HTTP_X_USER_ID' => 1]);
        $client->request('PATCH', '/game/1/add/'.$id);
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    private static function dataprovider_inviteToGame_checkWithInvalidPlayerRight(): array
    {
        return [
            ['a'],
            ['0'],
            ['-1'],
            ['10'],
        ];
    }

    public function test_inviteToGame_checkWithDuplicatePlayer(){
        $client = static::createClient([], ['HTTP_X_USER_ID' => 1]);
        $client->request('PATCH', '/game/1/add/1');
        $this->assertEquals(409, $client->getResponse()->getStatusCode());
    }

    public function test_inviteToGame_checkValidStatusCode(){
        $client = static::createClient([], ['HTTP_X_USER_ID' => 1]);
        $client->request('PATCH', '/game/1/add/2');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function test_inviteToGame_checkValidValues(){
        $client = static::createClient([], ['HTTP_X_USER_ID' => 1]);
        $client->request('PATCH', '/game/1/add/2');

        $content = json_decode($client->getResponse()->getContent());
        $this->assertObjectHasAttribute('id', $content);
        $this->assertObjectHasAttribute('state', $content);
        $this->assertObjectHasAttribute('playLeft', $content);
        $this->assertObjectHasAttribute('playRight', $content);
        $this->assertObjectHasAttribute('result', $content);
        $this->assertObjectHasAttribute('playerLeft', $content);
        $this->assertObjectHasAttribute('playerRight', $content);
    }

    /**
     * @dataProvider dataprovider_play_checkWithInvalidAuth
     */
    public function test_play_checkWithInvalidAuth($id){
        $client = static::createClient([], ['HTTP_X_USER_ID' => $id]);
        $client->request('PATCH', '/game/2');
        $this->assertEquals(401, $client->getResponse()->getStatusCode());
    }

    private static function dataprovider_play_checkWithInvalidAuth(): array
    {
        return [
            [''],
            ['a'],
            ['0'],
            ['-1'],
            ['10'],
        ];
    }

    /**
     * @dataProvider dataprovider_play_checkWithGameNotFound
     */
    public function test_play_checkWithGameNotFound($id){
        $client = static::createClient([], ['HTTP_X_USER_ID' => 1]);
        $client->request('PATCH', '/game/'.$id);
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    private static function dataprovider_play_checkWithGameNotFound(): array
    {
        return [
            ['a'],
            ['0'],
            ['-1'],
            ['10'],
        ];
    }

    /**
     * @dataProvider dataprovider_play_checkWithForbiddenGame
     */
    public function test_play_checkWithForbiddenGame($id){
        $client = static::createClient([], ['HTTP_X_USER_ID' => 3]);
        $client->request('PATCH', '/game/2');
        $this->assertEquals(403, $client->getResponse()->getStatusCode());
    }

    private static function dataprovider_play_checkWithForbiddenGame(): array
    {
        return [
            [1],
            [4],
        ];
    }

    public function test_play_checkWithGameNotStarted(){
        $client = static::createClient([], ['HTTP_X_USER_ID' => 1]);
        $client->request('PATCH', '/game/1');
        $this->assertEquals(409, $client->getResponse()->getStatusCode());
    }

    public function test_play_checkWithInvalidChoice(){
        $client = static::createClient([], ['HTTP_X_USER_ID' => 1]);
        $client->request('PATCH', '/game/2', [], [], ['CONTENT_TYPE' => 'application/json'], '{"choice":"invalid"}');
        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }

    public function test_play_checkValidStatusCode(){
        $client = static::createClient([], ['HTTP_X_USER_ID' => 1]);
        $client->request('PATCH', '/game/2', [], [], ['CONTENT_TYPE' => 'application/json'], '{"choice":"rock"}');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function test_play_checkValidValuesForFirstTurn(){
        $client = static::createClient([], ['HTTP_X_USER_ID' => 1]);
        $client->request('PATCH', '/game/2', [], [], ['CONTENT_TYPE' => 'application/json'], '{"choice":"rock"}');

        $content = json_decode($client->getResponse()->getContent());

        $this->assertObjectHasAttribute('id', $content);
        $this->assertObjectHasAttribute('state', $content);
        $this->assertObjectHasAttribute('playLeft', $content);
        $this->assertObjectHasAttribute('playRight', $content);
        $this->assertObjectHasAttribute('result', $content);
        $this->assertObjectHasAttribute('playerLeft', $content);
        $this->assertObjectHasAttribute('playerRight', $content);

        $this->assertEquals('rock', $content->playLeft);
        $this->assertEquals(null, $content->playRight);
        $this->assertEquals(null, $content->result);
    }

    /**
     * @dataProvider dataprovider_play_checkValidValuesWithGameResult
     */
    public function test_play_checkValidValuesWithGameResult($choice, $expectedResult){
        $client = static::createClient([], ['HTTP_X_USER_ID' => 2]);
        $client->request('PATCH', '/game/3', [], [], ['CONTENT_TYPE' => 'application/json'], '{"choice":"'.$choice.'"}');

        $content = json_decode($client->getResponse()->getContent());
        $this->assertObjectHasAttribute('id', $content);
        $this->assertObjectHasAttribute('state', $content);
        $this->assertObjectHasAttribute('playLeft', $content);
        $this->assertObjectHasAttribute('playRight', $content);
        $this->assertObjectHasAttribute('result', $content);
        $this->assertObjectHasAttribute('playerLeft', $content);
        $this->assertObjectHasAttribute('playerRight', $content);

        $this->assertEquals('scissors', $content->playLeft);
        $this->assertEquals($choice, $content->playRight);
        $this->assertEquals($expectedResult, $content->result);
    }

    private static function dataprovider_play_checkValidValuesWithGameResult(): array
    {
        return [
            ['rock', 'winRight'],
            ['paper', 'winLeft'],
            ['scissors', 'draw'],
        ];
    }

    /**
     * @dataProvider dataprovider_deleteGame_checkWithInvalidAuth
     */
    public function test_deleteGame_checkWithInvalidAuth($id){
        $client = static::createClient([], ['HTTP_X_USER_ID' => $id]);
        $client->request('DELETE', '/game/1');
        $this->assertEquals(401, $client->getResponse()->getStatusCode());
    }

    private static function dataprovider_deleteGame_checkWithInvalidAuth(): array
    {
        return [
            [''],
            ['a'],
            ['0'],
            ['-1'],
            ['10'],
        ];
    }

    /**
     * @dataProvider dataprovider_deleteGame_checkWithGameNotFound
     */
    public function test_deleteGame_checkWithGameNotFound($id){
        $client = static::createClient([], ['HTTP_X_USER_ID' => 1]);
        $client->request('DELETE', '/game/'.$id);
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    private static function dataprovider_deleteGame_checkWithGameNotFound(): array
    {
        return [
            ['a'],
            ['-1'],
        ];
    }

    public function test_deleteGame_checkWithForbiddenGame(){
        $client = static::createClient([], ['HTTP_X_USER_ID' => 3]);
        $client->request('DELETE', '/game/1');
        $this->assertEquals(403, $client->getResponse()->getStatusCode());
    }

    /**
     * @dataProvider dataprovider_deleteGame_checkValid
     */
    public function test_deleteGame_checkValidStatusCode($id){
        $client = static::createClient([], ['HTTP_X_USER_ID' => $id]);
        $client->request('DELETE', '/game/2');
        $this->assertEquals(204, $client->getResponse()->getStatusCode());
    }

    /**
     * @dataProvider dataprovider_deleteGame_checkValid
     */
    public function test_deleteGame_checkGameIsDeleted($id){
        $client = static::createClient([], ['HTTP_X_USER_ID' => $id]);
        $client->request('DELETE', '/game/2');
        $this->assertEquals(204, $client->getResponse()->getStatusCode());

        $client->request('GET', '/game/2');
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    private static function dataprovider_deleteGame_checkValid(): array
    {
        return [
            [1],
            [2],
        ];
    }
}
