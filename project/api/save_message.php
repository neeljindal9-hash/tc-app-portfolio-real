<?php

    header('Content-Type: application/json');
    $body = $request->rawContent();
    if (!is_string($body) || ($length ??= strlen($body)) > 1000) {
        $request->status = (400);
        throw new ExitException(json_encode(['error' => "Oversize or Invalid data - strlen $length"."B"]));
    }

    $data = json_decode($body, true);

    if (!is_array($data)) {
        $request->status = (400);
        echo json_encode(['error' => 'Invalid JSON']);
        throw new ExitException();
    }

    $parentID = filter_var($data['parent_id'] ?? null, FILTER_VALIDATE_INT);
    $convoID = filter_var($data['convo_id'] ?? null, FILTER_VALIDATE_INT);
    $messageData = $data['message_data'];
    $isAnonymous = ($data['isAnonymous'] === true) ? true : false;
    $structure = $data['structure'] ?? null;

    if (
        !ProfanityFilter::isClean($messageData['title'] ?? '') ||
        !ProfanityFilter::isClean($messageData['text'] ?? '')
    ) {
        $request->status = (403);
        echo json_encode(['error' => 'Content Guidelines Violated']);
        throw new ExitException();
    }

    if ($parentID === false || !$messageData['text'] ||  $messageData['text'] === '') {
        $request->status = (400);
        throw new ExitException(json_encode(['error' => 'Missing or invalid data']));
    }

    try {
        // AUTHORIZATION
        if (!ConversationRepository::verifyAccess($request->session['user_id'] ?? null, $convoID, $request->dbconn)) {
            $request->status = (403);
            throw new ExitException(json_encode(['error' => 'Unauthorized']));
        }

        // DATA VALIDATION
        $isFirstLayerMessage = (ConversationRepository::isValidConversation($parentID));
        if (!$isFirstLayerMessage && ($structure !== null || $isAnonymous !== false) ) {
            error_log("User " .$request->session['id'] ." sent invalid request: " . print_r($data));
            throw new ExitException(json_encode(['error' => 'Missing or invalid data']));
        }

        // hardcoded watcher_layer settings
        $watcher_layer = 1;
        if ($isFirstLayerMessage) {
            $watcher_layer = 0;
        }

        // EXECUTION
        $sendReceipt = ConversationRepository::sendMessage($parentID, $request->session['user_id'], $convoID, $messageData, $structure, $isAnonymous, $watcher_layer, true, $request->dbconn); // important, useTransaction is true here

        if ($sendReceipt['success'] !== true) {
            $request->status = (400);
            throw new ExitException(json_encode(['error' => $sendReceipt['error_msg']]));
        }
    } catch (ExitException $ee) {
        throw $ee;
    } catch(Exception $e) {
        $request->status = (500);
        error_log($e->getMessage());
        throw new ExitException(json_encode(['error' => 'Internal Server Error']));
    }

    $request->status = (200);
    echo json_encode(['success' => true]);
    
    
?>