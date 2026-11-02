<?php

require_once __DIR__ . "/../classes/Repositories/AdminRepository.php";
require_once __DIR__ . "/../classes/Repositories/ConversationRepository.php";
//require __DIR__ . "/../classes/Model/TestingScripts.php";

if (!isset($request) || !is_object($request)) {
    throw new RuntimeException(
        'CRITICAL PHP: Request global context not provided before admin.php file included...'
    );
}


if (empty($request->session['csrf_token'])) {
    $request->session['csrf_token'] = bin2hex(random_bytes(32));
}

// extra check
if (!(isset($request->session['admin']) && $request->session['admin'] === true)) {
    error_log("ALERT: request bypassed adminmiddleware");
    echo "403: Forbidden [CRITICAL]";
    throw new ExitException();
}

if (isset($request->get['back']) && $request->get['back'] === "true") {
    unset($request->session['conversationID']);
    $cleanUrl = strtok($request->server['REQUEST_URI'], '?');
    $request->redirect['location'] = $cleanUrl;
    header("Location: " . $cleanUrl);
    throw new ExitException();
}

// HANDLE POST ACTIONS (BUSINESS LOGIC)
if ($request->server['REQUEST_METHOD'] === 'POST') {

    // CSRF Validation FOR all
    if ((!isset($request->post['csrf_token'])) || ($request->post['csrf_token'] !== $request->session['csrf_token'])) {
        error_log("csrf threat in admin.php");
        throw new ExitException("403: Forbidden");
    }

    // Action: Create New Conversation
    if (isset($request->post['newConvoName'])) {
        $newConvoName = $request->post['newConvoName'];
        if (AdminRepository::createNewConversation($newConvoName, 0, $request->dbconn)) {
            $request->session['ui_notify'] = "Conversation " . $newConvoName . " successfully added!";
        } else {
            $request->session['ui_notify'] = "ERROR: New conversation failed... See Error Log.";
        }
        $request->redirect['location'] = $request->server['REQUEST_URI'];
        header("Location: " . $request->server['REQUEST_URI']);
        throw new ExitException();
    }

    // Action: Grant Admin Permissions
    if (isset($request->post['newAdminEmail'])) {
        $newAdmin = $request->post['newAdminEmail'];
        if (AdminRepository::setAdmin($newAdmin, $request, $request->dbconn)) {
             $request->session['ui_notify'] = "Admin " . htmlspecialchars($newAdmin) . " successfully added!";
        } else {
             $request->session['ui_notify'] = "New Admin: " . htmlspecialchars($newAdmin) . " failed.";
        }
        $request->redirect['location'] = $request->server['REQUEST_URI'];
        header("Location: " . $request->server['REQUEST_URI']);
        throw new ExitException();
    }

    // Action: Impersonate User
    if (isset($request->post['impersonateID'])) {
        $fake_id = $request->post['impersonateID'];
        $userInfo = AdminRepository::getUserInfo($fake_id, $request->dbconn);
        if ((!$userInfo) || $userInfo['is_admin']) {
            $request->session['ui_notify'] = "Couldn't impersonate user " . htmlspecialchars($fake_id) . ".";

            $request->redirect['location'] = $request->server['REQUEST_URI'];
            header("Location: " . $request->server['REQUEST_URI']);
            throw new ExitException();
        } else {
            $request->session['name'] = $userInfo['name'];
            $request->session['email'] = $userInfo['email'];$request->redirect['location'] = $request->server['REQUEST_URI'];
            $request->session['user_id'] = $fake_id;
            $request->session['impersonation'] = true;
            $request->session['admin'] = false; 
            $request->session['ui_notify'] = "Impersonating User " . htmlspecialchars($fake_id) . ". Admin perms removed.";
            
            $request->redirect['location'] = 'home';
            header("Location: home");
            throw new ExitException();
        }
    }

    // Action: Create New Mod
    if (isset($request->post['action']) && $request->post['action'] === 'create_mod' && isset($request->post['newName'])&& isset($request->post['label'])) {
        $name = $request->post['newName'];
        $label = $request->post['label'];
        if (AdminRepository::createModUser($name, $label, $request->dbconn)) {
            $request->session['ui_notify'] = "$label Profile" . $name . " was created.";
        } else {
            $request->session['ui_notify'] = "Something went wrong in creating mod $name";
        }
        $request->redirect['location'] = $request->server['REQUEST_URI'];
        header("Location: " . $request->server['REQUEST_URI']);
        throw new ExitException();
    }

    // Action: Delete a Specific Conversation
    if (isset($request->post['action']) && $request->post['action'] === 'delete' && isset($request->post['conversationID'])) {
        $cID = $request->post['conversationID'];
        if (AdminRepository::removeConversation($cID, $request->dbconn)) {
            $request->session['ui_notify'] = "Conversation #" . $cID . " was deleted.";
        } else {
            $request->session['ui_notify'] = "Conversation #" . $cID . " could not be deleted.";
        }
        $request->redirect['location'] = $request->server['REQUEST_URI'];
        header("Location: " . $request->server['REQUEST_URI']);
        throw new ExitException();
    }

    // Action: Delete an individual message
    if (isset($request->post['action']) && $request->post['action'] === 'delete_message' && isset($request->post['messageID'])) {
        $msgID = $request->post['messageID'];
        
        if (AdminRepository::deleteMessage($msgID, $request->dbconn)) {
            $request->session['ui_notify'] = "Message #" . $msgID . " deleted successfully.";
        } else {
            $request->session['ui_notify'] = "Failed to delete Message #" . $msgID . ".";
        }

        $request->redirect['location'] = $request->server['REQUEST_URI'];
        header("Location: " . $request->server['REQUEST_URI']);
        throw new ExitException();
    }

    // Action: Delete/Remove a user from a conversation
    if (isset($request->post['action']) && $request->post['action'] === 'delete_user' && isset($request->post['targetUserID'])) {
        $targetUID = $request->post['targetUserID'];
        $currentCID = $request->post['currentConvoID'];
        
        if (ConversationRepository::removeUserFromConversation($targetUID, $currentCID, $request->dbconn)) {
            $request->session['ui_notify'] = "User #" . $targetUID . " removed from conversation.";
        } else {
            $request->session['ui_notify'] = "Failed to remove User #$targetUID from conversation $currentCID.";
        }
        $request->redirect['location'] = $request->server['REQUEST_URI'];
        header("Location: " . $request->server['REQUEST_URI']);
        throw new ExitException();
    }

    // Action: Approve or reject class
    if (
        isset($request->post['action']) &&
        $request->post['action'] === 'approve_class'
    ) {
        $classID = (int)$request->post['classID'];

        if (AdminRepository::approveClass($classID, $request->dbconn)) {
            $request->session['ui_notify'] = "Class approved.";
        } else {
            $request->session['ui_notify'] = "Failed to approve class.";
        }
        
        $request->redirect['location'] = $request->server['REQUEST_URI'];
        header("Location: " . $request->server['REQUEST_URI']);
        throw new ExitException();
    }

    if (
        isset($request->post['action']) &&
        $request->post['action'] === 'reject_class'
    ) {
        $classID = (int)$request->post['classID'];

        if (AdminRepository::rejectClass($classID, $request->dbconn)) {
            $request->session['ui_notify'] = "Class rejected.";
        } else {
            $request->session['ui_notify'] = "Failed to reject class.";
        }

        $request->redirect['location'] = $request->server['REQUEST_URI'];
        header("Location: " . $request->server['REQUEST_URI']);
        throw new ExitException();
    }

    // Action: adjust conversation access settings
    if (
        isset($request->post['action']) &&
        $request->post['action'] === 'convo_access_adjustment'
    ) {
        $convoId = (int)$request->post['convoId'];
        $newAccessMode = (int)$request->post['mode'];

        if (AdminRepository::adjustConvoAccess($convoId, $newAccessMode, $request->dbconn)) {
            $request->session['ui_notify'] = "Class Updated.";
        } else {
            $request->session['ui_notify'] = "No update occurred.";
        }

        $request->redirect['location'] = $request->server['REQUEST_URI'];
        header("Location: " . $request->server['REQUEST_URI']);
        throw new ExitException();
    }
    // Action: assign user into conversation
    if (
        isset($request->post['action']) &&
        $request->post['action'] === 'assign_user_into_convo'
    ) {
        $convoId = (int)$request->post['convoId'];
        $userId = (int)$request->post['user_id'];

        if (ConversationRepository::addUserToConversation($userId, $convoId, $request->dbconn)) {
            $request->session['ui_notify'] = "User $userId joined conversation.";
        } else {
            $request->session['ui_notify'] = "No update occurred.";
        }

        $request->redirect['location'] = $request->server['REQUEST_URI'];
        header("Location: " . $request->server['REQUEST_URI']);
        throw new ExitException();
    }


    // Action: adjust Class details
    if (
        isset($request->post['action']) &&
        $request->post['action'] === 'adjust_class'
    ) {
        $classId = (int)$request->post['classId'];
        $subject = $request->post['subject'] === '' ? null : $request->post['subject'];
        $teacher = $request->post['teacher'] === '' ? null : $request->post['teacher'];
        $room = $request->post['room'] === '' ? null : $request->post['room'];

        if (AdminRepository::adjustClass((int)$classId, $subject, $teacher, $room, $request->dbconn)) {
            $request->session['ui_notify'] = "Adjusted class $classId... See new details in page.";
        } else {
            $request->session['ui_notify'] = "No update occurred.";
        }

        $request->redirect['location'] = $request->server['REQUEST_URI'];
        header("Location: " . $request->server['REQUEST_URI']);
        throw new ExitException();
    }

    // Action: MOD announcement
    if (
        isset($request->post['action']) &&
        $request->post['action'] === 'mod_announcement'
    ) {
        $convoId = (int)$request->post['cID'];
        $modID = (int)$request->post['authorID'];
        $type = (string)$request->post['type'];
        

        if (AdminRepository::makeModAnnouncement($convoId, $modID, $type, $request->dbconn)) {
            $request->session['ui_notify'] = "Made mod announcement of type: $type to C$convoId";
        } else {
            $request->session['ui_notify'] = "Mod Message Failed";
        }

        $request->redirect['location'] = $request->server['REQUEST_URI'];
        header("Location: " . $request->server['REQUEST_URI']);
        throw new ExitException();
    }

   
    
    
}

