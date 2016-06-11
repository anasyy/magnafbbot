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


/*********************************************** Database Related***************************************************************/
/*******************************************************************************************************************************/
/*******************************************************************************************************************************/

//Database Connecting-----------------------------------------------------------------------------------------------------------
    $servername = "mysql://$OPENSHIFT_MYSQL_DB_HOST:$OPENSHIFT_MYSQL_DB_PORT/";
    $username = "admin5Tz8fAu";
    $password = "TXmvF1jtRLvV";
    $database = "fbbot";
    $dbport = 3306;

    // Create connection
    $db = new mysqli($servername, $username, $password, $database, $dbport);

    // Check connection
    if ($db->connect_error) {
        die("Connection failed : " . $db->connect_error);
    } 
    echo "Connected successfully (".$db->host_info.")";
//------------------------------------------------------------------------------------------------------------------------------    

//Checking if the user is already in our database-------------------------------------------------------------------------------
    function checkUser($dbInstance, $id)
    {
        $sql = "SELECT `ID`, `UserId`, `UserName`, `ProfilePic`, `Country`, `Subscribed`, `Interactions` FROM `Test_Users_List` WHERE `UserId`=";
        $sql .= "'".$id."'";
        
        $result = $dbInstance->query($sql);
        
        if ($result->num_rows > 0)
        {
            return true;
        }
        else
        {
            return false;
        }
    }
//------------------------------------------------------------------------------------------------------------------------------

//Get a certain field in a user's data-------------------------------------------------------------------------------
    function getUserData($dbInstance, $id, $field)
    {
        $sql = "SELECT `";
        $sql .= $field;
        $sql .= "` FROM `Test_Users_List` WHERE `UserId`=";
        $sql .= "'".$id."'";
        
        $result = mysqli_query($dbInstance, $sql);
        $row = mysqli_fetch_array($result);
        $value = $row[$field];
        
        return $value;
    }
//------------------------------------------------------------------------------------------------------------------------------

//update a certain user info in the database---------------------------------------------------------------------------------------------    
    function updateUser($dbInstance, $id, $field, $value)
    {
        $sql = "UPDATE `Test_Users_List` SET `";
        $sql .= $field."` = ";
        $sql .= "'".$value."'";
        $sql .= " Where userId = '";
        $sql .= $id."'";
        
        if (mysqli_query($dbInstance, $sql))
        {
           echo "record updated successfully";
        }
        else
        {
           echo "Error: " . $sql . "<br>" . mysqli_error($dbInstance);
        }
    }
//------------------------------------------------------------------------------------------------------------------------------

//Insert a new user in the database---------------------------------------------------------------------------------------------    
    function insertUser($dbInstance, $id, $fname, $lname, $pic, $country, $subscribed, $interactions)
    {
        $sql = "INSERT INTO Test_Users_List (UserId, UserName, ProfilePic, Country, Subscribed, interactions) VALUES ('";
        $sql .= $id;
        $sql .= "', '";
        $sql .= $fname." ".$lname;
        $sql .= "', '";
        $sql .= $pic;
        $sql .= "', ";
        $sql .= "'".$country."'";
        $sql .= ", ";
        $sql .= $subscribed;
        $sql .= ", ";
        $sql .= $interactions;
        $sql .= ")";
        
        if (mysqli_query($dbInstance, $sql))
        {
           echo "New record created successfully";
        }
        else
        {
           echo "Error: " . $sql . "<br>" . mysqli_error($dbInstance);
        }
    }
//------------------------------------------------------------------------------------------------------------------------------

/*******************************************************************************************************************************/
/*******************************************************************************************************************************/
/*******************************************************************************************************************************/

