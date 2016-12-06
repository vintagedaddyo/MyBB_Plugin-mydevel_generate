<?php
/**
* MyDevel: Generate 1.0
* Copyright 2010 Aries-Belgium
*
* $Id$
*/

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook('admin_config_menu','mydevel_generate_admin_config_menu');
$plugins->add_hook('admin_config_action_handler','mydevel_generate_admin_config_action_handler');
$plugins->add_hook('admin_load','mydevel_generate_admin_load');


function mydevel_generate_info()
{
	return array(
		"name"			=> "MyDevel: Generate",
		"description"	=> "Generate users, threads and posts for development purposes.",
		"website"		=> "",
		"author"		=> "Aries-Belgium",
		"authorsite"	=> "mailto:aries.belgium@gmail.com",
		"version"		=> "1.1",
		"guid" 			=> "f16071e2b1e9bef5c2902fe2cb8acb63",
		"compatibility" => "16*"
	);
}

function mydevel_generate_admin_config_menu($sub_menu)
{
	global $mybb;
	if((int)$mybb->user['uid'] == 1)
	{
		$sub_menu['300'] = array("id" => "mydevel_generate", "title" => "Devel: Generate", "link" => "index.php?module=config-mydevel_generate");
	}
}

function mydevel_generate_admin_config_action_handler($actions)
{
	$actions['mydevel_generate'] = array('active' => 'mydevel_generate', 'file' => '');
}

