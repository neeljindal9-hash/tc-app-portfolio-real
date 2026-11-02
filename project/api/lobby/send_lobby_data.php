<?php

    header('Content-Type: application/json');
    $body = $request->rawContent();
    if (!is_string($body) || ($length ??= strlen($body)) > 300) {
        $request->status = (400);
        throw new ExitException(json_encode(['error' => "Oversize or Invalid data - strlen $length"."B"]));
    }

    $data = json_decode($body, true);

    if (!is_array($data)) {
        $request->status = (400);
        echo json_encode(['error' => 'Invalid JSON']);
        throw new ExitException();
    }

    $convoID = filter_var($data['convo_id'] ?? null, FILTER_VALIDATE_INT);
    $isInt = filter_var($data['is_int_data'] ?? null, FILTER_VALIDATE_INT);
    $multipart = filter_var($data['multipart'] ?? null, FILTER_VALIDATE_INT);

    $dataWritten = $data['data_written'] ?? null;
    $dataLocation = $data['data_location'] ?? null;
    $typeExpected = ['string', 'integer'];

    try {

        // DATA VALIDATION 1
        if ($multipart === 1) {
            if (is_array($dataLocation) && is_array($dataWritten) && sizeof($dataWritten) === sizeof($dataLocation)) {
                if (array_unique(array_map('gettype',$dataWritten)) !== [$typeExpected[$isInt]] || array_unique(array_map('gettype',$dataLocation)) !== ['integer']) {
                    $request->status = (400);
                    echo json_encode(['error' => 'Missing or Invalid Data 2']);
                    throw new ExitException();
                }
            } else {
                $request->status = (400);
                echo json_encode(['error' => 'Missing or Invalid Data 3']);
                throw new ExitException();
            }

        } else {
            if (gettype($dataLocation) !== 'integer' || gettype($dataWritten) !== $typeExpected[$isInt]) {
                $request->status = (400);
                echo json_encode(['error' => 'Missing or Invalid Data 4']);
                throw new ExitException();
            }
            $multipart = false;
        }
        if ($isInt === false || $convoID === false) {
            $request->status = (400);
            throw new ExitException(json_encode(['error' => 'Missing or invalid data 5']));
        }

        // AUTHORIZATION
        if (!ConversationRepository::verifyAccess($request->session['user_id'] ?? null, $convoID, $request->dbconn)) {
            $request->status = (403);
            throw new ExitException(json_encode(['error' => 'Unauthorized']));
        }

        // DATA VALIDATION 2
        if ($isInt === 0) {
            if (
                !ProfanityFilter::isClean(json_encode($dataWritten) ?? '')
            ) {
                $request->status = (403);
                echo json_encode(['error' => 'Content Guidelines Violated']);
                throw new ExitException();
            }
        }
       
        
        // EXECUTION
        $sendReceipt = UserLobbyRepository::reportNewInfo($convoID, $request->session['user_id'], $dataLocation, $dataWritten, $multipart, $request->dbconn);

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