// Make Bot Instance
$bot = new FbBotApp($token);
// Receive something
if (!empty($_REQUEST['hub_mode']) && $_REQUEST['hub_mode'] == 'subscribe' && $_REQUEST['hub_verify_token'] == $verify_token) {

    // Webhook setup request
    echo $_REQUEST['hub_challenge'];
} else {

    // Other event

    $data = json_decode(file_get_contents("php://input"), true, 512, JSON_BIGINT_AS_STRING);
    if (!empty($data['entry'][0]['messaging'])) {
        foreach ($data['entry'][0]['messaging'] as $message) {

            // Skipping delivery messages
            if (!empty($message['delivery'])) {
                continue;
            }
            
            $user = $bot->userProfile($message['sender']['id']);

            $id = $message['sender']['id'];
            $fname = $user->getFirstName();
            $lname = $user->getLastName();
            $pic = $user->getPicture();
            $country = 'Undefined';
            $subscribed = 0;
            $interactions = 1;
            

            $command = "";

            // When bot receive message from user
            if (!empty($message['message']))
            {
                if(!checkUser($db, $id))
                {
                    insertUser($db, $id, $fname, $lname, $pic, $country, $subscribed, $interactions);
                    $command = "FirstTimeVisitor";
                }
                else
                {
                    $subscribed = getUserData($db, $id, 'Subscribed');
                    $interactions = getUserData($db, $id, 'Interactions');
                    $interactions = $interactions + 1;
                    updateUser($db, $id, 'Interactions', $interactions);
                    $command = $message['message']['text']; 
                }
            }
            // When bot receive button click from user
            else if (!empty($message['postback'])) {
                $subscribed = getUserData($db, $id, 'Subscribed');
                $interactions = getUserData($db, $id, 'Interactions');
                $interactions = $interactions + 1;
                updateUser($db, $id, 'Interactions', $interactions);
                $command = $message['postback']['payload'];
            }
            
// Handle command------------------------------------------------------------------------------------------------------------------------
//---------------------------------------------------------------------------------------------------------------------------------------
//---------------------------------------------------------------------------------------------------------------------------------------
//---------------------------------------------------------------------------------------------------------------------------------------

            switch (strtolower($command)) {

                case "firsttimevisitor":
                    $bot->send(new StructuredMessage($message['sender']['id'],
                      StructuredMessage::TYPE_BUTTON,
                      [
                          'text' => "Hi ".$fname.", I'm here for you, Interested in maids.at?",
                          'buttons' => [
                              new MessageButton(MessageButton::TYPE_POSTBACK, "Yes, I'm interested")
                          ]
                      ]
                  ));
                  break;
                
                case "yes, i'm interested":
                    $bot->send(new StructuredMessage($message['sender']['id'],
                      StructuredMessage::TYPE_BUTTON,
                      [
                          'text' => "Congratulations ".$fname."! If you're working currently as a housemaid in an Arab country, you're already approved :)",
                          'buttons' => [
                              new MessageButton(MessageButton::TYPE_POSTBACK, 'Get Started')
                          ]
                      ]
                    ));
                    break;
                
                case 'start':
                case 'get started':
                    $bot->send(new Message($message['sender']['id'], "OK let's get started :) , tell us where are you now"));
                    $buttonsElementuae = [new MessageButton(MessageButton::TYPE_POSTBACK, 'UAE')];
                    $buttonsElementqatar = [new MessageButton(MessageButton::TYPE_POSTBACK, 'Qatar')];
                    $buttonsElementsaudi = [new MessageButton(MessageButton::TYPE_POSTBACK, 'Saudi')];
                    $buttonsElementbahrain = [new MessageButton(MessageButton::TYPE_POSTBACK, 'Bahrain')];
                    $buttonsElementoman = [new MessageButton(MessageButton::TYPE_POSTBACK, 'Oman')];
                    $buttonsElementkuwait = [new MessageButton(MessageButton::TYPE_POSTBACK, 'Kuwait')];
                    $bot->send(new StructuredMessage($message['sender']['id'],
                        StructuredMessage::TYPE_GENERIC,
                        [
                            'elements' => [new MessageElement("UAE", "", "http://i.imgur.com/By1Gjb0.png",$buttonsElementuae),
                                          new MessageElement("Qatar", "", "http://i.imgur.com/wcB92J1.jpg", $buttonsElementqatar),
                                          new MessageElement("Bahrain", "", "http://i.imgur.com/FeIl7E4.png", $buttonsElementbahrain),
                                          new MessageElement("Saudi", "", "http://i.imgur.com/zSwjYn4.png", $buttonsElementsaudi),
                                          new MessageElement("Oman", "", "http://i.imgur.com/FUO8icM.png", $buttonsElementoman),
                                          new MessageElement("Kuwait", "", "http://i.imgur.com/RgdXMom.jpg", $buttonsElementkuwait)]
                        ]
                    ));
                    break;    
                
                case 'uae':
                case 'qatar':
                case 'saudi':
                case 'oman':
                case 'kuwait':
                case 'bahrain':
                    $buttonsElement = [new MessageButton(MessageButton::TYPE_WEB, 'Watch videos', 'http://bit.ly/27SuFCr'),
                                       new MessageButton(MessageButton::TYPE_POSTBACK, 'Learn More'),
                                       new MessageButton(MessageButton::TYPE_POSTBACK, 'Job Info')];
                    $bot->send(new StructuredMessage($message['sender']['id'],
                        StructuredMessage::TYPE_GENERIC,
                        [
                            'elements' => [new MessageElement("Welcome to maids.at", "How may we help you?", "http://i.imgur.com/PcuBZU9.png", $buttonsElement)]
                        ]
                    ));
                    updateUser($db, $id, 'Country', $command);
                    break;
                    
                case 'learn more':
                    $bot->send(new Message($message['sender']['id'], 'When your employer tells you your exact travel date to the Philippines,immediately message us here or miss call us on +1-424-2430506 to organize your flight back to Dubai.'));
                    break;
                
                case 'job info':
                    $bot->send(new StructuredMessage($message['sender']['id'],
                      StructuredMessage::TYPE_BUTTON,
                      [
                          'text' => 'What would you like to know?',
                          'buttons' => [
                              new MessageButton(MessageButton::TYPE_POSTBACK, 'My salary?'),
                              new MessageButton(MessageButton::TYPE_POSTBACK, 'Benefits I get?')
                          ]
                      ]
                  ));
                    break;
                
                case 'my salary?':
                    $bot->send(new Message($message['sender']['id'], "Your salary will start from 18,000 pesos"));
                    break;
                
                case 'benefits i get?':
                    $bot->send(new Message($message['sender']['id'], "You'll get one day off per week, Free food, Free medical insurance, and Free tickets home. You work for kind clients, and  When you are ill, you take the day off."));
                    break;
                    
                case 'subscribe':
                    updateUser($db, $id, 'Subscribed', 1);
                    $bot->send(new Message($message['sender']['id'], "Great, you'll get our news letter every Sunday :)
remember You can unsubscribe at anytime by typing ' Unsubscribe ', but that will make us sad :("));
                    break;
                
                case 'unsubscribe':
                    updateUser($db, $id, 'Subscribed', 0);
                    $bot->send(new Message($message['sender']['id'], "Unsubscribed, but that's really breaks our hearts, ".$fname." :("));
                    break;
                
                case '':
                    break;
                
                default:
                    $bot->send(new Message($message['sender']['id'], "Sorry ".$fname.", I don't understand what you're saying, if you feel lost, type 'Start' to get back from the beginning :)"));
            }
            
            $interactions = getUserData($db, $id, 'Interactions');
            if($interactions == 8)
            {
                $bot->send(new StructuredMessage($message['sender']['id'],
                      StructuredMessage::TYPE_BUTTON,
                      [
                          'text' => $fname.", it seems you're really interested, and we're gonna have a long-time relationship :) , do you want to subscribe to our newsletter?",
                          'buttons' => [
                              new MessageButton(MessageButton::TYPE_POSTBACK, "Subscribe")
                          ]
                      ]
                  ));
            }
        }
    }
}