function mydevel_generate_admin_load()
{	
	global $mybb,$page;
	
	if($mybb->input['module'] == "config-mydevel_generate")
	{
		$sub_tabs = array(
			"users" => array(
				'title'=> "Users",
				'link' => 'index.php?module=config-mydevel_generate&amp;action=users',
				'description' => "Generate users."
			),
			"threads" => array(
				'title' => "Threads",
				'link' => 'index.php?module=config-mydevel_generate&amp;action=threads',
				'description' => "Generate threads with posts in them."
			),
			"posts" => array(
				'title'=> "Posts",
				'link' => 'index.php?module=config-mydevel_generate&amp;action=posts',
				'description' => "Generate posts within existing threads."
			),
		);
		
		$page->add_breadcrumb_item("Devel: Generate","index.php?module=config-mydevel_generate");
		
		$page->output_header();
		
		switch($mybb->input['action'])
		{
			case 'posts':
				if($mybb->request_method == 'post')
				{
					require_once MYBB_ROOT."inc/datahandlers/post.php";
					
					$count = (int)$mybb->input['count'];
					$fids = $mybb->input['forum'];
					$random_user = $mybb->input['random_user'] == '1' ? true : false;
					$random_icon = $mybb->input['random_icon'] == '1' ? true : false;
					
					$i=0;
					while($i<$count)
					{
						$user = mydevel_generate_get_user($random_user);
						$thread = mydevel_generate_get_thread($fids);
						$post = array(
							"tid" => $thread['tid'],
							"replyto" => $thread['tid'] ,
							"fid" => $thread['fid'],
							"subject" => "RE: ".$thread['subject'],
							"icon" => mydevel_generate_get_icon($random_icon),
							"uid" => $user['uid'],
							"username" => $user['username'],
							"message" => mydevel_generate_lorem_ipsum(250,3),
							"ipaddress" => get_ip(),
							"posthash" => md5($user['uid'].random_str())
						);
						
						$posthandler = new PostDataHandler("insert");
						$posthandler->action = "thread";
						$posthandler->set_data($post);
						$valid_post = $posthandler->validate_post();
						if($valid_post)
						{
							$post_info = $posthandler->insert_post();
							$in[$thread['tid']] = true;
							$i++;
						}
					}
					
					$in_count = count(array_keys($in));
					flash_message("Generated {$count} posts in {$in_count} threads.",'success');
					admin_redirect("index.php?module=config-mydevel_generate&amp;action=posts");
				}
				
				$page->output_nav_tabs($sub_tabs,'posts');
				
				$form = new Form("index.php?module=config-mydevel_generate&amp;action=posts","POST");
				
				$form_container = new FormContainer("Generate posts");
				
				$form_container->output_row(
					"Count",
					"How many posts would you like to generate?",
					$form->generate_text_box('count', "", array('id' => 'count')),
					'count'
				);
				
				$form_container->output_row(
					"Forums",
					"Select the forums in which you would like to generate the posts.",
					$form->generate_forum_select('forum[]', "", array('id' => 'forum','multiple'=>true,'size'=>5)),
					'forum'
				);
				
				$form_container->output_row(
					"Use random users",
					"Do you want to use a different (random) user for each post?",
					$form->generate_yes_no_radio('random_user','yes')
				);
				
				$form_container->output_row(
					"Use random post icons",
					"Do you want to use different (random) icons for each thread?",
					$form->generate_yes_no_radio('random_icon','yes')
				);
				
				$form_container->end();
				
				$buttons[] = $form->generate_submit_button("Generate");
				$form->output_submit_wrapper($buttons);
				
				$form->end();
				
				break;
			case 'threads':
				if($mybb->request_method == 'post')
				{
					require_once MYBB_ROOT."inc/datahandlers/post.php";
					
					$count = (int)$mybb->input['count'];
					$fids = $mybb->input['forum'];
					$random_user = $mybb->input['random_user'] == '1' ? true : false;
					$random_icon = $mybb->input['random_icon'] == '1' ? true : false;
					
					$i=0;
					while($i<$count)
					{
						$user = mydevel_generate_get_user($random_user);
						$forum = mydevel_generate_get_forum($fids);
						$new_thread = array(
							"fid" => $forum['fid'],
							"prefix" => 0,
							"subject" => mydevel_generate_lorem_ipsum(5,3),
							"icon" => mydevel_generate_get_icon($random_icon),
							"uid" => $user['uid'],
							"username" => $user['username'],
							"message" => mydevel_generate_lorem_ipsum(250,3),
							"ipaddress" => get_ip(),
							"posthash" => md5($user['uid'].random_str())
						);
						
						$posthandler = new PostDataHandler("insert");
						$posthandler->action = "thread";
						$posthandler->set_data($new_thread);
						if($posthandler->validate_thread())
						{
							$thread_info = $posthandler->insert_thread();
							$i++;
						}
					}
					
					flash_message("Generated {$count} threads.",'success');
					admin_redirect("index.php?module=config-mydevel_generate&amp;action=threads");
				}
				
				$page->output_nav_tabs($sub_tabs,'threads');
				
				$form = new Form("index.php?module=config-mydevel_generate&amp;action=threads","POST");
				
				$form_container = new FormContainer("Generate threads");
				
				$form_container->output_row(
					"Count",
					"How many threads would you like to generate?",
					$form->generate_text_box('count', "", array('id' => 'count')),
					'count'
				);
				
				$form_container->output_row(
					"Forums",
					"Select the forums in which you would like to generate the threads in.",
					$form->generate_forum_select('forum[]', "", array('id' => 'forum','multiple'=>true,'size'=>5)),
					'forum'
				);
				
				$form_container->output_row(
					"Use random users",
					"Do you want to use a different (random) user for each thread?",
					$form->generate_yes_no_radio('random_user','yes')
				);
				
				$form_container->output_row(
					"Use random post icons",
					"Do you want to use different (random) icons for each thread?",
					$form->generate_yes_no_radio('random_icon','yes')
				);
				
				$form_container->end();
				
				$buttons[] = $form->generate_submit_button("Generate");
				$form->output_submit_wrapper($buttons);
				
				$form->end();
				break;
			case 'users':
			default:
				if($mybb->request_method == 'post')
				{
					require_once MYBB_ROOT."inc/datahandlers/user.php";
					
					$count = (int)$mybb->input['count'];
					$gids = $mybb->input['group'];
					$random_avatar = $mybb->input['random_avatar'] == '1' ? true : false;
					
					$i=0;
					while($i<$count)
					{
						$username = mydevel_generate_lorem_ipsum(1,6);
						$gid = mydevel_generate_get_group($gids);
						$new_user = array(
							"username" => $username,
							"password" => "devel123",
							"password2" => "devel123",
							"email" => strtolower($username)."@devel.gen",
							"email2" => strtolower($username)."@devel.gen",
							"usergroup" => $gid,
							"displaygroup" => $gid,
							"profile_fields_editable" => true,
						);
						mydevel_generate_set_avatar($new_user,$random_avatar);
						
						$userhandler = new UserDataHandler('insert');
						$userhandler->set_data($new_user);
						if($userhandler->validate_user())
						{
							$userhandler->insert_user();
							$i++;
						}
					}
					
					flash_message("Generated {$count} users.",'success');
					admin_redirect("index.php?module=config-mydevel_generate&amp;action=users");
				}
				
				$page->output_nav_tabs($sub_tabs,'users');
				
				$form = new Form("index.php?module=config-mydevel_generate&amp;action=users","POST");
				
				$form_container = new FormContainer("Generate threads");
				
				$form_container->output_row(
					"Count",
					"How many threads would you like to generate?",
					$form->generate_text_box('count', "", array('id' => 'count')),
					'count'
				);
				
				$form_container->output_row(
					"Groups",
					"Select the group(s) in which you would like to generate the users.",
					$form->generate_group_select('group[]', "", array('id' => 'group','multiple'=>true,'size'=>5)),
					'group'
				);
				
				$form_container->output_row(
					"Use random user avatars",
					"Do you want to use different (random) avatars for each user?",
					$form->generate_yes_no_radio('random_avatar','yes')
				);
				
				$form_container->end();
				
				$buttons[] = $form->generate_submit_button("Generate");
				$form->output_submit_wrapper($buttons);
				
				$form->end();
				break;
		}		
		
		$page->output_footer();
		
		die();
	}
}

