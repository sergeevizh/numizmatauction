<?php

/**
 * Simpla CMS
 *
 * @copyright	2011 Denis Pikusov
 * @link		http://simplacms.ru
 * @author		Denis Pikusov
 *
 */
 
require_once('Simpla.php');

class Users extends Simpla
{	
	// осторожно, при изменении соли испортятся текущие пароли пользователей
	private $salt = '8e86a279d6e182b3c811c559e6b15484';	
	
	function get_users($filter = array())
	{
		$limit = 1000;
		$page = 1;
		$group_id_filter = '';	
		$keyword_filter = '';
		$$buyer_filter = '';

		if(isset($filter['limit']))
			$limit = max(1, intval($filter['limit']));

		if(isset($filter['page']))
			$page = max(1, intval($filter['page']));

		if(isset($filter['group_id']))
			$group_id_filter = $this->db->placehold('AND u.group_id in(?@)', (array)$filter['group_id']);
		
		if(isset($filter['keyword']))
		{
			$keywords = explode(' ', $filter['keyword']);
			foreach($keywords as $keyword)
				$keyword_filter .= $this->db->placehold(
					'AND (u.name LIKE "%'.$this->db->escape(trim($keyword)).'%" 
					OR u.id LIKE "%'.$this->db->escape(trim($keyword)).'%"  
					OR u.fam LIKE "%'.$this->db->escape(trim($keyword)).'%" 
					OR u.otch LIKE "%'.$this->db->escape(trim($keyword)).'%" 
					OR u.email LIKE "%'.$this->db->escape(trim($keyword)).'%" 
					OR u.phone LIKE "%'.$this->db->escape(trim($keyword)).'%" 
					OR u.login LIKE "%'.$this->db->escape(trim($keyword)).'%" 
					OR u.last_ip LIKE "%'.$this->db->escape(trim($keyword)).'%")');
		}
		
		if(isset($filter['seller']))
		{
			$seller_filter = 'AND seller=1';
		}

		if(isset($filter['buyer']))
		{
			$seller_filter = 'AND buyer=1';
		}		

		$order = 'u.fam';
		if(!empty($filter['sort']))
			switch ($filter['sort'])
			{
				case 'date':
				$order = 'u.created DESC';
				break;
				case 'name':
				$order = 'u.fam';
				break;
			}
		

		$sql_limit = $this->db->placehold(' LIMIT ?, ? ', ($page-1)*$limit, $limit);
		// Выбираем пользователей
		$query = $this->db->placehold("SELECT 	u.id, 
												u.email, 
												u.document,
												u.login, 
												u.password, 
												u.name, 
												u.fam, 
												u.commission,
												u.seller,
												u.buyer,												
												u.otch,												
												u.area,
												u.postcode,
												u.city,
												u.region,
												u.street,
												u.number,
												u.housing,
												u.office,
												u.phone,
												u.group_id, 
												u.enabled, 
												u.last_ip, 
												u.created, 
												g.discount, u.bot,
												g.name as group_name FROM __users u
		                                LEFT JOIN __groups g ON u.group_id=g.id 
										WHERE 1 $group_id_filter $keyword_filter $seller_filter $buyer_filter ORDER BY $order $sql_limit");
		$this->db->query($query);
		return $this->db->results();
	}
		
	function count_users($filter = array())
	{
		$group_id_filter = '';	
		$keyword_filter = '';

		if(isset($filter['group_id']))
			$group_id_filter = $this->db->placehold('AND u.group_id in(?@)', (array)$filter['group_id']);
		
		if(isset($filter['keyword']))
		{
			$keywords = explode(' ', $filter['keyword']);
			foreach($keywords as $keyword)
				$keyword_filter .= $this->db->placehold(
					'AND (u.name LIKE "%'.$this->db->escape(trim($keyword)).'%" 
					OR u.id LIKE "%'.$this->db->escape(trim($keyword)).'%"  
					OR u.fam LIKE "%'.$this->db->escape(trim($keyword)).'%" 
					OR u.otch LIKE "%'.$this->db->escape(trim($keyword)).'%" 
					OR u.email LIKE "%'.$this->db->escape(trim($keyword)).'%" 
					OR u.phone LIKE "%'.$this->db->escape(trim($keyword)).'%" 
					OR u.login LIKE "%'.$this->db->escape(trim($keyword)).'%" 
					OR u.last_ip LIKE "%'.$this->db->escape(trim($keyword)).'%")');
		}

		// Выбираем пользователей
		$query = $this->db->placehold("SELECT count(*) as count FROM __users u
		                                LEFT JOIN __groups g ON u.group_id=g.id 
										WHERE 1 $group_id_filter $keyword_filter ORDER BY u.name");
		$this->db->query($query);
		return $this->db->result('count');
	}

		
	function get_user($id)
	{
		if(gettype($id) == 'string')
			$where = $this->db->placehold(' WHERE u.email=? ', $id);
		else
			$where = $this->db->placehold(' WHERE u.id=? ', intval($id));
	
		// Выбираем пользователя
		$query = $this->db->placehold("SELECT u.id, u.email, u.password, u.name, 
												u.area,
												u.login, 
												u.document,
												u.fam, 
												u.seller,
												u.buyer,
												u.commission,
												u.otch, 
												u.postcode,
												u.city,
												u.region,
												u.street,
												u.number,
												u.housing,
												u.office,
												u.phone,	u.bot, 											
			u.group_id, u.enabled, u.last_ip, u.created, g.discount, g.name as group_name FROM __users u LEFT JOIN __groups g ON u.group_id=g.id $where LIMIT 1", $id);
		$this->db->query($query);
		$user = $this->db->result();
		if(empty($user))
			return false;
		$user->discount *= 1; // Убираем лишние нули, чтобы было 5 вместо 5.00
		return $user;
	}
	
	public function add_user($user)
	{
		$user = (array)$user;
		if ($user->group_id==0) $user->group_id == 1;
		if(isset($user['password']))
			$user['password'] = md5($this->salt.$user['password'].md5($user['password']));
			
		$query = $this->db->placehold("SELECT count(*) as count FROM __users WHERE email=?", $user['email']);
		$this->db->query($query);
		
		if($this->db->result('count') > 0)
			return false;
		
		$query = $this->db->placehold("INSERT INTO __users SET ?%", $user);
		$this->db->query($query);
		return $this->db->insert_id();
	}
		
	public function update_user($id, $user)
	{
		$user = (array)$user;
		if(isset($user['password']))
			$user['password'] = md5($this->salt.$user['password'].md5($user['password']));
		$query = $this->db->placehold("UPDATE __users SET ?% WHERE id=? LIMIT 1", $user, intval($id));
		$this->db->query($query);
		return $id;
	}
	
	/*
	*
	* Удалить пользователя
	* @param $post
	*
	*/	
	public function delete_user($id)
	{
		if(!empty($id))
		{
			$query = $this->db->placehold("UPDATE __orders SET user_id=NULL WHERE id=? LIMIT 1", intval($id));
			$this->db->query($query);
			
			$query = $this->db->placehold("DELETE FROM __users WHERE id=? LIMIT 1", intval($id));
			if($this->db->query($query))
				return true;
		}
		return false;
	}		
	
	function get_groups()
	{
		// Выбираем группы
		$query = $this->db->placehold("SELECT g.id, g.name, g.discount FROM __groups AS g ORDER BY g.discount");
		$this->db->query($query);
		return $this->db->results();
	}	
	
	function get_group($id)
	{
		// Выбираем группу
		$query = $this->db->placehold("SELECT * FROM __groups WHERE id=? LIMIT 1", $id);
		$this->db->query($query);
		$group = $this->db->result();

		return $group;
	}	
	
	
	public function add_group($group)
	{
		$query = $this->db->placehold("INSERT INTO __groups SET ?%", $group);
		$this->db->query($query);
		return $this->db->insert_id();
	}
		
	public function update_group($id, $group)
	{
		$query = $this->db->placehold("UPDATE __groups SET ?% WHERE id=? LIMIT 1", $group, intval($id));
		$this->db->query($query);
		return $id;
	}
	
	public function delete_group($id)
	{
		if(!empty($id))
		{
			$query = $this->db->placehold("UPDATE __users SET group_id=NULL WHERE group_id=? LIMIT 1", intval($id));
			$this->db->query($query);
			
			$query = $this->db->placehold("DELETE FROM __groups WHERE id=? LIMIT 1", intval($id));
			if($this->db->query($query))
				return true;
		}
		return false;
	}		
	
	public function check_password($email, $password)
	{
		$encpassword = md5($this->salt.$password.md5($password));
		$query = $this->db->placehold("SELECT id FROM __users WHERE email=? AND password=? LIMIT 1", $email, $encpassword);
		$this->db->query($query);
		if($id = $this->db->result('id'))
			return $id;
		return false;
	}


	public function add_document($user_id, $file)
	{
		$image = $this->image->upload_image($file['tmp_name'], $file['name']);
		
		if ($image != false)
		{
			$this->update_user($user_id, array('document'=>$image));
		}
	}	


	public function rand_str($length = 8, $chars = '1234567890numizat') {
	     $chars_length = (strlen($chars) - 1);
	     $string = $chars{rand(0, $chars_length)};
	     for ($i = 1; $i < $length; $i = strlen($string))  {
	         $r = $chars{rand(0, $chars_length)};
	         if ($r != $string{$i - 1}) $string .=  $r;
	     }
	     return $string;
	}
	
}