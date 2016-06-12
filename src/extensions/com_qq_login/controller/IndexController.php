﻿<?php
class IndexController extends PwBaseController {
    private $cfg;
    public function run() {
		require Wind::getRealPath('SRC:extensions.com_qq_login.service.qq_oauth2');
		$qq = new qq_oauth2($this->cfg());
    	if(!$this->is_post()){ // 显示模板前
	    	$code = $this->getInput('code');
			//授权前
			if($this->is_usercancel()){ // user canceled
	    		$this->showMessage('您已取消了QQ登录', '/');
	    	}  else if(empty($code)){ // 授权前
				$qq->get_auth_code(); //跳转到QQ
	    	} else { //授权后
	    		$uid = $qq->get_uid($code);
	    		if($uid>0){ //该用户已授权，直接设置为已登录
		    		$this->bind_user($uid);
	    		} else {
			    	if($this->is_login()){ //should bind qq id
			    		$uid = $this->get_uid();
			    		$qq->bind_user($uid);
			    		$this->showMessage('您已成功绑定该QQ号!', '/');
			    	} else { //display to register or login
			    		$uinfo = $qq->get_user_info();
			    		if(empty($uinfo)){
				    		throw new exception('can not get user info. try again later please.');
			    		}
				    	$this->setOutput($uinfo);
				    	$this->setTemplate('index_run');
			    	}		    		
	    		}
	    	}
    	} else { //显示模板并提交表单后
    		$mail = $this->getInput('email');
    		$nick = $this->getInput('username');
    		$uid = $qq->register_user($mail,$nick,$error);
    		if(!empty($error)){
    			if(is_string($error))
		    		$this->showMessage('发生错误：'.$error, '/');
		    	else
		    		$this->showError($error);
    		} else {
			    $qq->bind_user($uid);
			    $this->bind_user($uid);
    		}
	    	// add new user
    	}
     //   $this->setTemplate('index_run');
    }
    function is_login(){
    	static $loginUser;
    	if(is_null($loginUser)){
	    	$loginUser = Wekit::getLoginUser();
    	}
	    return $loginUser->isExists();
    }
    function get_uid(){
	    $loginUser = Wekit::getLoginUser();
		return intval($loginUser->uid);
    }
    function is_usercancel(){
	    return $this->getInput('usercancel');
    }
    function is_post(){
	    return $_SERVER['REQUEST_METHOD']=='POST';
    }
    function bind_user($uid=0){
    	if($uid==0){
	    	$uid = $this->get_uid();
    	}
		Wind::import('SRV:user.srv.PwRegisterService');
        Wind::import('SRV:user.srv.PwLoginService');
		$service = new PwRegisterService();
		//$this->runHook('c_login_dorun', $login);
		//Windid::load('user.WindidUser');
		$info = $service->sysUser($uid);
		$identity = PwLoginService::createLoginIdentify($info);
		$identity = base64_encode($identity);
		$userService = Wekit::load('user.srv.PwUserService');
		$userService->updateLastLoginData($info['uid'],$this->getRequest()->getClientIp());
		$userService->createIdentity($info['uid'], $info['password']);
		$this->showMessage('咿呀呀！您终于又回来了、等你好久了，快去论坛发布帖子吧！','/');
    }
		
	private function cfg($key=false,$value=false) {
		if(is_null($this->cfg)) {
			require Wind::getRealPath('SRC:extensions.com_qq_login.service.qq_oauth_config');
			$this->cfg = new qq_oauth_config();
		}
		if(empty($key)){
			return $this->cfg;
		}
		if(empty($value)){
			return $this->cfg->$key;
		} else {
			$this->cfg->$key = $value;
		}
	} 
}