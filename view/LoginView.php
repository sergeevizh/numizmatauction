<?PHP

require_once('View.php');

class LoginView extends View
{
	function fetch()                            
	{		
		if ($this->user) header('Location: /user/bets');
		// Выход
		if($this->request->get('action') == 'logout')
		{
			unset($_SESSION['user_id']);
			header('Location: '.$this->config->root_url);
			exit();
		}
		// Вспомнить пароль
		elseif($this->request->get('action') == 'password_remind')
		{
			// Если запостили email
			if($this->request->method('post') && $this->request->post('email'))
			{
				$email = $this->request->post('email');
				$this->design->assign('email', $email);
				
				// Выбираем пользователя из базы
				$user = $this->users->get_user($email);
				if(!empty($user))
				{
					// Генерируем новый пароль и меняем его
					
					$new_password = $this->users->rand_str();
					$this->users->update_user($user->id, array('password'=>$new_password));
					
					// Отправляем письмо пользователю с новым паролем
					$this->notify->email_new_password($user->id, $new_password);
					$this->design->assign('email_sent', true);
				}
				else
				{
					$this->design->assign('error', 'user_not_found');
				}
			}
			// Если к нам перешли по ссылке для восстановления пароля
			elseif($this->request->get('code'))
			{
				// Проверяем существование сессии
				if(!isset($_SESSION['password_remind_code']) || !isset($_SESSION['password_remind_user_id']))
				return false;
				
				// Проверяем совпадение кода в сессии и в ссылке
				if($this->request->get('code') != $_SESSION['password_remind_code'])
					return false;
				
				// Выбераем пользователя из базы
				$user = $this->users->get_user(intval($_SESSION['password_remind_user_id']));
				if(empty($user))
					return false;
				
				// Залогиниваемся под пользователем и переходим в кабинет для изменения пароля
				$_SESSION['user_id'] = $user->id;
				header('Location: '.$this->config->root_url.'/user/bets');
			}
			return $this->design->fetch('password_remind.tpl');
		}
		// Вход
		elseif($this->request->method('post') && $this->request->post('login'))
		{				
			$email			= $this->request->post('email');
			$password		= $this->request->post('password');
			
			$this->design->assign('email', $email);
		
			if($user_id = $this->users->check_password($email, $password))
			{
				$user = $this->users->get_user($email);
				if($user->group_id==2)
				{
					$_SESSION['user_id'] = $user_id;
					$this->users->update_user($user_id, array('last_ip'=>$_SERVER['REMOTE_ADDR']));
										

					if(!empty($_SESSION['current_page']) && stripos($_SESSION['current_page'],'lot') != false)
						header('Location: '.$_SESSION['current_page']);				
					else
						header('Location: http://numisrus.ru/user/bets');
					
				}
				else
				{
					$this->design->assign('error', 'user_disabled');
				}
			}
			else
			{
				$this->design->assign('error', 'login_incorrect');
			}				
		}	
		return $this->design->fetch('login.tpl');
	}	
}
