<?php
include( APPPATH.'/libraries/Snoopy.class.php' );


class Zhua extends CI_Controller {

	function __construct()
	{
		parent::__construct();	

	}


	function plan()
	{
		$min=1;
		$max=6749;

		$this->load->driver('cache');
		if ( !$num1 = $this->cache->file->get('num1'))
		{
		     $num1 = 1;
		}else{
			$num1++;
		}
		$this->cache->file->save('num1', $num1, 3600*24);

		if($num1>=$min && $num1 <=$max)
		{
			echo $num1;
			$url='http://ask.babyschool.com.cn/expanslist-'.$num1.'.html';
			$this->get_content_with_listurl($url);
		}
	}
	
	function get_content_with_listurl($url)
	{
		$snoopy = new Snoopy;
		$snoopy->agent = "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)";
		$snoopy->referer = "http://www.".rand(500,50000).".cn/";
		$snoopy->rawheaders["Pragma"] = "no-cache";
		$snoopy->rawheaders["X_FORWARDED_FOR"] = rand(1,255).".".rand(1,255).".".rand(1,255).".".rand(1,255);
		$snoopy->expandlinks = true;
		 
		 
		// fetch the text of the website www.google.com:
		if($snoopy->fetchlinks($url))
		{ 
		    // other methods: fetch, fetchform, fetchlinks, submittext and submitlinks		 
		    if(strpos($snoopy->response_code, '200'))
		    {
			    foreach ($snoopy->results as $k => $v) {
			    	if(strpos($v, 'expans-')){
			    		//get page content
			    		$snoopy_page = new Snoopy;
			    		$snoopy_page->fetch($v);
			    		//get ask and content
			    		preg_match_all('/(\<h3 class="ask"\>)(.*)(\<span\>)/',$snoopy_page->results,$match_ask);
			    		preg_match_all('/(\<meta name="Description" content=")(.*)(" \/\>)/',$snoopy_page->results,$match_detail);
			    		preg_match_all('/(\<div class=\"detail mt10\"\>)(.*)(\<\/div\>)/',$snoopy_page->results,$match_answer);
			    		

			    		$daoguo_db = $this->load->database('default', TRUE);
						//$ask_db = $this->load->database('ask', TRUE);

						$daoguo_db->from('ask_data');
						$daoguo_db->where('pageurl',$v);
						$result=$daoguo_db->get()->num_rows();

						if($result==0)
						{
							$d=array(
								'listurl'=>$url,
								'pageurl'=>$v,
								'ask'=>(isset($match_ask[2][0]))?$match_ask[2][0]:'',
								'ask_detail'=>(isset($match_detail[2][0]))?$match_detail[2][0]:'',
								'answer'=>(isset($match_answer[2][0]))?$match_answer[2][0]:'',
							);
							$daoguo_db->insert('ask_data', $d);
							//var_dump($d);
							echo $v.'<hr>';
						}else{
							echo $v.'已存在<hr>';
						}


			    	}
			    }
			    //var_dump($question);
			}
		}
		else {
		    print "Snoopy: error while fetching document: ".$snoopy->error."\n";
		}


	}//end class

	function sync_ask()
	{
		$daoguo_db = $this->load->database('default', TRUE);
		$ask_db = $this->load->database('ask', TRUE);

		$daoguo_db->from('ask_data');
		$daoguo_db->where('state',0);
		$daoguo_db->limit(10);
		$result=$daoguo_db->get()->result_array();
		if($result)
		{
			foreach ($result as $k => $v) {
				$d=array(
						'question_content'=>$v['ask'],
						'question_detail'=>$v['ask_detail'],
						'add_time'=>time(),
						'update_time'=>time(),
						'published_uid'=>rand(3,23),
						'category_id'=>1,
						);
				$question=$ask_db->insert('aws_question', $d);
				if($question && $v['answer']!='')
				{
					$answer_uid=rand(3,23);
					$dd=array(
						'question_id'=>$ask_db->insert_id(),
						'answer_content'=>$v['answer'],
						'add_time'=>time()+3600,
						'uid'=>$answer_uid,
						'category_id'=>1,
						);
					$ask_db->insert('aws_answer', $dd);
					$question_id=$ask_db->insert_id();
					//echo $question_id.'<hr>';

					$view_count=rand(9,100);
					$ask_db->set('answer_users', 1, FALSE);
					$ask_db->set('answer_count', 1, FALSE);
					$ask_db->set('answer_users', 1, FALSE);
					$ask_db->set('view_count', $view_count, FALSE);
					$ask_db->set('last_answer', $answer_uid, FALSE);
					$ask_db->where('question_id', $question_id);
					$ask_db->update('aws_question');
				}

				$daoguo_db->set('state', 1, FALSE);
				$daoguo_db->where('dataid', $v['dataid']);
				$daoguo_db->update('ask_data');
			}
		}



	}


	function tt()
	{
		$this->get_content_with_listurl('http://ask.babyschool.com.cn/expanslist-13.html');
	}

	


}
?>