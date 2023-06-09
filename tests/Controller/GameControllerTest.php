<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Hautelook\AliceBundle\PhpUnit\RecreateDatabaseTrait;

class GameControllerTest extends WebTestCase
{
    use RecreateDatabaseTrait;

    /**
     * @dataProvider dataprovider_getGameList_checkAuthorizedMethods
     */
    public function test_getGameList_checkAuthorizedMethods($method){
        $client = static::createClient();
        $client->request($method, '/games');
        $this->assertEquals(405, $client->getResponse()->getStatusCode());
    }

    private static function dataprovider_getGameList_checkAuthorizedMethods(): array
    {
        return [
            ['PUT'],
            ['DELETE'],
            ['PATCH'],
        ];
    }

     public function test_getGameList_checkReturnStatus(){
        $client = static::createClient();
        $client->request('GET', '/games');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function test_getGameList_checkValues(){
        $client = static::createClient();
        $client->request('GET', '/games');

        $content = json_decode($client->getResponse()->getContent());

        $expectedResult = json_decode(file_get_contents(__DIR__.'/expect/test_getGameList_checkValues.json'));
        $this->assertEquals($expectedResult, $content);
    }

    /**
     * @dataProvider dataprovider_getGameById_checkAuthorizedMethods
     */
    public function test_getGameById_checkAuthorizedMethods(string $method){
        $client = static::createClient();
        $client->request($method, '/game/1');
        $this->assertEquals(405, $client->getResponse()->getStatusCode());
    }

    private static function dataprovider_getGameById_checkAuthorizedMethods(): array
    {
        return [
            ['POST'],
            ['PUT'],
        ];
    }

    /**
     * @dataProvider dataprovider_getGameById_checkWithInvalidId
     */
    public function test_getGameById_checkWithInvalidId($id){
        $client = static::createClient();
        $client->request('GET', '/game/'.$id);
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    private static function dataprovider_getGameById_checkWithInvalidId(): array
    {
        return [
            [0],
            [-1],
            ['a'],
        ];
    }

    public function test_getGameById_checkReturnStatus(){
        $client = static::createClient();
        $client->request('GET', '/game/1');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function test_getGameById_checkValues(){
        $client = static::createClient();
        $client->request('GET', '/game/4');

        $content = $client->getResponse()->getContent();
        $this->assertJsonStringEqualsJsonString('{"id":4,"state":"finished","playLeft":"paper","playRight":"scissors","result":"winLeft","playerLeft":{"id":1,"name":"John","age":25},"playerRight":{"id":2,"name":"Jane","age":22}}', $content);
    }

    public function test_postGame_checkStatusWithoutUserParam(){
        $client = static::createClient();
        $client->request('POST', '/games');
        $this->assertEquals(401, $client->getResponse()->getStatusCode());
    }

    /**
     * @dataProvider dataprovider_postGame_checkStatusWithInvalidUserParam
     */
    public function test_postGame_checkStatusWithInvalidUserParam($userParam){
        $client = static::createClient([], ['HTTP_X_USER_ID' => $userParam]);
        $client->request('POST', '/games');
        $this->assertEquals(401, $client->getResponse()->getStatusCode());
    }

    private static function dataprovider_postGame_checkStatusWithInvalidUserParam(): array
    {
        return [
            [''],
            ['a'],
            ['0'],
            ['-1'],
        ];
    }

    public function test_postGame_checkStatusWhenValid(){
        $client = static::createClient([], ['HTTP_X_USER_ID' => 1]);
        $client->request('POST', '/games');
        $this->assertEquals(201, $client->getResponse()->getStatusCode());
    }

    public function test_postGame_checkValuesWhenValid(){
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
    public function test_addPlayerRightToGame_checkAuthorizedMethods($method){
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
     * @dataProvider dataprovider_addPlayerRightToGame_checkWithInvalidAuth
     */
    public function test_addPlayerRightToGame_checkWithInvalidAuth($id){
        $client = static::createClient([], ['HTTP_X_USER_ID' => $id]);
        $client->request('PATCH', '/game/1/add/2');
        $this->assertEquals(401, $client->getResponse()->getStatusCode());
    }

    private static function dataprovider_addPlayerRightToGame_checkWithInvalidAuth(): array
    {
        return [
            [''],
            ['a'],
            ['0'],
            ['-1'],
        ];
    }

    /**
     * @dataProvider dataprovider_addPlayerRightToGame_checkWithInvalidGameId
     */
    public function test_addPlayerRightToGame_checkWithInvalidGameId($id){
        $client = static::createClient([], ['HTTP_X_USER_ID' => 1]);
        $client->request('PATCH', '/game/'.$id.'/add/2');
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    private static function dataprovider_addPlayerRightToGame_checkWithInvalidGameId(): array
    {
        return [
            ['a'],
            ['0'],
            ['-1'],
            ['10'],
        ];
    }

    /**
     * @dataProvider dataprovider_addPlayerRightToGame_checkWithInvalidGameStatus
     */
    public function test_addPlayerRightToGame_checkWithInvalidGameStatus($id){
        $client = static::createClient([], ['HTTP_X_USER_ID' => 1]);
        $client->request('PATCH', '/game/'.$id.'/add/2');
        $this->assertEquals(409, $client->getResponse()->getStatusCode());
    }

    private static function dataprovider_addPlayerRightToGame_checkWithInvalidGameStatus(): array
    {
        return [
            [2],
            [4]
        ];
    }

    /**
     * @dataProvider dataprovider_addPlayerRightToGame_checkWithInvalidPlayerRight
     */
    public function test_addPlayerRightToGame_checkWithInvalidPlayerRight($id){
        $client = static::createClient([], ['HTTP_X_USER_ID' => 1]);
        $client->request('PATCH', '/game/1/add/'.$id);
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    private static function dataprovider_addPlayerRightToGame_checkWithInvalidPlayerRight(): array
    {
        return [
            ['a'],
            ['0'],
            ['-1'],
            ['10'],
        ];
    }

    public function test_addPlayerRightToGame_checkWithDuplicatePlayer(){
        $client = static::createClient([], ['HTTP_X_USER_ID' => 1]);
        $client->request('PATCH', '/game/1/add/1');
        $this->assertEquals(409, $client->getResponse()->getStatusCode());
    }

    public function test_addPlayerRightToGame_checkValidStatusCode(){
        $client = static::createClient([], ['HTTP_X_USER_ID' => 1]);
        $client->request('PATCH', '/game/1/add/2');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function test_addPlayerRightToGame_checkValidValues(){
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
     * @dataProvider dataprovider_addChoiceToGame_checkWithInvalidAuth
     */
    public function test_addChoiceToGame_checkWithInvalidAuth($id){
        $client = static::createClient([], ['HTTP_X_USER_ID' => $id]);
        $client->request('PATCH', '/game/2');
        $this->assertEquals(401, $client->getResponse()->getStatusCode());
    }

    private static function dataprovider_addChoiceToGame_checkWithInvalidAuth(): array
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
     * @dataProvider dataprovider_addChoiceToGame_checkWithGameNotFound
     */
    public function test_addChoiceToGame_checkWithGameNotFound($id){
        $client = static::createClient([], ['HTTP_X_USER_ID' => 1]);
        $client->request('PATCH', '/game/'.$id);
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    private static function dataprovider_addChoiceToGame_checkWithGameNotFound(): array
    {
        return [
            ['a'],
            ['0'],
            ['-1'],
            ['10'],
        ];
    }

    /**
     * @dataProvider dataprovider_addChoiceToGame_checkWithForbiddenGame
     */
    public function test_addChoiceToGame_checkWithForbiddenGame($id){
        $client = static::createClient([], ['HTTP_X_USER_ID' => 3]);
        $client->request('PATCH', '/game/2');
        $this->assertEquals(403, $client->getResponse()->getStatusCode());
    }

    private static function dataprovider_addChoiceToGame_checkWithForbiddenGame(): array
    {
        return [
            [1],
            [4],
        ];
    }

    public function test_addChoiceToGame_checkWithGameNotStarted(){
        $client = static::createClient([], ['HTTP_X_USER_ID' => 1]);
        $client->request('PATCH', '/game/1');
        $this->assertEquals(409, $client->getResponse()->getStatusCode());
    }

    public function test_addChoiceToGame_checkWithInvalidChoice(){
        $client = static::createClient([], ['HTTP_X_USER_ID' => 1]);
        $client->request('PATCH', '/game/2', [], [], ['CONTENT_TYPE' => 'application/json'], '{"choice":"invalid"}');
        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }

    public function test_addChoiceToGame_checkValidStatusCode(){
        $client = static::createClient([], ['HTTP_X_USER_ID' => 1]);
        $client->request('PATCH', '/game/2', [], [], ['CONTENT_TYPE' => 'application/json'], '{"choice":"rock"}');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function test_addChoiceToGame_checkValidValuesForFirstTurn(){
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
     * @dataProvider dataprovider_addChoiceToGame_checkValidValuesWithGameResult
     */
    public function test_addChoiceToGame_checkValidValuesWithGameResult($choice, $expectedResult){
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

    private static function dataprovider_addChoiceToGame_checkValidValuesWithGameResult(): array
    {
        return [
            ['rock', 'winRight'],
            ['paper', 'winLeft'],
            ['scissors', 'draw'],
        ];
    }

    /**
     * @dataProvider dataprovider_deleteGameById_checkWithInvalidAuth
     */
    public function test_deleteGameById_checkWithInvalidAuth($id){
        $client = static::createClient([], ['HTTP_X_USER_ID' => $id]);
        $client->request('DELETE', '/game/1');
        $this->assertEquals(401, $client->getResponse()->getStatusCode());
    }

    private static function dataprovider_deleteGameById_checkWithInvalidAuth(): array
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
     * @dataProvider dataprovider_deleteGameById_checkWithGameNotFound
     */
    public function test_deleteGameById_checkWithGameNotFound($id){
        $client = static::createClient([], ['HTTP_X_USER_ID' => 1]);
        $client->request('DELETE', '/game/'.$id);
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    private static function dataprovider_deleteGameById_checkWithGameNotFound(): array
    {
        return [
            ['a'],
            ['-1'],
        ];
    }

    public function test_deleteGameById_checkWithForbiddenGame(){
        $client = static::createClient([], ['HTTP_X_USER_ID' => 3]);
        $client->request('DELETE', '/game/1');
        $this->assertEquals(403, $client->getResponse()->getStatusCode());
    }

    /**
     * @dataProvider dataprovider_deleteGameById_checkValid
     */
    public function test_deleteGameById_checkValidStatusCode($id){
        $client = static::createClient([], ['HTTP_X_USER_ID' => $id]);
        $client->request('DELETE', '/game/2');
        $this->assertEquals(204, $client->getResponse()->getStatusCode());
    }

    /**
     * @dataProvider dataprovider_deleteGameById_checkValid
     */
    public function test_deleteGameById_checkGameIsDeleted($id){
        $client = static::createClient([], ['HTTP_X_USER_ID' => $id]);
        $client->request('DELETE', '/game/2');
        $this->assertEquals(204, $client->getResponse()->getStatusCode());

        $client->request('GET', '/game/2');
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    private static function dataprovider_deleteGameById_checkValid(): array
    {
        return [
            [1],
            [2],
        ];
    }
}
