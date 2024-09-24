<?

include __DIR__ . "/../includes/config.php";
include __DIR__ . "/bin/functions.php";



block_set('config', $config);


// недопустимые символы в имени модуля
$op = str_replace(array("index", "/"), "", @$_GET['op']);



// простая авторизация
if(!(@$_SERVER['PHP_AUTH_USER'] == 'admin')
    || !($config['pass_admin'] == @$_SERVER['PHP_AUTH_PW'])){
  header('WWW-Authenticate: Basic realm="admin"');
  sleep(1);
  die("<h1 style='margin-bottom:5px;'>403 Forbidden</h1><div>You don't have permission to access on this server.</div>");
}

block_set('title', ucfirst($op));
echo head();
echo menu();

if(!empty($op) and file_exists(__DIR__ . "/{$op}.php")){
  $en = true;
  include __DIR__ . "/{$op}.php";
  if($op != 'api') echo foot();
}else{
  header("Location: /cas/?op=smartcards");
}

exit;




/*

MM    MM EEEEEEE NN   NN UU   UU 
MMM  MMM EE      NNN  NN UU   UU 
MM MM MM EEEEE   NN N NN UU   UU 
MM    MM EE      NN  NNN UU   UU 
MM    MM EEEEEEE NN   NN  UUUUU  
                                 

*/

function menu(){
  
  $ops = array(
    'smartcards' => "<i class='fas fa-sim-card'></i> My Smartcards",
    'ecmg' => "<i class='fas fa-hdd'></i> ECM Generators",
    'emmg' => "<i class='far fa-hdd'></i> EMM Generators",
    'log' => "<i class='fas fa-clipboard-check'></i> Logs",
    '/' => "<i class='fas fa-door-open'></i> Exit"
  );
  
  $o = '<nav class="navbar navbar-expand-lg navbar-dark bg-danger">
  	<a class="navbar-brand" href="/cas"><i class="fas fa-user-shield"></i> Admin Panel</a>
  	<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbar1" aria-controls="navbar1" aria-expanded="false" aria-label="Toggle navigation">
    	<span class="navbar-toggler-icon"></span>
  	</button>

  	<div class="collapse navbar-collapse" id="navbar1">
    	<ul class="navbar-nav mr-auto">';
    foreach($ops as $k=>$v){
      $o .= '<li class="nav-item' . (@$_GET['op'] == $k ? ' active' : '') . '">
              <a class="nav-link" href="' . ($k=="/"?$k:"/cas/?op={$k}") . '">' . $v . (@$_GET['op'] == $k ? ' <span class="sr-only">(current)</span>' : '') . '</a>
            </li>';
    }
   $o .= '</ul>';
   
   // форму поиска покаываем только в смарткартах и логах
   if(@$_GET['op']=='smartcards' or @$_GET['op']=='log'){
     $search = (!empty($_POST['search']) ? reredos($_POST['search']) : (@$_GET['s'] ? reredos($_GET['s']) : ""));
     block_set('search', $search);
     
     $o .= '<form action="" method="post" class="form-inline my-2 my-lg-0">
      		<input name="search" value="' . $search . '" class="form-control mr-sm-2" type="search" placeholder="Search ' . $_GET['op'] . '" aria-label="Search">
      		<button class="btn btn-outline-light my-2 my-sm-0" type="submit">Search</button>
    	</form>';
   }
   $o .= '</div></nav>';
  

  
  return $o;
}



?>
