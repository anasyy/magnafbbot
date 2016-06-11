<?php

// parameters
$verify_token = '';
$token = "";

// check token at setup
if ($_REQUEST['hub_verify_token'] === $verify_token) {
  echo $_REQUEST['hub_challenge'];
  exit;
}


require_once(dirname(__FILE__) . '/vendor/autoload.php');

use pimax\FbBotApp;
use pimax\Messages\Message;
use pimax\Messages\ImageMessage;
use pimax\UserProfile;
use pimax\Messages\MessageButton;
use pimax\Messages\StructuredMessage;
use pimax\Messages\MessageElement;
use pimax\Messages\MessageReceiptElement;
use pimax\Messages\Address;
use pimax\Messages\Summary;
use pimax\Messages\Adjustment;


// Make Bot Instance
$bot = new FbBotApp($token);

/*********************************************** Database Related***************************************************************/
/*******************************************************************************************************************************/
/*******************************************************************************************************************************/

//Database Connecting-----------------------------------------------------------------------------------------------------------
    $servername = "127.0.0.1";
    $username = "danimagna";
    $password = "";
    $database = "FB_Bot_Users_List";
    $dbport = 3306;

    // Create connection
    $db = new mysqli($servername, $username, $password, $database, $dbport);

    // Check connection
    if ($db->connect_error) {
        die("Connection failed: " . $db->connect_error);
    } 
    echo "Connected successfully (".$db->host_info.")";
//------------------------------------------------------------------------------------------------------------------------------    

//Sending Messages to subscribed users in our database-------------------------------------------------------------------------------
    
    $sql = "SELECT `UserId` FROM `Test_Users_List` WHERE `Subscribed`= 1";
    $result = mysqli_query($db, $sql);
        
    while ( $row = $result->fetch_assoc() )
    {
        $bot->send(new Message($row["UserId"], "Hi, This is a news letter :)"));
    }
        
    $result->free();
    
//------------------------------------------------------------------------------------------------------------------------------

/*******************************************************************************************************************************/
/*******************************************************************************************************************************/
/*******************************************************************************************************************************/