function mydevel_generate_lorem_ipsum($words=0,$min_word_length=0)
{
	$lorem = "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam ac euismod ipsum. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Donec est urna, ullamcorper ut congue vel, luctus a erat. Integer placerat enim eu purus lobortis ut lacinia mi adipiscing. Donec bibendum volutpat luctus. Maecenas venenatis est ut metus iaculis vitae convallis dolor vulputate. Nam pulvinar dolor ac turpis porttitor eu pulvinar nisl euismod. Nulla tortor diam, porta ut dictum vitae, auctor vitae lectus. Cras et risus turpis. In commodo posuere neque, ac semper lectus semper id. Praesent ut sapien lorem, in hendrerit velit. Duis vitae eros eget velit tincidunt euismod. Nullam mi massa, hendrerit non fringilla nec, egestas id nibh. Fusce erat enim, molestie in eleifend in, pretium sit amet urna. Donec sem felis, posuere nec porta luctus, gravida sed magna. Aenean bibendum mattis elementum. Nunc sagittis nibh sit amet mauris aliquam consectetur. Integer sodales, risus at hendrerit pellentesque, elit felis tincidunt leo, ac tincidunt nisi enim non risus.
	Nunc in dictum nisi. Integer nisl metus, adipiscing id tempor sed, semper at turpis. Mauris nec metus nec tellus malesuada elementum vulputate non mi. Suspendisse ut nisi et tellus molestie ornare sed iaculis ligula. Cras convallis rutrum magna, ut venenatis magna rhoncus id. Maecenas ut molestie diam. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Praesent sit amet nunc ipsum. Nunc egestas ornare neque, ut scelerisque lacus pharetra a. Morbi et tincidunt arcu. Mauris dui turpis, sollicitudin bibendum sagittis ac, interdum ut neque. Ut quis nibh libero.
	Ut facilisis nisl non purus sagittis et aliquet metus ornare. Sed ac vestibulum neque. Aliquam tincidunt mi sed arcu venenatis gravida. Quisque mollis pellentesque quam, vitae aliquet massa consectetur faucibus. Aliquam egestas tortor in ipsum viverra eu interdum felis faucibus. Maecenas quis tellus non arcu faucibus pretium eu in nunc. Cras ut nulla vitae diam tempus lacinia. Phasellus ac erat ac ligula scelerisque euismod non vel tortor. In at libero ac lectus tempus elementum. Donec fringilla turpis vel dui interdum ac faucibus leo rutrum.
	Duis id eros quis justo euismod tincidunt. Quisque nec nibh nunc, id suscipit arcu. Phasellus vel blandit augue. Etiam enim massa, sollicitudin ut posuere id, ultrices eget est. Mauris dui diam, sodales eu semper eu, lobortis sed felis. Aliquam sollicitudin libero vitae magna ultricies commodo. Donec ac risus sapien. Proin ac dui fringilla dolor aliquet sagittis quis consequat eros. Morbi a ante vel nunc aliquam viverra quis non nisi. Sed vel ullamcorper neque. Morbi id enim mi. Vivamus nisi lectus, eleifend et condimentum a, aliquam at lacus. Vivamus sed risus metus, quis vestibulum lorem. Integer fringilla sodales neque, nec adipiscing mauris venenatis in. Nulla metus turpis, volutpat eu pharetra non, gravida ac nisi. Duis gravida suscipit tortor, sed auctor mauris porttitor sed. Nulla a mollis mi. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Nullam metus nisl, ultricies dapibus eleifend quis, porta id nisl. Duis bibendum commodo arcu id mollis.
	Aliquam quis porttitor ipsum. Mauris non risus leo. Fusce pellentesque faucibus sapien, ac auctor tellus suscipit ut. Pellentesque augue nunc, feugiat eu rutrum ac, dignissim nec metus. Duis nec enim nibh, id porta ligula. Morbi sit amet erat mauris, non egestas ligula. Sed justo magna, tincidunt nec consectetur sed, tristique iaculis lectus. Nunc vitae turpis dui, sed consectetur erat. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur vehicula magna id arcu auctor facilisis. Pellentesque augue odio, vestibulum non convallis sit amet, vulputate et velit. Morbi condimentum cursus sapien, a elementum lacus tempus nec.
	Proin at dui nibh. Curabitur ultricies nisi vitae ante ornare vulputate. Phasellus ornare mollis ante vel feugiat. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Duis id quam at justo condimentum accumsan. Vivamus et scelerisque risus. Donec nec lacus ac diam bibendum suscipit. Sed sollicitudin bibendum ultricies. Donec id nisi sapien. Aliquam non dolor vel velit porttitor posuere vitae eu nisl. Sed molestie pulvinar dapibus. Sed eget odio vitae ipsum eleifend venenatis. Maecenas leo mauris, eleifend vitae bibendum ac, fermentum eget purus. Aliquam consectetur neque nec purus bibendum eget fringilla nunc vulputate. Etiam ac velit justo, eget molestie lacus.
	Vestibulum faucibus dui justo, nec suscipit enim. Proin iaculis erat a nibh facilisis congue vel nec nunc. Proin viverra luctus aliquet. Sed eu ipsum turpis, id malesuada sem. Curabitur diam nisl, venenatis non egestas id, cursus in velit. Vivamus in eros diam, id ullamcorper nunc. Ut vel arcu at augue gravida gravida et id mi. Etiam facilisis tellus blandit ligula aliquet molestie. Nunc dolor libero, pharetra sit amet porta quis, viverra ut tortor. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae;
	Fusce semper tincidunt nisl, vel ultricies lorem condimentum sit amet. Praesent imperdiet pulvinar nibh, pellentesque posuere nibh consequat a. Vivamus tempor metus sit amet dolor hendrerit eu elementum dolor porta. Cras suscipit lobortis ligula et tristique. Donec a dui ac nisl congue pellentesque vel et libero. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur et odio tempor augue fermentum venenatis. Fusce scelerisque lacus dui, quis hendrerit massa. Aliquam placerat ultricies lobortis. Morbi aliquam ipsum et nisi sagittis eget tincidunt risus lacinia. Praesent tincidunt tincidunt arcu, id fringilla mauris consectetur nec. Ut ac dui lectus. Integer metus quam, consequat a venenatis id, faucibus ac diam.	Suspendisse tincidunt, nulla nec adipiscing aliquam, quam ligula accumsan nisl, a consequat ante nunc a urna. Morbi a sem et orci aliquet facilisis sit amet quis mi. Sed dignissim leo eu lacus adipiscing vitae venenatis odio consectetur. Aliquam sit amet nisl ut nisl feugiat rutrum sit amet vitae nisl. Sed augue urna, ultricies vitae vehicula quis, tincidunt non nulla. Cras lobortis lacinia adipiscing. Vestibulum volutpat elementum pretium. Pellentesque posuere ultricies eros sit amet dignissim. Donec non pulvinar nisi. Proin lacus sapien, ornare scelerisque congue a, adipiscing at est. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Etiam dolor urna, luctus nec congue ut, dapibus at arcu. Pellentesque id odio et metus luctus ornare. Donec in nulla quam, quis ultricies nulla. Phasellus at eros massa, at pulvinar augue. Maecenas consectetur ipsum id risus faucibus posuere.
	Cras dolor ipsum, elementum vel consequat vitae, sagittis a orci. Sed auctor neque vitae nibh feugiat bibendum. Duis leo sem, mollis at tincidunt id, viverra ut mauris. Aliquam erat volutpat. Nulla facilisi. Donec sit amet dui quis neque gravida lacinia a sit amet lectus. Sed eu fermentum massa. Sed varius velit sit amet nunc sodales vitae tempus leo rutrum. Duis id est sed libero egestas scelerisque vitae eget urna. Etiam mi eros, commodo eget sollicitudin a, semper a ante. Proin a congue lacus.
	Praesent condimentum hendrerit orci interdum tristique. Suspendisse urna risus, ultrices non ullamcorper non, congue ut odio. Aliquam accumsan erat eget nisi vestibulum vehicula. Duis quis erat augue, eget pellentesque leo. Duis sed mauris sit amet purus tincidunt faucibus. Vestibulum volutpat hendrerit mauris vitae ullamcorper. Praesent eleifend, nunc ut accumsan porta, tellus massa rhoncus turpis, vitae dignissim augue enim vitae sem. Curabitur imperdiet blandit lectus, at scelerisque lectus viverra sit amet. Nulla neque ligula, fermentum vel hendrerit at, adipiscing quis sapien. Nullam vulputate vestibulum diam, eget venenatis justo euismod eget. Sed id libero risus. Etiam eleifend turpis nec nisi fermentum pellentesque. Sed a felis eget est vestibulum tincidunt sed sit amet ante. Quisque tempus nisi in ipsum tristique sit amet porttitor purus suscipit. Aliquam sed enim sit amet est scelerisque aliquet. Praesent placerat pharetra tincidunt. Nulla egestas tincidunt pulvinar. Phasellus ut ipsum orci, et pellentesque ipsum. Nullam placerat mi sed elit volutpat et semper massa malesuada. Integer faucibus pretium blandit.
	Vestibulum et sapien cursus velit pretium vestibulum vitae rhoncus mi. Suspendisse sodales ullamcorper metus, ac auctor orci fermentum quis. Vivamus accumsan odio non elit lacinia in egestas libero mollis. Phasellus egestas ultrices luctus. Fusce eleifend sapien arcu. Aliquam et ligula felis. Phasellus eu eros quis augue commodo tincidunt. Nam faucibus blandit pellentesque. Nulla luctus dolor odio. Maecenas eget mi leo, vitae suscipit est. Nam sit amet metus quis sem feugiat pulvinar. Nunc ornare vehicula enim, quis commodo ipsum sodales non. Nulla eget ipsum at neque consectetur faucibus.
	Donec molestie interdum odio ut vehicula. Nam elementum suscipit mi eget consectetur. Sed sollicitudin tempus vehicula. Donec sodales venenatis dolor, lacinia dapibus tellus malesuada vel. Sed laoreet mauris ut dolor pellentesque ac semper ligula euismod. Pellentesque sodales augue ac odio molestie sagittis. Praesent neque dui, egestas in laoreet quis, scelerisque ac sapien. Suspendisse eu eros quis velit accumsan imperdiet. Mauris at dolor magna. Vivamus euismod, dolor et fermentum aliquam, mi nulla fringilla massa, a porttitor lectus quam ac eros. Nam ut sem eu est convallis molestie. Vivamus sodales magna a risus fermentum vestibulum. Maecenas nec tellus a felis cursus hendrerit. Nunc dictum nisi at enim cursus iaculis. Ut id leo eget eros fringilla posuere et in libero.
	Pan se illo latente. Proposito secundarimente nos da, sia duce lateres initialmente un, que se vista auxiliary. Via latino movimento paternoster al. Qui tu africa traducite, sed active programma le, non cinque specimen il. Qui tu membros specimen linguage.
	Prime romanic il per, nos tu vices magazines anglo-romanic. Tu tote cinque tamben que, per vide regula nostre de. Pro da etiam questiones, via lo regno scriber publicate. Duo in facto simplificate. In ample initialmente anteriormente pan, web al existe giuseppe, denominator association angloromanic ma sia.
	Pan libro vocabulario initialmente se, nos il instituto tentation intermediari, quotidian representantes del in. Sed su ille prime message, non latino supervivite e. De celos spatios internet via, e secundo quotidian nos. Uso su articulo primarimente. In sia practic litteratura, nos esser auxiliary ma.
	Infra tamben regula qui ma, lo nos populos linguage, su usos national connectiones sia. Signo latente programma uno su. Qui ha linguistic publicationes, duo origine parolas specimen de. Sed se europee traducite, duo un rapide proposito denomination.
	Russo debitores principalmente e nos. Super scriber litteratura es qui, al tres vocabulario secundarimente via, su articulo excellente pan. Se qualcunque secundarimente uso. Pro union initialmente il. Es post nomina sed, libro connectiones uso al.
	Post etiam traduction que lo. Su facto proposito responder qui, original summarios uno e. Non de active responder. Pan moderne involvite e. Se uno vide lista primarimente, non se ample angloromanic denomination.
	Su con rapide ascoltar, per existe simplificate un. Il sine nomine proposito non. Tu regno publicate occidental nos, de nos medio capital. Non ma nomina millennios. O avantiate occidental uno, qui al technologia simplificate.
	Publicava occidental historiettas le qui. Ma articulo abstracte uno, su sed vices origine language. Con integre latente programma es, iala populos computator ha web, ma human vostre initialmente via. Le studio magazines movimento via, in disuso specimen ascoltar nos. E nos servi laborava, voluntate continentes o per. Un uso basate traduction greco-latin, pro un subjecto specimen hereditage, latino europeo uno al.
	Per cinque nomine original e, se pan post commun supervivite, pro veni capital sanctificate o. Latino populos es sia. Lo americano appellate introductori per, texto prime historia il via, del o super anglo-romanic. O anque latente instruction uso, web lo vocabulos initialmente essentialmente, rapide quales tempore il via. Del al nostre capital. Pan es vide iala toto, es pan malo parolas, nos libere integre o.
	Non tamben sanctificate un. Qui de texto appellate. Gode vide pardona sed ma. De ultra summarios anglo-romanic sia, illo avantiate independente duo il, lingua svedese simplificate web in. Technologia encyclopedia del tu.";
	
	$words_array = preg_split("#[\n\.\ \,]+#",$lorem);
	mt_srand((double)microtime()*1000000);
	shuffle($words_array);
	
	if($words <= 0) $words = count($words_array);
	
	$output = array();
	for($i=0;$i<$words;$i++)
	{
		if(strlen($words_array[$i]) >= $min_word_length)
		{
			$output[] = $words_array[$i];
		}
		else
		{
			$words++;
		}
	}
	
	$output_str = implode(" ",$output);
	$output_str = strtolower($output_str);
	$output_str = ucfirst($output_str);
	
	return $output_str;
}

