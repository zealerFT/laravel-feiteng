<?php
/**
 * Created by PhpStorm.
 * User: aishan
 * Date: 16-6-16
 * Time: 下午4:39
 */
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
class LoginTest extends TestCase
{
    /**
     *测试普通用户登陆
     *
     * @return void
     */
    public function testUserLogin()
    {
        $testData = [
            'mobile'=>'13317140411',
            'password'=>'qqqq1111'
        ];
        $verifyData = [
            'code'=>'200',
        ];
        $this->post('/user/login', $testData)
            ->seeJson($verifyData)->see('token');
    }

    /**
     * 测试带openId的普通登陆
     */
    public function testUserLoginWithOpenId(){
        $testData = [
            'mobile'=>'13317140411',
            'password'=>'qqqq1111',
            'openId'    =>'oaHtnwLN5Ox1aoNMeQoGsNIQjOOw'
        ];
        $verifyData = [
            'code'=>'200',
        ];
        $this->post('/user/login', $testData)
            ->seeJson($verifyData)->see('token');
    }



}