// FETCH DATA FOR VIEW RENDERING
$conversationData = ConversationRepository::getAllConversationNames($request->dbconn);
$pendingClasses = AdminRepository::fetchAllUnverifiedClasses($request->dbconn);
$confirmedClasses = AdminRepository::fetchAllVerifiedClasses($request->dbconn);
$viewingConversation = (isset($request->session['conversationID'])) || (isset($request->post['conversationID']) && is_numeric($request->post['conversationID']) && ($request->post['action'] ?? '') === 'access');
$allUsers = AdminRepository::fetchAllUsers($request->dbconn);

if ($viewingConversation) {
    $cID = $request->post['conversationID'] ?? $request->session['conversationID'];

    $request->session['conversationID'] = $cID;
    
    $currentConvoData = ConversationRepository::getConvoInfo($cID, $request->dbconn);
    $msgs = AdminRepository::fetchAllConvoMessages($cID, $request->dbconn);
    
    $sortedArrayMessages = [];
    foreach ($msgs as $msg) {
        $sortedArrayMessages[$msg['coalesced_par_id']][] = $msg;
    }

    $usrs = ConversationRepository::fetchUsers($cID, $request->dbconn);
    $joinUrl = ConversationRepository::getCodeGivenConvoID($cID);
    $whatTheConvoIdShouldBe = ConversationRepository::getConvoIDGivenCode($joinUrl); 
}
?>