function mydevel_generate_get_user($random=false)
{
	global $db;
	
	if($random)
	{
		$users = array();
		$query = $db->simple_select("users");
		while($user = $db->fetch_array($query))
			$users[] = $user;
		
		mt_srand((double)microtime()*1000000);
		return $users[array_rand($users)];
	}
	else
	{
		return get_user(1);
	}
}

function mydevel_generate_get_group($array)
{
	global $db;
	
	mt_srand((double)microtime()*1000000);
	$gid = $array[array_rand($array)];
	
	return $gid;
}

function mydevel_generate_set_avatar(&$user,$random)
{
	if($random)
	{
		$user['avatartype'] = "gallery";
		$avatars = array();
		foreach(glob(MYBB_ROOT . "images/avatars/*") as $file)
		{
			$avatars[] = $file;
		}
		
		mt_srand((double)microtime()*1000000);
		$random_file = $avatars[array_rand($avatars)];
		list($image_w,$image_h) = getimagesize($random_file);
		$user['avatardimensions'] = $image_w . "|" . $image_h;
		$user['avatar'] = str_replace(MYBB_ROOT,"",$random_file);
	}
	else
	{
		return;
	}
}

function mydevel_generate_get_forum($array)
{
	global $db;
	
	mt_srand((double)microtime()*1000000);
	$fid = $array[array_rand($array)];
	$forum = get_forum($fid);
	if($forum['type'] == "c")
	{
		$child_forums = array();
		$query = $db->simple_select("forums","*","pid=".(int)$forum['fid']);
		while($row = $db->fetch_array($query))
			$child_forums[] = $row['fid'];
		
		return  mydevel_generate_get_forum($child_forums);
	}
	
	return $forum;
}

function mydevel_generate_get_thread($array)
{
	global $db;
	
	$forum = mydevel_generate_get_forum($array);
	$query = $db->simple_select("threads","*","fid=".(int)$forum['fid'],array('order_by'=>'RAND()','limit'=>1));
	$thread = $db->fetch_array($query);
	
	return is_array($thread) ? $thread : false;
	
}

function mydevel_generate_get_icon($random=false)
{
	global $db;
	
	if($random)
	{
		$query = $db->simple_select("icons","*","",array('order_by'=>"RAND()",'limit'=>1));
		$icon = $db->fetch_array($query);
		
		return $icon['iid'];
	}
	else
	{
		return 0;
	}
}

