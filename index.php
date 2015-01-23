<?php
session_start();
/**
                                                                        
    ███████╗███████╗████████╗████████╗██╗███╗   ██╗ ██████╗ ███████╗    
    ██╔════╝██╔════╝╚══██╔══╝╚══██╔══╝██║████╗  ██║██╔════╝ ██╔════╝    
    ███████╗█████╗     ██║      ██║   ██║██╔██╗ ██║██║  ███╗███████╗    
    ╚════██║██╔══╝     ██║      ██║   ██║██║╚██╗██║██║   ██║╚════██║    
    ███████║███████╗   ██║      ██║   ██║██║ ╚████║╚██████╔╝███████║    
    ╚══════╝╚══════╝   ╚═╝      ╚═╝   ╚═╝╚═╝  ╚═══╝ ╚═════╝ ╚══════╝    
                                                                        
*/

// 'terminal' color scheme
$bgcolor           = '#888';
$fgcolor           = '#333';
$selectioncolor    = '#00E';

// psql settings and instantiationerino
$pshell            = new psqlWebShell;
$pshell->port      = '5432';
$pshell->defaultdb = 'postgres';
$pshell->user      = 'postgres';


/**
                                                
     ██████╗██╗      █████╗ ███████╗███████╗    
    ██╔════╝██║     ██╔══██╗██╔════╝██╔════╝    
    ██║     ██║     ███████║███████╗███████╗    
    ██║     ██║     ██╔══██║╚════██║╚════██║    
    ╚██████╗███████╗██║  ██║███████║███████║    
     ╚═════╝╚══════╝╚═╝  ╚═╝╚══════╝╚══════╝    
                                                
*/



/****
 * 
 *  Hi, i'm the psqlWebShell thingy
 *  I basically just execute psql commands and return the results
 *
 */
class psqlWebShell {
	public $user;
	public $port;
	public $defaultdb;
	public $cur_db;

	public function runPSQLCMD($cmd, $db)
	{
		// escape backslashes
		$quote_fix = str_replace('\\', '\\\\', $cmd);

		// escape the double quotes plz
		$quote_fix = str_replace('"', '\"', $quote_fix);

		// prepare the command
		$prepd_cmd = 'psql -U '.$this->user.' -p '.$this->port.' -d '.$db.' -c "' . $quote_fix . '"';
		
		// some weird proc_open voodoo
		$descr = array(
		    1 => array(
		        'pipe',
		        'w'
		    )
		);
		$pipes = array();
		$returned = '';

		// run it and get the output
		$process = proc_open($prepd_cmd, $descr, $pipes);
		if (is_resource($process)) {
		    while ($f = fgets($pipes[1])) {
		        $returned .= $f;
		    }
		    fclose($pipes[1]);
		}
		return $returned;
	}

	public function getDBArray() 
	{
		// query postgres for a list of dbs
		$dbs_raw = $this->runPSQLCMD('SELECT datname FROM pg_database WHERE datistemplate = false;', $this->defaultdb);

		// splode it into array
		$dbs_ar = explode("\n", $dbs_raw);

		// shift off the title, line, result number and trailing linebreak... did i go too far with shift/pop? no.
		array_shift($dbs_ar);
		array_shift($dbs_ar);
		array_pop($dbs_ar);
		array_pop($dbs_ar);
		array_pop($dbs_ar);

		return $dbs_ar;
	}

	public function getDropdownList($ar, $id) 
	{
		// create a nice select element and make the current database the default selection
		$db_html = '<select name="'.$id.'">';
		foreach ($ar as $value) {
			$db_html .= "<option ". ( trim($value) == $this->cur_db ? "SELECTED" : "" ) . " value='" . trim($value) . "'>" . trim($value) . "</option>\n";
		}
		$db_html .= '</select>';	
		return $db_html;
	}
}

/**
                                             
    ██╗      ██████╗  ██████╗ ██╗ ██████╗    
    ██║     ██╔═══██╗██╔════╝ ██║██╔════╝    
    ██║     ██║   ██║██║  ███╗██║██║         
    ██║     ██║   ██║██║   ██║██║██║         
    ███████╗╚██████╔╝╚██████╔╝██║╚██████╗    
    ╚══════╝ ╚═════╝  ╚═════╝ ╚═╝ ╚═════╝    
                                             
*/


// get selected db from POST or default to default db
if(!isset($_POST['db'])) {
	// default to postgres
	$_SESSION['db'] = $pshell->defaultdb;
} else {
	$_SESSION['db'] = $_POST['db'];
}
$pshell->cur_db = $_SESSION['db'];

// get the list of available dbs
$dbar = $pshell->getDBArray();
$db_html = $pshell->getDropdownList($dbar, 'db');

// clear buffer if requested, ignore command input
if(isset($_POST['clear'])) {
	$_SESSION['history'] = "-- cleared --\n";
	
} else if(isset($_POST['command'])) {
	// otherwise, run command
	$cmd = $_POST['command'];
	$returned = $pshell->runPSQLCMD($cmd, $pshell->cur_db);

	// update log buffer thing
	$_SESSION['history'] .= $cmd . "\n";
	$_SESSION['history'] .= $returned . "\n";
}

// get log history buffer thing for echo in body, ok
$log = $_SESSION['history'];


/**
                                            
    ██╗  ██╗████████╗███╗   ███╗██╗         
    ██║  ██║╚══██╔══╝████╗ ████║██║         
    ███████║   ██║   ██╔████╔██║██║         
    ██╔══██║   ██║   ██║╚██╔╝██║██║         
    ██║  ██║   ██║   ██║ ╚═╝ ██║███████╗    
    ╚═╝  ╚═╝   ╚═╝   ╚═╝     ╚═╝╚══════╝    
                                            
*/


?>
<!DOCTYPE html>
<html>
<head>
	<title>PSQL</title>
	<style>
	.command {
		width:100%;
		height:100px;
		color:<?php echo $fgcolor; ?>;
		background-color:<?php echo $bgcolor; ?>;
		border: 0px;
		font-family:"Lucida Console", Monaco, monospace;
		font-size: 17px;
	}
	body, html {
  		padding:0;
  		margin:0;
  		background-color:<?php echo $bgcolor; ?>;
  		font-family:"Lucida Console", Monaco, monospace;
  		font-size: 17px;
  		color:<?php echo $fgcolor; ?>;
  		height:100%;
	}
	</style>
</head>
<body>
	<pre><?php echo $log; ?></pre>
	<form method="POST">
		<textarea class="command" name="command" autofocus></textarea>
		DB: <?php echo $db_html; ?> 
		<input type="submit" value="execute"> 
		<button name="clear">clear buffer</button>
	</form>
</body>
<script>
	// scroll to bottom of page just like command prompt, wheee
	document.onready = function () {
		window.scrollTo(0,document.body.scrollHeight);
	};
</script>
</html>