<!DOCTYPE html>
<html>
<head>
    
    <title>Admin Portal</title>
    <meta charset="UTF-8">
    <style>
        .inline-form { display: inline; margin-left: 10px; }
        .delete-btn { color: red; background: none; border: none; cursor: pointer; text-decoration: underline; padding: 0; }
        .msg-row { margin-bottom: 5px; }
    </style>
</head>
<body>

    <?php if (isset($request->session['ui_notify'])): ?>
        <div style="background: #eee; padding: 10px; margin-bottom: 15px;">
            <?= htmlspecialchars($request->session['ui_notify']); ?>
            <?php unset($request->session['ui_notify']); ?>
        </div>
    <?php endif; ?>

    <?php if (!$viewingConversation): ?>

        <h1>Welcome to Admin Portal:</h1>
        <h3>Logged in as (Admin): <?= htmlspecialchars($request->session['name']) ?> </h3>
        <a href="logout">Logout</a> | <a href="home">Home</a>

        <h3>Select Conversation</h3>
        <form id="conversationForm" method="POST">
            <label for="conversationSelector">Choose a conversation:</label>
            <select id="conversationSelector" name="conversationID" required>
                <?php foreach ($conversationData as $conversation): ?>
                    <option value="<?= (int)$conversation['ID'] ?>">
                        <?= htmlspecialchars($conversation['ID'] . ": " . $conversation['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="csrf_token" value="<?=$request->session['csrf_token']?>">
            <button type="submit" name="action" value="access">Access</button>
            <button type="submit" name="action" value="delete" onclick="return confirm('DELETE FOREVER!!??')" style="color: red;">Delete</button>
        </form>

        <h3>Create Conversation</h3>
        <form id="newConversationForm" method="POST">
            <input type="text" name="newConvoName" required>
            <input type="hidden" name="csrf_token" value="<?=$request->session['csrf_token']?>" >
            <button type="submit">Create New</button>
        </form>

        <h3>Grant Admin Permissions</h3>
        <form id="newAdmin" method="POST">
            <input type="email" name="newAdminEmail" required>
            <input type="hidden" name="csrf_token" value="<?=$request->session['csrf_token']?>" >
            <button type="submit">Set Admin</button>
        </form>

        <h3>Access User View</h3>
        <form id="userID" method="POST">
            <input type="number" name="impersonateID" required>
            <input type="hidden" name="csrf_token" value="<?=$request->session['csrf_token']?>" >
            <button type="submit">Go</button>
        </form>

        <h3>Create Mod Profile</h3>
        <form id="newMod" method="POST">
            <input type="text" name="newName" placeholder="Name" required>
            <input type="text" name="label"  placeholder="Label" required>
            <input type="hidden" name="csrf_token" value="<?=$request->session['csrf_token']?>" >
            <button type="submit" name="action" value="create_mod">Spawn</button>
        </form>


        <h3>Send Mod announcement</h3>
        <form id="modAnn" method="POST">
            <input type="number" name="cID" placeholder="Conversation ID" required>
            <input type="number" name="authorID"  placeholder="MOD AUTHOR ID" required>
            <select name="type" required>
                <option value="" disabled selected>Select a label</option>
                <option value="welcome_new">New Hub- Plaza Rules/Guidelines</option>
            </select>
            <input type="hidden" name="csrf_token" value="<?=$request->session['csrf_token']?>" >
            <button type="submit" name="action" value="mod_announcement">Send</button>
        </form>

        
        <h2>All Classes</h2>
        <?php if (empty($pendingClasses) && empty($confirmedClasses)): ?>

            <p>No classes.</p>

        <?php else: ?>
            <h3>Pending Class Requests (Still Visible to Users)</h3>

            <?php foreach ($pendingClasses as $class): ?>

                <div style="margin-bottom: 10px;">
                    <?= htmlspecialchars($class['ID'])?>:&nbsp&nbsp
                    <b><?= htmlspecialchars($class['subject']) ?></b>
                    -
                    <?= htmlspecialchars($class['teacher']) ?>
                    - Room <?= htmlspecialchars($class['room']) ?>

                    <form method="POST" style="display:inline;">
                        <input type="hidden" 
                            name="classID" 
                            value="<?= (int)$class['ID'] ?>">

                        <input type="hidden"
                            name="csrf_token"
                            value="<?= $request->session['csrf_token'] ?>">

                        <button 
                            type="submit"
                            name="action"
                            value="approve_class">
                            Approve For (Locked) Convo
                        </button>

                        <button
                            type="submit"
                            name="action"
                            value="reject_class"
                            onclick="return confirm('Reject this class?')">
                            Reject
                        </button>
                    </form>

                </div>

            <?php endforeach; ?>

            <br><br>
            <h3>Approved Classes (Have Conversation Hub):</h3><br>

            <?php foreach ($confirmedClasses as $class): ?>
                <br>
                <?= htmlspecialchars($class['ID'])?>:&nbsp&nbsp
                <b><?= htmlspecialchars($class['subject']) ?></b>
                    -
                    <?= htmlspecialchars($class['teacher']) ?>
                    - Room <?= htmlspecialchars($class['room']) ?>

            <?php endforeach; ?>
            
            <h3>Adjust Class Details</h3>

            <form class="inline-form" method="POST">
                
                <input type="hidden" name="csrf_token" value="<?=$request->session['csrf_token']?>" >
                <input type="number" name="classId" step="1" placeholder="Class ID" required>
                <input type="text" name="subject" placeholder="Subject..." >
                <input type="text" name="teacher" placeholder="Teacher..." >
                <input type="text" name="room" placeholder="Room..." >
                
                <button type="submit" name="action" value="adjust_class" class="submit-btn">Update</button>
            </form>

            
            <?php endif; ?>

            <br><br><br><br>
            <h3>All Users Info:</h3><br>

            <?php foreach ($allUsers as $user): ?>
                <br>
                <div <?= $user['is_admin'] ? "style='color: red;'" : null ?>>
                <b><?= htmlspecialchars($user['ID']) ?></b>
                    . 
                    <?= htmlspecialchars($user['name']) ?>
                    -  <?= htmlspecialchars($user['email'] ?? "NONE") ?>
                    -  w<?= htmlspecialchars($user['warnings']) ?>
                    -  <?= htmlspecialchars($user['school_id'] ?? "NONE") ?>
                    -  R(<?= htmlspecialchars($user['rel_1'] ?? "ERR") . ", ". htmlspecialchars($user['rel_2'] ?? "ERR"). ", ". htmlspecialchars($user['rel_3'] ?? "ERR") ?>)
                    - &emsp; <?= htmlspecialchars(json_decode($user['public_flags'] ?? "[]", true)['label'] ?? "") ?>
                </div>

            <?php endforeach; ?>



        
        
    <?php else: // Viewing a specific conversation ?>

        <h1>In Conversation: <?= htmlspecialchars($currentConvoData['name'] ?? '') ?></h1>
        <a href="home">Home</a> | <a href="admin?back=true">Back</a>

        <h3>Welcome to Conversation: <?= htmlspecialchars($currentConvoData['ID'] ?? '') ?> </h3>
            
        <p>  
            Code: <?= htmlspecialchars($joinUrl) ?> <br>
            (Crosscheck) ID: <?= htmlspecialchars($whatTheConvoIdShouldBe) ?> 

            Access Mode: <?= htmlspecialchars($currentConvoData['access_mode'])?>
            Rankings: <?= htmlspecialchars($currentConvoData['rankings'] ?? "none")?>
        </p>
        
        <h4>Access Mode: 0- Normal, 1-readonly, 2-lockedsufficientusers, 3-lockdown, 4-otherdisabled</h4>
        <form class="inline-form" method="POST">
            <input type="hidden" name="convoId" value="<?= htmlspecialchars($cID) ?>">
            <input type="hidden" name="csrf_token" value="<?=$request->session['csrf_token']?>" >
            <input type="number" name="mode" step="1" placeholder="Enter mode" required>
            
            <button type="submit" name="action" value="convo_access_adjustment" class="submit-btn">Set</button>
        </form>
        
        <h2>Messages:</h2>
        <div id="messageList">
            <?php if (empty($msgs)): ?>
                <div>No Messages Here</div>
            <?php else: ?>
                <?php foreach (($sortedArrayMessages[$cID] ?? []) as $msg0): ?>
                    <div class="0-layer-messages msg-row">
                        <?= htmlspecialchars($msg0['user_id'] . ' sent: ' . json_encode(json_decode($msg0['data']), JSON_PRETTY_PRINT)) ?>
                        
                        <form class="inline-form" method="POST">
                            <input type="hidden" name="messageID" value="<?= htmlspecialchars($msg0['ID']) ?>">
                            <input type="hidden" name="currentConvoID" value="<?= htmlspecialchars($cID) ?>">
                            <input type="hidden" name="csrf_token" value="<?=$request->session['csrf_token']?>" >
                            <button type="submit" name="action" value="delete_message" class="delete-btn" onclick="return confirm('WARNING: Delete this message and all replies permanently??')">[Delete]</button>
                        </form>
                    </div>
                    
                    <?php if (array_key_exists($msg0['ID'], $sortedArrayMessages)): ?>
                        <details >
                            <summary>Expand sub-thread (<?= count($sortedArrayMessages[$msg0['ID']]) ?>)</summary>
                            <?php foreach ($sortedArrayMessages[$msg0['ID']] as $msg1): ?>
                                <div class="1-layer-messages msg-row">
                                    &emsp;<?= htmlspecialchars("∟ " . $msg1['user_id'] . ' sent: ' . json_encode(json_decode($msg1['data']), JSON_PRETTY_PRINT)) ?>
                                    
                                    <form class="inline-form" method="POST">
                                        <input type="hidden" name="messageID" value="<?= htmlspecialchars($msg1['ID']) ?>">
                                        <input type="hidden" name="currentConvoID" value="<?= htmlspecialchars($cID) ?>">
                                        <input type="hidden" name="csrf_token" value="<?=$request->session['csrf_token']?>" >
                                        <button type="submit" name="action" value="delete_message" class="delete-btn" onclick="return confirm('WARNING: Delete this message and all replies permanently??')">[Delete]</button>
                                    </form>
                                </div>

                                <?php if (array_key_exists($msg1['ID'], $sortedArrayMessages)): ?>
                                    <details style="margin-left: 18px;">
                                        <summary>Expand sub-thread (<?= count($sortedArrayMessages[$msg1['ID']]) ?>)</summary>
                                        <?php foreach ($sortedArrayMessages[$msg1['ID']] as $msg2): ?>
                                            <div class="2-layer-messages msg-row">
                                                &emsp;<?= htmlspecialchars("∟ " . $msg2['user_id'] . ' sent: ' . json_encode(json_decode($msg2['data']), JSON_PRETTY_PRINT)) ?>
                                                
                                                <form class="inline-form" method="POST">
                                                    <input type="hidden" name="messageID" value="<?= htmlspecialchars($msg2['ID']) ?>">
                                                    <input type="hidden" name="currentConvoID" value="<?= htmlspecialchars($cID) ?>">
                                                    <input type="hidden" name="csrf_token" value="<?=$request->session['csrf_token']?>" >
                                                    <button type="submit" name="action" value="delete_message" class="delete-btn" onclick="return confirm('WARNING: Delete this message and all replies permanently??')">[Delete]</button>
                                                </form>
                                            </div>

                                            <?php if (array_key_exists($msg2['ID'], $sortedArrayMessages)): ?>
                                                <details style="margin-left: 18px;">
                                                    <summary>Expand sub-thread (<?= count($sortedArrayMessages[$msg2['ID']]) ?>)</summary>
                                                    <?php foreach ($sortedArrayMessages[$msg2['ID']] as $msg3): ?>
                                                        <div class="3-layer-messages msg-row">
                                                            &emsp;<?= htmlspecialchars("∟ " . $msg3['user_id'] . ' sent: ' . json_encode(json_decode($msg3['data']), JSON_PRETTY_PRINT)) ?>
                                                            
                                                            <form class="inline-form" method="POST">
                                                                <input type="hidden" name="messageID" value="<?= htmlspecialchars($msg3['ID']) ?>">
                                                                <input type="hidden" name="currentConvoID" value="<?= htmlspecialchars($cID) ?>">
                                                                <input type="hidden" name="csrf_token" value="<?=$request->session['csrf_token']?>" >
                                                                <button type="submit" name="action" value="delete_message" class="delete-btn" onclick="return confirm('WARNING: Delete this message and all replies permanently??')">[Delete]</button>
                                                            </form>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </details>
                                            <?php endif; ?>

                                        <?php endforeach; ?>
                                    </details>
                                <?php endif; ?>

                            <?php endforeach; ?>
                        </details>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <h2>Users:</h2>
        <div id="userList">
            <?php if (empty($usrs)): ?>
                <div>No Users Here</div>
            <?php else: ?>
                <?php foreach ($usrs as $usr): ?>
                    <div class="msg-row">
                        <?= htmlspecialchars("ID: " . $usr['ID'] . " " . $usr['name'] . " w-" . $usr['warnings']) ?>
                        
                        <form class="inline-form" method="POST">
                            <input type="hidden" name="targetUserID" value="<?= htmlspecialchars($usr['ID']) ?>">
                            <input type="hidden" name="currentConvoID" value="<?= htmlspecialchars($cID) ?>">
                            <input type="hidden" name="csrf_token" value="<?=$request->session['csrf_token']?>" >
                            <button type="submit" name="action" value="delete_user" class="delete-btn" onclick="return confirm('Remove user from this conversation?')">[Remove User]</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <h2>Assign User:</h2>
        <form class="inline-form" method="POST">
            <input type="hidden" name="convoId" value="<?= htmlspecialchars($cID) ?>">
            <input type="hidden" name="csrf_token" value="<?=$request->session['csrf_token']?>" >
            <input type="number" name="user_id" step="1" placeholder="User ID" required>
            
            <button type="submit" name="action" value="assign_user_into_convo" class="submit-btn">Add</button>
        </form>

        


    <?php endif ?>

</body>
</html>